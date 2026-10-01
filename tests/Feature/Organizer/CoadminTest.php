<?php

namespace Tests\Feature\Organizer;

use App\Http\Controllers\Event\CoadminInvitationController;
use App\Mail\CoadminInvitationMail;
use App\Models\Event;
use App\Models\EventCoadmin;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\CoadminService;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tests\TestCase;

/**
 * Coadministradores: el dueño invita por correo; quien acepta sólo puede usar el
 * panel y la información del evento.
 */
class CoadminTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Event $event;

    /** Seguro: RefreshDatabase vacía la BD; aborta si no es una BD *_test. */
    protected function beforeRefreshingDatabase()
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException("Abortado: RefreshDatabase iba a vaciar '{$database}'.");
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Los roles vienen del seeder, como en una instalación real.
        $this->seed(RolesAndAdminSeeder::class);

        [$this->owner, $this->event] = $this->makeOrganizerWithEvent();
    }

    /* ---------------------------------------------------------------------
     | Invitar y quitar
     * -------------------------------------------------------------------*/

    public function test_invitar_envia_un_correo_con_liga_firmada(): void
    {
        Mail::fake();

        $this->invite(' Elena@Email.com ')
            ->assertRedirect(route('organizer.settings.index'))
            ->assertSessionHas('success');

        $invitation = $this->event->coadmins()->sole();
        $this->assertSame('elena@email.com', $invitation->email);
        $this->assertFalse($invitation->isAccepted());

        Mail::assertQueued(CoadminInvitationMail::class, function (CoadminInvitationMail $mail) use ($invitation) {
            return $mail->hasTo('elena@email.com')
                && str_contains($mail->acceptUrl, "/coadministrador/invitacion/{$invitation->id}")
                && str_contains($mail->acceptUrl, 'signature=');
        });
    }

    public function test_la_pantalla_lista_pendientes_y_aceptados(): void
    {
        $this->pendingInvitation('pendiente@email.com');
        $this->acceptedCoadmin('Elena Somuano', 'elena@email.com');

        $this->actingAs($this->owner)
            ->get(route('organizer.settings.index'))
            ->assertOk()
            ->assertSeeInOrder(['Tú', 'pendiente@email.com', 'Invitación pendiente', 'Elena Somuano', 'Quitar acceso']);
    }

    public function test_no_puede_invitarse_a_si_mismo_ni_repetir(): void
    {
        Mail::fake();

        $this->invite(strtoupper($this->owner->email))->assertSessionHasErrorsIn('coadmin', 'email');

        $this->pendingInvitation('elena@email.com');
        $this->invite('ELENA@email.com')->assertSessionHasErrorsIn('coadmin', 'email');

        $this->invite('no-es-correo')->assertSessionHasErrorsIn('coadmin', 'email');

        Mail::assertNothingQueued();
    }

    public function test_hay_un_maximo_de_coadministradores(): void
    {
        Mail::fake();

        for ($i = 1; $i <= CoadminService::MAX_PER_EVENT; $i++) {
            $this->pendingInvitation("persona{$i}@email.com");
        }

        $this->invite('una-mas@email.com')->assertSessionHas('error');

        $this->assertSame(CoadminService::MAX_PER_EVENT, $this->event->coadmins()->count());
        Mail::assertNothingQueued();
    }

    public function test_quien_se_registro_por_la_invitacion_conserva_su_cuenta_al_perder_el_acceso(): void
    {
        $invitation = $this->pendingInvitation('nueva@email.com');

        $this->post($this->acceptUrl($invitation), [
            'name' => 'Nueva Persona',
            'password' => 'secreta123',
            'password_confirmation' => 'secreta123',
        ]);

        $user = User::where('email', 'nueva@email.com')->sole();

        $this->actingAs($this->owner)
            ->delete(route('organizer.settings.coadmins.destroy', $invitation->fresh()->id));

        // Pierde el acceso a esa boda, pero no se queda sin ningún rol.
        $user = $user->fresh();
        $this->assertFalse($user->hasRole(EventCoadmin::ROLE));
        $this->assertTrue($user->hasRole('organizer'));

        $this->actingAs($user)->get(route('panel'))
            ->assertOk()
            ->assertSee('Aún no tienes una invitación digital');
    }

    public function test_quitar_acceso_elimina_la_invitacion_y_el_rol(): void
    {
        [$coadmin, $invitation] = $this->acceptedCoadmin('Elena', 'elena@email.com');

        $this->actingAs($this->owner)
            ->delete(route('organizer.settings.coadmins.destroy', $invitation->id))
            ->assertRedirect(route('organizer.settings.index'));

        $this->assertModelMissing($invitation);
        $this->assertFalse($coadmin->fresh()->hasRole(EventCoadmin::ROLE));

        $this->actingAs($coadmin->fresh())
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->assertForbidden();
    }

    public function test_no_puede_quitar_coadministradores_de_otro_evento(): void
    {
        [, $otherEvent] = $this->makeOrganizerWithEvent();
        $foreign = $otherEvent->coadmins()->create(['email' => 'ajeno@email.com']);

        $this->actingAs($this->owner)
            ->delete(route('organizer.settings.coadmins.destroy', $foreign->id))
            ->assertNotFound();

        $this->assertModelExists($foreign);
    }

    /* ---------------------------------------------------------------------
     | Aceptar
     * -------------------------------------------------------------------*/

    public function test_persona_sin_cuenta_crea_la_suya_y_entra_al_panel(): void
    {
        $invitation = $this->pendingInvitation('nueva@email.com');
        $url = $this->acceptUrl($invitation);

        $this->get($url)->assertOk()->assertSee('Crea tu cuenta')->assertSee('nueva@email.com');

        $this->post($url, [
            'name' => 'Nueva Persona',
            // Se ignora: el correo sale de la invitación.
            'email' => 'atacante@email.com',
            'password' => 'secreta123',
            'password_confirmation' => 'secreta123',
        ])->assertRedirect(route('panel'));

        $user = User::where('email', 'nueva@email.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole(EventCoadmin::ROLE));
        // También organizadora: puede comprar su propia invitación.
        $this->assertTrue($user->hasRole('organizer'));
        $this->assertTrue($invitation->fresh()->isAccepted());
        $this->assertDatabaseMissing('users', ['email' => 'atacante@email.com']);
    }

    public function test_liga_alterada_o_vencida_no_acepta_nada(): void
    {
        $invitation = $this->pendingInvitation('nueva@email.com');

        $this->get($this->acceptUrl($invitation) . 'x')->assertOk()->assertSee('La invitación venció');

        $expired = URL::temporarySignedRoute('coadmin.invitation.show', now()->subMinute(), ['invitation' => $invitation->id]);
        $this->post($expired, [
            'name' => 'Nueva',
            'password' => 'secreta123',
            'password_confirmation' => 'secreta123',
        ])->assertSee('La invitación venció');

        // Firma de otra invitación no sirve para ésta.
        $other = $this->pendingInvitation('otra@email.com');
        $forged = str_replace("/invitacion/{$other->id}?", "/invitacion/{$invitation->id}?", $this->acceptUrl($other));
        $this->get($forged)->assertSee('La invitación venció');

        $this->assertDatabaseMissing('users', ['email' => 'nueva@email.com']);
        $this->assertFalse($invitation->fresh()->isAccepted());
    }

    public function test_quien_ya_tiene_cuenta_inicia_sesion_y_vuelve_a_aceptar(): void
    {
        $existing = User::factory()->create(['email' => 'elena@email.com']);
        $invitation = $this->pendingInvitation('elena@email.com');
        $url = $this->acceptUrl($invitation);

        $this->get($url)->assertRedirect(route('login'));

        // Tras iniciar sesión regresa a la liga, que acepta la invitación.
        $this->post(route('login.perform'), ['email' => 'elena@email.com', 'password' => 'password'])
            ->assertRedirect($url);

        $this->get($url)->assertRedirect(route('panel'));

        $this->assertTrue($invitation->fresh()->isAccepted());
        $this->assertSame($existing->id, $invitation->fresh()->user_id);
        $this->assertNull(session(CoadminInvitationController::SESSION_RETURN_URL));
    }

    public function test_quien_ya_tiene_cuenta_no_puede_registrarse_por_la_liga(): void
    {
        $existing = User::factory()->create(['email' => 'elena@email.com']);
        $invitation = $this->pendingInvitation('elena@email.com');

        $this->post($this->acceptUrl($invitation), [
            'name' => 'Suplantador',
            'password' => 'secreta123',
            'password_confirmation' => 'secreta123',
        ])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertTrue(password_verify('password', $existing->fresh()->password), 'No debe cambiar su contraseña.');
        $this->assertFalse($invitation->fresh()->isAccepted());
    }

    public function test_con_otra_cuenta_abierta_no_se_acepta(): void
    {
        $invitation = $this->pendingInvitation('elena@email.com');

        $this->actingAs(User::factory()->create(['email' => 'otra@email.com']))
            ->get($this->acceptUrl($invitation))
            ->assertOk()
            ->assertSee('Esta invitación es para otra cuenta');

        $this->assertFalse($invitation->fresh()->isAccepted());
    }

    public function test_invitacion_cancelada_ya_no_sirve(): void
    {
        $invitation = $this->pendingInvitation('elena@email.com');
        $url = $this->acceptUrl($invitation);
        $invitation->delete();

        $this->get($url)->assertOk()->assertSee('Esta invitación ya no está disponible');
    }

    /* ---------------------------------------------------------------------
     | Qué puede hacer un coadministrador
     * -------------------------------------------------------------------*/

    public function test_coadministrador_edita_la_boda_desde_invitaciones_compartidas(): void
    {
        [$coadmin] = $this->acceptedCoadmin('Elena', 'elena@email.com');

        // Sin invitación propia, su panel le sugiere comprar una.
        $this->actingAs($coadmin)->get(route('panel'))
            ->assertOk()
            ->assertSee('Aún no tienes una invitación digital')
            ->assertSee(route('shared-events.index'));

        $this->actingAs($coadmin)->get(route('shared-events.index'))
            ->assertOk()
            ->assertSee('Activa')
            ->assertSee(route('events.wizard.edit', ['event' => $this->event->slug]));

        $this->actingAs($coadmin)
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->assertOk();
    }

    public function test_organizador_sin_bodas_compartidas_no_ve_esa_opcion(): void
    {
        $this->actingAs($this->owner)->get(route('panel'))
            ->assertDontSee('href="' . route('shared-events.index') . '"', false);
    }

    public function test_coadministrador_no_entra_a_configuracion_invitados_ni_notificaciones(): void
    {
        [$coadmin] = $this->acceptedCoadmin('Elena', 'elena@email.com');

        foreach (['organizer.settings.index', 'organizer.guests.index', 'organizer.notifications.index'] as $route) {
            $this->actingAs($coadmin)->get(route($route))->assertForbidden();
        }

        $this->actingAs($coadmin)
            ->post(route('organizer.settings.coadmins.store'), ['email' => 'x@email.com'])
            ->assertForbidden();
    }

    public function test_el_menu_del_coadministrador_tiene_panel_informacion_y_compartidas(): void
    {
        [$coadmin] = $this->acceptedCoadmin('Elena', 'elena@email.com');

        $this->actingAs($coadmin)->get(route('panel'))
            ->assertSee('href="' . route('events.info') . '"', false)
            ->assertSee('href="' . route('shared-events.index') . '"', false)
            // href completo: /configuracion es prefijo de /configuracion-evento/...
            ->assertDontSee('href="' . route('organizer.guests.index') . '"', false)
            ->assertDontSee('href="' . route('organizer.notifications.index') . '"', false)
            ->assertDontSee('href="' . route('organizer.settings.index') . '"', false);
    }

    public function test_coadministrador_no_edita_otros_eventos(): void
    {
        [$coadmin] = $this->acceptedCoadmin('Elena', 'elena@email.com');
        [, $otherEvent] = $this->makeOrganizerWithEvent();

        $this->actingAs($coadmin)
            ->get(route('events.wizard.edit', ['event' => $otherEvent->slug, 'step' => 'general']))
            ->assertForbidden();
    }

    public function test_invitacion_pendiente_no_da_acceso(): void
    {
        $user = User::factory()->create(['email' => 'elena@email.com']);
        $user->roles()->attach(Role::firstOrCreate(['name' => EventCoadmin::ROLE])->id);
        $this->pendingInvitation('elena@email.com');

        $this->actingAs($user)
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->assertForbidden();
    }

    public function test_coadministrador_va_al_panel_al_iniciar_sesion(): void
    {
        [$coadmin] = $this->acceptedCoadmin('Elena', 'elena@email.com');
        Auth::logout();

        $this->post(route('login.perform'), ['email' => $coadmin->email, 'password' => 'password'])
            ->assertRedirect(route('panel'));
    }

    /* ---------------------------------------------------------------------
     | Helpers
     * -------------------------------------------------------------------*/

    private function invite(string $email)
    {
        return $this->actingAs($this->owner)
            ->from(route('organizer.settings.index'))
            ->post(route('organizer.settings.coadmins.store'), ['email' => $email]);
    }

    private function pendingInvitation(string $email): EventCoadmin
    {
        return $this->event->coadmins()->create(['email' => $email, 'invited_by' => $this->owner->id]);
    }

    /** @return array{0: User, 1: EventCoadmin} */
    private function acceptedCoadmin(string $name, string $email): array
    {
        $user = User::factory()->create(['name' => $name, 'email' => $email]);
        $invitation = $this->pendingInvitation($email);
        app(CoadminService::class)->accept($invitation, $user);

        return [$user->fresh(), $invitation->fresh()];
    }

    private function acceptUrl(EventCoadmin $invitation): string
    {
        return app(CoadminService::class)->acceptUrl($invitation);
    }

    private function makeOrganizerWithEvent(): array
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'organizer'])->id);

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
            'custom_url' => 'pareja-' . uniqid(),
        ]);

        return [$user, $event];
    }
}
