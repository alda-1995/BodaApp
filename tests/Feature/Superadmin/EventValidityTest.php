<?php

namespace Tests\Feature\Superadmin;

use App\Models\Event;
use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Database\Seeders\RolesAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Vigencia de una invitación, ajustada a mano por el superadmin.
 *
 * Normalmente se calcula sola (fecha de la boda + días de la plantilla). Esta
 * pantalla es para cuando hay que intervenir: una boda pospuesta, una cortesía,
 * o cerrar una antes de tiempo.
 */
class EventValidityTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
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

        $this->seed(RolesAndAdminSeeder::class);

        $this->superadmin = User::factory()->create();
        $this->superadmin->roles()->attach(Role::named('superadmin')->id);

        $this->organizer = User::factory()->create();
        $this->organizer->roles()->attach(Role::named('organizer')->id);

        $this->event = Event::factory()->create([
            'user_id' => $this->organizer->id,
            // Con vista de verdad: hay tests que abren la invitación pública.
            'template_id' => Template::factory()->create([
                'view_path' => 'build-templates.template-editorial.index',
                'duration_days' => 21,
            ])->id,
            'custom_url' => 'ana-y-luis',
            'event_date' => now()->addMonths(2),
            'is_active' => true,
        ]);
    }

    /* ---------------------------------------------------------------------
     | Quién entra
     * -------------------------------------------------------------------*/

    public function test_un_organizador_no_entra(): void
    {
        $this->actingAs($this->organizer)
            ->get(route('superadmin.events.validity.edit', $this->event->id))
            ->assertForbidden();
    }

    public function test_quien_no_inicio_sesion_no_entra(): void
    {
        $this->get(route('superadmin.events.validity.edit', $this->event->id))
            ->assertRedirect(route('login'));
    }

    public function test_la_lista_de_usuarios_lleva_a_la_vigencia(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Vigencia')
            ->assertSee(route('superadmin.events.validity.edit', $this->event->id));
    }

    /* ---------------------------------------------------------------------
     | La pantalla
     * -------------------------------------------------------------------*/

    public function test_la_pantalla_muestra_de_donde_sale_la_vigencia(): void
    {
        $this->actingAs($this->superadmin)
            ->get(route('superadmin.events.validity.edit', $this->event->id))
            ->assertOk()
            ->assertSee('Vigencia de la invitación')
            // Lo que hoy la determina, para no cambiarla a ciegas.
            ->assertSee('21 días')
            ->assertSee('ana-y-luis')
            ->assertSee('name="expires_at"', false)
            ->assertSee('name="is_active"', false);
    }

    /* ---------------------------------------------------------------------
     | Lo que hace
     * -------------------------------------------------------------------*/

    public function test_vencerla_a_mano_la_cierra_para_todos(): void
    {
        $this->guardar(['expires_at' => now()->subDay()->format('Y-m-d\TH:i')])
            ->assertSessionHasNoErrors();

        $event = $this->event->fresh();

        $this->assertTrue($event->isExpired());
        $this->assertFalse($event->isAvailable());

        // Y la invitación pública deja de servir, sin esperar al comando.
        $this->get('/invitacion/ana-y-luis')->assertStatus(410);
    }

    public function test_extender_la_vigencia_la_devuelve_a_la_vida(): void
    {
        // Como la dejaría el comando por horas tras vencer.
        $this->event->forceFill(['expires_at' => now()->subWeek(), 'is_active' => false])->save();

        $this->guardar([
            'expires_at' => now()->addMonths(3)->format('Y-m-d\TH:i'),
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $event = $this->event->fresh();

        $this->assertFalse($event->isExpired());
        $this->assertTrue($event->isAvailable());
        $this->get('/invitacion/ana-y-luis')->assertOk();
    }

    /**
     * Extender la fecha de una apagada no la revive: son dos cosas distintas y
     * por eso la pantalla las edita juntas.
     */
    public function test_extender_sin_encenderla_la_deja_apagada(): void
    {
        $this->event->forceFill(['is_active' => false])->save();

        $this->guardar([
            'expires_at' => now()->addMonths(3)->format('Y-m-d\TH:i'),
            'is_active' => null,
        ])->assertSessionHasNoErrors();

        $event = $this->event->fresh();

        $this->assertFalse($event->isExpired());
        $this->assertFalse($event->isAvailable());
    }

    /**
     * Apagarla a mano no es vencerla: su fecha sigue por delante, así que la
     * página pública no puede decir "estuvo activa hasta" una fecha futura.
     */
    public function test_apagada_a_mano_la_pagina_publica_no_inventa_una_fecha(): void
    {
        $this->guardar([
            'expires_at' => now()->addMonths(3)->format('Y-m-d\TH:i'),
            'is_active' => null,
        ])->assertSessionHasNoErrors();

        $this->get('/invitacion/ana-y-luis')
            ->assertStatus(410)
            ->assertSee('Esta invitación ya no está disponible')
            ->assertDontSee('Estuvo activa hasta');
    }

    public function test_sin_fecha_de_vencimiento_no_vence(): void
    {
        $this->guardar(['expires_at' => ''])->assertSessionHasNoErrors();

        $event = $this->event->fresh();

        $this->assertNull($event->expires_at);
        $this->assertFalse($event->isExpired());
    }

    /**
     * Lo que se escribe aquí manda sobre el cálculo automático, que sólo corre
     * cuando cambia la fecha de la boda.
     */
    public function test_la_fecha_puesta_a_mano_no_la_recalcula_el_modelo(): void
    {
        $aMano = now()->addYear()->startOfMinute();

        $this->guardar(['expires_at' => $aMano->format('Y-m-d\TH:i')])->assertSessionHasNoErrors();

        $this->assertSame(
            $aMano->format('Y-m-d H:i'),
            $this->event->fresh()->expires_at->format('Y-m-d H:i'),
        );
    }

    public function test_una_fecha_que_no_es_fecha_no_pasa(): void
    {
        $this->guardar(['expires_at' => 'el jueves'])->assertSessionHasErrors('expires_at');
    }

    /* ------------------------------------------------------------------ */

    private function guardar(array $overrides = [])
    {
        return $this->actingAs($this->superadmin)
            ->from(route('superadmin.events.validity.edit', $this->event->id))
            ->put(route('superadmin.events.validity.update', $this->event->id), array_merge([
                'expires_at' => now()->addMonths(2)->format('Y-m-d\TH:i'),
                'is_active' => '1',
            ], $overrides));
    }
}
