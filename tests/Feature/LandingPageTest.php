<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * La página principal: lo primero que ve alguien que todavía no es cliente.
 *
 * Lo que se fija aquí es que venda lo que de verdad hay —el catálogo sale de
 * la base, no escrito en la vista— y que las dos puertas de entrada estén
 * donde deben: iniciar sesión para quien no la tiene, el panel para quien sí.
 */
class LandingPageTest extends TestCase
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

    public function test_la_portada_abre_sin_sesion(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Tu boda entera en una sola liga')
            ->assertSee('Invitaciones digitales de boda');
    }

    /* ---------------------------------------------------------------------
     | El catálogo sale de la base
     * -------------------------------------------------------------------*/

    public function test_lista_las_plantillas_activas_con_su_precio_y_sus_dos_acciones(): void
    {
        $template = Template::factory()->create([
            'name' => 'Boda Destino',
            'slug' => 'boda-destino',
            'price' => 1499,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $html = $this->get(route('home'))->assertOk();

        $html->assertSee('Boda Destino')
            ->assertSee('$1,499')
            // La vigencia es un argumento de venta, y es la que diga el panel.
            ->assertSee('30 días')
            // Ver antes de comprar, y comprar.
            ->assertSee(route('templates.preview', $template->slug))
            ->assertSee(route('checkout.checkout-preview', $template->slug));
    }

    public function test_una_plantilla_apagada_no_se_ofrece(): void
    {
        Template::factory()->create(['name' => 'Borrador interno', 'is_active' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Borrador interno');
    }

    public function test_sin_plantillas_la_pagina_lo_dice_en_vez_de_romperse(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Estamos preparando nuevos diseños.');
    }

    public function test_el_precio_de_arranque_es_el_mas_barato(): void
    {
        Template::factory()->create(['price' => 2400, 'is_active' => true]);
        Template::factory()->create(['price' => 990, 'is_active' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Desde $990 MXN');
    }

    /* ---------------------------------------------------------------------
     | Las dos puertas de entrada
     * -------------------------------------------------------------------*/

    public function test_a_quien_no_tiene_sesion_se_le_ofrece_iniciarla(): void
    {
        $html = $this->get(route('home'))->assertOk();

        $html->assertSee('Iniciar sesión')
            ->assertSee(route('login'))
            // Su panel todavía no existe: ofrecerlo sólo lo mandaría al login.
            ->assertDontSee('Ir a mi panel');
    }

    public function test_a_quien_ya_entro_se_le_ofrece_su_panel(): void
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::firstOrCreate(['name' => 'organizer'])->id);

        $html = $this->actingAs($user)->get(route('home'))->assertOk();

        $html->assertSee('Ir a mi panel')
            ->assertSee(route('panel'))
            // Ya tiene sesión: volver a pedírsela sobra.
            ->assertDontSee('Iniciar sesión');
    }

    public function test_el_menu_lleva_a_todas_las_secciones_de_la_pagina(): void
    {
        $html = $this->get(route('home'))->assertOk()->getContent();

        foreach (['plantillas', 'incluye', 'como-funciona', 'preguntas'] as $ancla) {
            $this->assertStringContainsString('href="#' . $ancla . '"', $html, "El menú no lleva a #{$ancla}.");
            $this->assertStringContainsString('id="' . $ancla . '"', $html, "Falta la sección #{$ancla}.");
        }
    }
}
