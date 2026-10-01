<?php

namespace Tests\Feature\Onboarding;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * URL amigable de la invitación: se genera en el registro con los nombres de la
 * pareja, es única y es la que usan los enlaces de la invitación digital.
 */
class CustomUrlTest extends TestCase
{
    use RefreshDatabase;

    /** Seguro: RefreshDatabase vacía la BD; aborta si no es una BD *_test. */
    protected function beforeRefreshingDatabase()
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException("Abortado: RefreshDatabase iba a vaciar '{$database}'.");
        }
    }

    /* ---------------------------------------------------------------------
     | Generación en el registro
     * -------------------------------------------------------------------*/

    public function test_genera_la_url_amigable_con_los_nombres_de_la_pareja(): void
    {
        [$user, $event] = $this->userWithEvent();

        $this->submitProfile($user, 'Ana', 'Luis')->assertRedirect(route('panel'));

        $this->assertSame('ana-y-luis', $event->fresh()->custom_url);
    }

    public function test_guarda_los_nombres_cortos_de_la_direccion(): void
    {
        [$user, $event] = $this->userWithEvent();

        $this->submitProfile($user, 'Ana', 'Luis');

        $event = $event->fresh();
        // Sólo para la URL: los nombres del evento se capturan en el wizard.
        $this->assertSame(['Ana', 'Luis'], $event->urlNames());
        $this->assertSame(['', ''], $event->coupleNames());
    }

    public function test_quita_acentos_y_caracteres_especiales(): void
    {
        [$user, $event] = $this->userWithEvent();

        $this->submitProfile($user, 'José Ángel', 'María Ñúñez');

        $this->assertSame('jose-angel-y-maria-nunez', $event->fresh()->custom_url);
    }

    public function test_dos_parejas_con_los_mismos_nombres_obtienen_urls_distintas(): void
    {
        // Antes: Str::slug a secas chocaba contra el índice único y el registro fallaba.
        $this->eventWithUrl('ana-y-luis');
        [$user, $event] = $this->userWithEvent();

        $this->submitProfile($user, 'Ana', 'Luis')->assertRedirect(route('panel'));

        $this->assertSame('ana-y-luis-2', $event->fresh()->custom_url);
    }

    public function test_reenviar_el_registro_conserva_su_propia_url(): void
    {
        [$user, $event] = $this->userWithEvent();
        $event->update(['custom_url' => 'ana-y-luis']);

        $this->submitProfile($user, 'Ana', 'Luis');

        $this->assertSame('ana-y-luis', $event->fresh()->custom_url, 'No debe chocar contra su propia URL.');
    }

    /* ---------------------------------------------------------------------
     | Vista previa en la pantalla de registro
     * -------------------------------------------------------------------*/

    public function test_la_vista_previa_devuelve_la_url_exacta_que_se_guardara(): void
    {
        $this->eventWithUrl('ana-y-luis');
        [$user] = $this->userWithEvent();

        $this->getJson(route('onboarding.url.preview', [
            'partner_1_name' => 'Ana',
            'partner_2_name' => 'Luis',
            'email' => $user->email,
        ]))
            ->assertOk()
            ->assertJson([
                'slug' => 'ana-y-luis-2',
                'url' => url('/invitacion/ana-y-luis-2'),
            ]);
    }

    public function test_la_vista_previa_espera_los_dos_nombres(): void
    {
        $this->getJson(route('onboarding.url.preview', ['partner_1_name' => 'Ana']))
            ->assertOk()
            ->assertJson(['slug' => null, 'url' => null]);
    }

    public function test_el_campo_de_la_url_es_de_solo_lectura_y_no_se_envia(): void
    {
        [$user] = $this->userWithEvent();

        $html = $this->get(URL::temporarySignedRoute('onboarding.profile.view', now()->addMinutes(30), ['email' => $user->email]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*id="invitation_url_preview"[^>]*>/s', $html);
        preg_match('/<input[^>]*id="invitation_url_preview"[^>]*>/s', $html, $match);

        $this->assertStringContainsString('readonly', $match[0], 'El campo no debe ser editable.');
        $this->assertStringNotContainsString('name=', $match[0], 'El campo sólo muestra la URL: no debe enviarse.');
    }

    /* ---------------------------------------------------------------------
     | Uso de la URL amigable
     * -------------------------------------------------------------------*/

    public function test_el_enlace_del_invitado_usa_la_url_amigable(): void
    {
        $event = $this->eventWithUrl('ana-y-luis');
        $guest = Guest::factory()->create(['user_id' => $event->user_id]);
        $uuid = (string) Str::uuid();
        $guest->events()->attach($event->id, ['uuid' => $uuid, 'max_passes' => 1]);

        $this->assertSame(url('/invitacion/ana-y-luis'), $event->invitationUrl());
        $this->assertSame(url("/invitacion/ana-y-luis/{$uuid}"), $guest->invitationFor($event)->invitationUrl());
    }

    public function test_sin_url_amigable_usa_el_identificador_interno(): void
    {
        [, $event] = $this->userWithEvent();

        $this->assertSame(url('/invitacion/' . $event->slug), $event->invitationUrl());
    }

    public function test_crear_o_guardar_un_evento_no_genera_la_url_automaticamente(): void
    {
        // El checkout crea el evento sin nombres: el trait Sluggable no debe inventar
        // una URL (sin 'source' usaría el JSON del evento como texto).
        [, $event] = $this->userWithEvent();
        $event->update(['title' => 'Otro título']);

        $this->assertNull($event->fresh()->custom_url);
    }

    /* ------------------------------------------------------------------ */

    /** @return array{0: User, 1: Event} */
    private function userWithEvent(): array
    {
        $user = User::factory()->create();

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
            'custom_url' => null,
        ]);

        return [$user, $event];
    }

    private function eventWithUrl(string $customUrl): Event
    {
        [, $event] = $this->userWithEvent();
        $event->update(['custom_url' => $customUrl]);

        return $event;
    }

    private function submitProfile(User $user, string $partner1, string $partner2)
    {
        return $this->post(route('onboarding.profile.store'), [
            'email' => $user->email,
            'name' => 'Organizador',
            'partner_1_name' => $partner1,
            'partner_2_name' => $partner2,
        ]);
    }
}
