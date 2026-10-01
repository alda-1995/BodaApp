<?php

namespace Tests\Feature\Event;

use App\DTOs\Payment\ProcessWebhookOrderDTO;
use App\Models\Event;
use App\Models\EventCoadmin;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\CoadminService;
use App\Services\OrderService;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Vigencia de la invitación digital: se calcula con la fecha del evento, al
 * vencer se deshabilita y el organizador puede comprar otra.
 */
class EventLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;
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

        Storage::fake('public');
        config(['events.default_duration_days' => 21]);

        // Los roles vienen del seeder, como en una instalación real.
        $this->seed(RolesAndAdminSeeder::class);

        [$this->organizer, $this->event] = $this->organizerWithEvent();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /* ---------------------------------------------------------------------
     | Cálculo de la vigencia
     * -------------------------------------------------------------------*/

    public function test_al_comprar_no_se_fija_fecha_ni_vigencia(): void
    {
        $this->fulfill('nueva@pareja.com');

        $event = User::where('email', 'nueva@pareja.com')->sole()->currentEvent();

        // La fecha la captura la pareja en el wizard; hasta entonces no corre nada.
        $this->assertNull($event->event_date);
        $this->assertNull($event->expires_at);
        $this->assertTrue($event->isAvailable());
        $this->assertSame(Event::STATUS_PENDING_DATE, $event->statusKey());
    }

    public function test_capturar_la_fecha_da_21_dias_por_defecto(): void
    {
        $this->event->update(['event_date' => '2026-12-05 18:00:00']);

        $this->assertSame('2026-12-26 18:00:00', $this->event->fresh()->expires_at->format('Y-m-d H:i:s'));
    }

    public function test_respeta_la_duracion_de_la_plantilla(): void
    {
        $this->event->template->update(['duration_days' => 10]);

        $this->event->fresh()->update(['event_date' => '2026-12-05 18:00:00']);

        $this->assertSame('2026-12-15', $this->event->fresh()->expires_at->format('Y-m-d'));
    }

    public function test_el_wizard_explica_la_vigencia_junto_a_la_fecha(): void
    {
        $this->event->update(['event_date' => '2026-12-05 18:00:00']);

        $this->actingAs($this->organizer)
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->assertOk()
            ->assertSee('Tu invitación seguirá activa 21 días después de la fecha del evento (hasta el 26/12/2026).');
    }

    /* ---------------------------------------------------------------------
     | Invitación vencida
     * -------------------------------------------------------------------*/

    public function test_vencida_el_dueno_ve_la_invitacion_a_comprar_otra(): void
    {
        $this->expire($this->event);

        $this->actingAs($this->organizer)
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->assertRedirect(route('events.info'));

        $this->actingAs($this->organizer)->get(route('events.info'))
            ->assertOk()
            ->assertSee('Invitación vencida')
            ->assertSee('Comprar otra invitación');

        $this->actingAs($this->organizer)->get(route('panel'))
            ->assertOk()
            ->assertSee('Tu invitación digital ya no está activa');
    }

    public function test_vencida_ya_no_se_puede_guardar(): void
    {
        $this->expire($this->event);

        $this->actingAs($this->organizer)
            ->put(route('events.wizard.update', ['event' => $this->event->slug, 'step' => 'general']), [
                'name_event' => 'Otra',
            ])
            ->assertForbidden();
    }

    public function test_el_comando_deshabilita_solo_las_vencidas(): void
    {
        $this->expire($this->event);
        [, $current] = $this->organizerWithEvent();
        $current->update(['event_date' => now()->addMonth()]);
        [, $withoutDate] = $this->organizerWithEvent();
        $withoutDate->update(['event_date' => null]);

        $this->artisan('events:deactivate-expired')->assertSuccessful();

        $this->assertFalse($this->event->fresh()->is_active);
        $this->assertTrue($current->fresh()->is_active);
        $this->assertTrue($withoutDate->fresh()->is_active);
    }

    public function test_el_comando_esta_programado(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('events:deactivate-expired')
            ->assertSuccessful();
    }

    /* ---------------------------------------------------------------------
     | Sin invitación / comprar otra
     * -------------------------------------------------------------------*/

    public function test_con_invitacion_vigente_informacion_lleva_al_wizard(): void
    {
        $this->actingAs($this->organizer)
            ->get(route('events.info'))
            ->assertRedirect(route('events.wizard.edit', ['event' => $this->event->slug]));
    }

    public function test_sin_invitacion_se_sugiere_comprar_una(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'organizer'])->id);

        foreach (['events.info', 'panel'] as $route) {
            $this->actingAs($user)->get(route($route))
                ->assertOk()
                ->assertSee('Aún no tienes una invitación digital')
                ->assertSee('href="' . route('home') . '"', false);
        }
    }

    public function test_el_menu_oculta_invitados_notificaciones_y_configuracion_sin_invitacion_vigente(): void
    {
        $this->expire($this->event);

        $response = $this->actingAs($this->organizer)->get(route('panel'))->assertOk();

        foreach (['organizer.guests.index', 'organizer.notifications.index', 'organizer.settings.index'] as $hidden) {
            $response->assertDontSee('href="' . route($hidden) . '"', false);
        }

        $response->assertSee('href="' . route('events.info') . '"', false);
    }

    public function test_sin_invitacion_vigente_esas_pantallas_redirigen(): void
    {
        $routes = ['organizer.guests.index', 'organizer.notifications.index', 'organizer.settings.index'];

        // Vencida.
        $this->expire($this->event);

        foreach ($routes as $route) {
            $this->actingAs($this->organizer)->get(route($route))
                ->assertRedirect(route('events.info'))
                ->assertSessionHas('error');
        }

        // Nunca compró.
        $user = User::factory()->create();
        $user->roles()->attach(Role::named('organizer')->id);

        foreach ($routes as $route) {
            $this->actingAs($user)->get(route($route))->assertRedirect(route('events.info'));
        }
    }

    public function test_el_menu_los_muestra_con_invitacion_vigente(): void
    {
        $response = $this->actingAs($this->organizer)->get(route('panel'))->assertOk();

        foreach (['organizer.guests.index', 'organizer.notifications.index', 'organizer.settings.index'] as $visible) {
            $response->assertSee('href="' . route($visible) . '"', false);
        }
    }

    public function test_con_invitacion_vigente_no_puede_comprar_otra(): void
    {
        $this->mockCheckout();

        $this->post(route('checkout.process-identity'), [
            'template_id' => $this->event->template_id,
            'email' => $this->organizer->email,
        ])->assertSessionHasErrors('email');
    }

    public function test_al_vencer_puede_comprar_otra(): void
    {
        $this->mockCheckout();
        // Vencida aunque el comando aún no la haya deshabilitado.
        $this->expire($this->event);

        $this->post(route('checkout.process-identity'), [
            'template_id' => $this->event->template_id,
            'email' => $this->organizer->email,
        ])->assertRedirect('https://checkout.test/pago');
    }

    public function test_la_nueva_compra_es_la_que_se_muestra(): void
    {
        $this->expire($this->event);

        $this->fulfill($this->organizer->email);

        $newEvent = $this->organizer->fresh()->currentEvent();
        $this->assertNotSame($this->event->id, $newEvent->id);

        $this->actingAs($this->organizer)
            ->get(route('events.info'))
            ->assertRedirect(route('events.wizard.edit', ['event' => $newEvent->slug]));
    }

    public function test_un_coadministrador_que_compra_se_vuelve_organizador(): void
    {
        $coadmin = User::factory()->create(['email' => 'elena@email.com']);
        app(CoadminService::class)->accept(
            $this->event->coadmins()->create(['email' => 'elena@email.com']),
            $coadmin,
        );

        $this->fulfill('elena@email.com');

        $coadmin = $coadmin->fresh();
        $this->assertTrue($coadmin->hasRole('organizer'));
        $this->assertTrue($coadmin->hasRole(EventCoadmin::ROLE));
        $this->assertNotNull($coadmin->currentEvent());

        // Su menú de organizador conserva el acceso a las bodas compartidas.
        $this->actingAs($coadmin)->get(route('organizer.guests.index'))
            ->assertOk()
            ->assertSee('href="' . route('shared-events.index') . '"', false);
    }

    /* ---------------------------------------------------------------------
     | Invitaciones compartidas vencidas
     * -------------------------------------------------------------------*/

    public function test_compartida_vencida_no_se_edita(): void
    {
        $coadmin = User::factory()->create(['email' => 'elena@email.com']);
        app(CoadminService::class)->accept(
            $this->event->coadmins()->create(['email' => 'elena@email.com']),
            $coadmin,
        );
        $this->expire($this->event);

        $this->actingAs($coadmin)->get(route('shared-events.index'))
            ->assertOk()
            ->assertSee('Vencida')
            ->assertDontSee(route('events.wizard.edit', ['event' => $this->event->slug]));

        $this->actingAs($coadmin)
            ->get(route('events.wizard.edit', ['event' => $this->event->slug, 'step' => 'general']))
            ->assertRedirect(route('shared-events.index'))
            ->assertSessionHas('error');
    }

    /* ---------------------------------------------------------------------
     | Helpers
     * -------------------------------------------------------------------*/

    private function expire(Event $event): void
    {
        $event->update(['event_date' => now()->subDays(30)]);
        $this->assertTrue($event->fresh()->isExpired());
    }

    private function organizerWithEvent(): array
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'organizer'])->id);

        $event = Event::factory()->create([
            'user_id' => $user->id,
            'template_id' => Template::factory()->create(['view_path' => null, 'duration_days' => null])->id,
            'custom_url' => 'pareja-' . uniqid(),
        ]);

        return [$user, $event];
    }

    private function mockCheckout(): void
    {
        $this->mock(OrderService::class, fn ($mock) => $mock
            ->shouldReceive('createStripeCheckoutSession')
            ->andReturn('https://checkout.test/pago'));
    }

    private function fulfill(string $email): void
    {
        app(OrderService::class)->fulfillWebhookOrder(new ProcessWebhookOrderDTO(
            stripeSessionId: 'cs_test_' . uniqid(),
            customerEmail: $email,
            customerName: 'Cliente',
            templateId: $this->event->template_id,
            amountTotal: 499,
            currency: 'mxn',
        ));
    }
}
