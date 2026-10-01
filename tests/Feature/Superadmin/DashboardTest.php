<?php

namespace Tests\Feature\Superadmin;

use App\Models\Event;
use App\Models\Order;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use App\Services\Superadmin\DashboardPeriod;
use App\Services\Superadmin\DashboardService;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Panel del superadmin: las cifras del negocio y lo que pide acción.
 *
 * Lo que se fija aquí son las reglas, no la maqueta: qué cuenta como venta,
 * qué alcanza el filtro de periodo y qué se considera pendiente.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

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

        $this->seed(RolesAndAdminSeeder::class);

        $this->superadmin = User::factory()->create();
        $this->superadmin->roles()->attach(Role::named('superadmin')->id);
    }

    private function service(): DashboardService
    {
        return app(DashboardService::class);
    }

    /**
     * Deja la tabla de órdenes en cero antes de contar.
     *
     * Corriendo la suite completa queda una orden escrita FUERA de la
     * transacción de su prueba —no la deja este archivo, lo verifiqué— y el
     * panel suma todas las del periodo, así que se colaría en las cifras.
     * Estas pruebas parten de una tabla limpia para medir sólo lo suyo.
     */
    private function soloMisOrdenes(): void
    {
        Order::query()->delete();
    }

    /* =====================================================================
     | Acceso
     * ===================================================================*/

    public function test_solo_el_superadmin_entra(): void
    {
        $organizador = User::factory()->create();
        $organizador->roles()->attach(Role::named('organizer')->id);

        $this->actingAs($organizador)->get(route('superadmin.dashboard'))->assertForbidden();

        // Sin sesión tampoco. Esta app responde 403 en vez de mandar al login,
        // que es como se comporta en todas sus rutas protegidas.
        $this->get(route('superadmin.dashboard'))->assertForbidden();
    }

    /**
     * Fija cuándo vence una invitación.
     *
     * No se puede pasar 'expires_at' al crearla: Event recalcula la vigencia a
     * partir de la fecha de la boda cada vez que esa fecha cambia. Aquí se
     * escribe después, directo en la tabla, para que el hook no la pise.
     */
    private function eventoQueVence(?string $cuando, bool $activa = true): Event
    {
        $evento = Event::factory()->create(['is_active' => $activa]);

        Event::withoutEvents(fn () => Event::where('id', $evento->id)->update([
            'expires_at' => $cuando,
        ]));

        return $evento->refresh();
    }

    /* =====================================================================
     | Ventas
     * ===================================================================*/

    public function test_solo_cuentan_como_venta_las_ordenes_pagadas(): void
    {
        $this->soloMisOrdenes();

        Order::factory()->create(['status' => 'completed', 'amount' => 1000]);
        Order::factory()->create(['status' => 'completed', 'amount' => 500]);
        // Ni la que quedó a medias ni la que falló son dinero en la caja.
        Order::factory()->create(['status' => 'pending', 'amount' => 900]);
        Order::factory()->create(['status' => 'failed', 'amount' => 700]);

        $ventas = $this->service()->sales(DashboardPeriod::fromRequest('mes'));

        $this->assertSame(1500.0, $ventas['ingresos']);
        $this->assertSame(2, $ventas['pagadas']);
        $this->assertSame(1, $ventas['pendientes']);
        $this->assertSame(1, $ventas['fallidas']);
        $this->assertSame(750.0, $ventas['ticket']);
    }

    public function test_sin_ventas_el_ticket_promedio_no_revienta(): void
    {
        $this->soloMisOrdenes();

        // Dividir entre cero es el error clásico de un panel recién estrenado.
        $ventas = $this->service()->sales(DashboardPeriod::fromRequest('mes'));

        $this->assertSame(0.0, $ventas['ingresos']);
        $this->assertSame(0.0, $ventas['ticket']);
        $this->assertNull($ventas['variacion'], 'Sin base anterior no hay porcentaje que enseñar.');
    }

    public function test_el_periodo_deja_fuera_lo_que_no_le_toca(): void
    {
        $this->soloMisOrdenes();

        Order::factory()->create(['status' => 'completed', 'amount' => 1000, 'created_at' => now()]);
        Order::factory()->create(['status' => 'completed', 'amount' => 400, 'created_at' => now()->subMonths(3)]);

        $esteMes = $this->service()->sales(DashboardPeriod::fromRequest('mes'));
        $esteAnio = $this->service()->sales(DashboardPeriod::fromRequest('anio'));

        $this->assertSame(1000.0, $esteMes['ingresos']);
        $this->assertSame(1400.0, $esteAnio['ingresos']);

        // El acumulado histórico ignora el filtro a propósito.
        $this->assertSame(1400.0, $esteMes['acumulado']);
    }

    public function test_el_rango_personalizado_se_endereza_si_viene_al_reves(): void
    {
        $periodo = DashboardPeriod::fromRequest('personalizado', '2026-03-31', '2026-03-01');

        $this->assertSame('2026-03-01', $periodo->from->format('Y-m-d'));
        $this->assertSame('2026-03-31', $periodo->to->format('Y-m-d'));
    }

    public function test_un_periodo_inventado_cae_en_el_mes_en_curso(): void
    {
        $periodo = DashboardPeriod::fromRequest('lo-que-sea');

        $this->assertSame(DashboardPeriod::MES, $periodo->key);
    }

    /* =====================================================================
     | Invitaciones
     * ===================================================================*/

    public function test_cuenta_las_invitaciones_por_su_estado(): void
    {
        $this->eventoQueVence(now()->addYear()->toDateTimeString());
        $this->eventoQueVence(now()->addDays(5)->toDateTimeString());
        $this->eventoQueVence(now()->subDay()->toDateTimeString());
        $this->eventoQueVence(now()->addYear()->toDateTimeString(), activa: false);

        $estado = $this->service()->invitations();

        $this->assertSame(4, $estado['total']);
        $this->assertSame(1, $estado['activas']);
        $this->assertSame(1, $estado['por_vencer']);
        $this->assertSame(1, $estado['vencidas']);
        $this->assertSame(1, $estado['suspendidas']);
    }

    /* =====================================================================
     | Pendientes
     * ===================================================================*/

    public function test_avisa_de_las_plantillas_activas_que_no_se_pueden_cobrar(): void
    {
        Template::factory()->create(['name' => 'Boda Sin Precio', 'is_active' => true, 'stripe_price_id' => null]);
        Template::factory()->create(['name' => 'Boda Vendible', 'is_active' => true, 'stripe_price_id' => 'price_123']);

        $titulos = collect($this->service()->pending())->pluck('titulo');
        $this->assertContains('Plantillas activas que no se pueden cobrar', $titulos);

        $grupo = collect($this->service()->pending())->firstWhere('titulo', 'Plantillas activas que no se pueden cobrar');
        $nombres = collect($grupo['filas'])->pluck('texto');

        $this->assertContains('Boda Sin Precio', $nombres);
        $this->assertNotContains('Boda Vendible', $nombres);
    }

    public function test_un_pendiente_sin_filas_no_aparece(): void
    {
        // Nada que resolver: el panel no debe enseñar grupos vacíos.
        Template::factory()->create(['is_active' => true, 'stripe_price_id' => 'price_123']);

        $this->assertSame([], $this->service()->pending());
    }

    /* =====================================================================
     | La pantalla
     * ===================================================================*/

    public function test_la_pantalla_pinta_las_cifras(): void
    {
        $this->soloMisOrdenes();

        Order::factory()->create(['status' => 'completed', 'amount' => 1200]);
        $this->eventoQueVence(now()->addYear()->toDateTimeString());

        $this->actingAs($this->superadmin)
            ->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Ventas')
            ->assertSee('$1,200.00 MXN')
            ->assertSee('Invitaciones')
            ->assertSee('Pide acción')
            ->assertSee('Actividad reciente')
            // El periodo por omisión es el mes en curso.
            ->assertSee('Este mes');
    }

    public function test_la_pantalla_respeta_el_rango_personalizado(): void
    {
        $this->soloMisOrdenes();

        Order::factory()->create(['status' => 'completed', 'amount' => 1200, 'created_at' => now()->subMonths(6)]);

        $html = $this->actingAs($this->superadmin)
            ->get(route('superadmin.dashboard', [
                'periodo' => 'personalizado',
                'desde' => now()->subDays(7)->format('Y-m-d'),
                'hasta' => now()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->getContent();

        // La venta de hace medio año queda fuera del rango: los ingresos van en
        // cero. El acumulado histórico sí la incluye, porque ignora el filtro.
        $this->assertStringContainsString('$0.00 MXN', $html);
        $this->assertStringContainsString('$1,200.00 MXN', $html);
        $this->assertStringContainsString('Acumulado histórico', $html);
    }
}
