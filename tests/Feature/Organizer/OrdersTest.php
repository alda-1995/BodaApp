<?php

namespace Tests\Feature\Organizer;

use App\Models\Event;
use App\Models\Order;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Historial de pagos del organizador, al que se llega desde Configuración.
 */
class OrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $organizer;

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

        $this->organizer = $this->makeOrganizer();
    }

    /* ---------------------------------------------------------------------
     | Cómo se llega
     * -------------------------------------------------------------------*/

    public function test_configuracion_lleva_a_los_pagos(): void
    {
        // La pantalla sólo existe si se puede llegar a ella.
        $this->event($this->organizer);

        $this->actingAs($this->organizer)
            ->get(route('organizer.settings.index'))
            ->assertOk()
            ->assertSee('Ver mis pagos')
            ->assertSee(route('organizer.orders.index'));
    }

    public function test_quien_no_inicio_sesion_no_entra(): void
    {
        $this->get(route('organizer.orders.index'))->assertRedirect(route('login'));
    }

    /* ---------------------------------------------------------------------
     | La lista
     * -------------------------------------------------------------------*/

    public function test_la_lista_muestra_lo_que_compro(): void
    {
        $order = $this->order($this->organizer, 'Boda Destino', 1499, 'completed');

        $this->actingAs($this->organizer)
            ->get(route('organizer.orders.index'))
            ->assertOk()
            ->assertSee('Boda Destino')
            ->assertSee('$1,499.00')
            ->assertSee('Pagada')
            ->assertSee(route('organizer.orders.show', $order->id));
    }

    public function test_cada_estado_se_dice_en_cristiano(): void
    {
        // Los cinco que admite la columna; no hay más que una orden pueda ser.
        foreach ([
            'completed' => 'Pagada',
            'paid' => 'Pagada',
            'pending' => 'Pendiente',
            'failed' => 'Rechazada',
            'refunded' => 'Reembolsada',
        ] as $status => $texto) {
            $usuario = $this->makeOrganizer();
            $this->order($usuario, 'Boda Destino', 990, $status);

            $this->actingAs($usuario)
                ->get(route('organizer.orders.index'))
                ->assertOk()
                ->assertSee($texto);
        }
    }

    public function test_sin_pagos_lo_dice_en_vez_de_romperse(): void
    {
        $this->actingAs($this->organizer)
            ->get(route('organizer.orders.index'))
            ->assertOk()
            ->assertSee('Todavía no tienes pagos registrados.');
    }

    public function test_solo_se_ven_los_pagos_propios(): void
    {
        $this->order($this->organizer, 'Boda Destino', 1499, 'completed');

        $ajeno = $this->makeOrganizer();
        $this->order($ajeno, 'Boda Ajena', 2500, 'completed');

        $this->actingAs($this->organizer)
            ->get(route('organizer.orders.index'))
            ->assertOk()
            ->assertSee('Boda Destino')
            ->assertDontSee('Boda Ajena');
    }

    /* ---------------------------------------------------------------------
     | El detalle
     * -------------------------------------------------------------------*/

    public function test_el_detalle_trae_la_referencia_para_soporte(): void
    {
        $order = $this->order($this->organizer, 'Boda Destino', 1499, 'completed');

        $this->actingAs($this->organizer)
            ->get(route('organizer.orders.show', $order->id))
            ->assertOk()
            ->assertSee('Boda Destino')
            ->assertSee('$1,499.00')
            ->assertSee($order->stripe_session_id);
    }

    public function test_nadie_puede_ver_el_pago_de_otro(): void
    {
        $ajeno = $this->makeOrganizer();
        $order = $this->order($ajeno, 'Boda Ajena', 2500, 'completed');

        // 404 y no 403: ni siquiera se le confirma que esa orden existe.
        $this->actingAs($this->organizer)
            ->get(route('organizer.orders.show', $order->id))
            ->assertNotFound();
    }

    /**
     * Cuando la invitación vence es justo cuando alguien revisa qué pagó, así
     * que su historial no puede quedarse detrás de 'event.active'.
     */
    public function test_los_pagos_se_siguen_viendo_con_la_invitacion_vencida(): void
    {
        $event = $this->event($this->organizer);
        $event->update(['is_active' => false, 'event_date' => now()->subYear()]);

        $this->order($this->organizer, 'Boda Destino', 1499, 'completed');

        $this->actingAs($this->organizer)
            ->get(route('organizer.orders.index'))
            ->assertOk()
            ->assertSee('Boda Destino');
    }

    /* ------------------------------------------------------------------ */

    private function makeOrganizer(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'organizer'])->id);

        return $user;
    }

    private function event(User $user): Event
    {
        return Event::factory()->create([
            'user_id' => $user->id,
            'template_id' => Template::factory()->create(['view_path' => null])->id,
            'custom_url' => 'boda-' . $user->id,
            'is_active' => true,
            'event_date' => now()->addMonths(4),
        ]);
    }

    private function order(User $user, string $plantilla, float $monto, string $status): Order
    {
        return Order::factory()->create([
            'user_id' => $user->id,
            'template_id' => Template::factory()->create(['name' => $plantilla, 'view_path' => null])->id,
            'amount' => $monto,
            'currency' => 'mxn',
            'status' => $status,
        ]);
    }
}
