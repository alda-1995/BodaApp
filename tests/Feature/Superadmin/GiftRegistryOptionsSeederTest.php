<?php

namespace Tests\Feature\Superadmin;

use App\Models\SystemSection;
use App\Templates\Sections\Catalog\GiftRegistrySection;
use Database\Seeders\GiftRegistryOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * El catálogo de mesa de regalos que deja sembrado el seeder.
 *
 * Es configuración del producto, no de una boda: si se rehace la base sin él,
 * el paso "Mesa de regalos" del wizard queda vacío y la invitación no tiene qué
 * pintar. Aquí se fija que lo que siembra siga sirviendo para eso.
 */
class GiftRegistryOptionsSeederTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(GiftRegistryOptionsSeeder::class);
    }

    public function test_siembra_el_catalogo_como_lo_espera_el_wizard(): void
    {
        $section = SystemSection::where('key', 'registries')->sole();

        $this->assertTrue($section->is_global);
        $this->assertTrue($section->is_active);
        $this->assertSame('repeater', $section->type);
        $this->assertSame('gift_registry', $section->parent);
    }

    public function test_los_tres_tipos_de_mesa_con_sus_datos(): void
    {
        $schema = SystemSection::where('key', 'registries')->sole()->schema;

        // El selector de tipo va primero: de él dependen todos los demás.
        $this->assertSame('type', $schema[0]['key']);
        $this->assertSame(
            ['cuenta_bancaria', 'liverpool', 'amazon'],
            array_column($schema[0]['options'], 'value'),
        );

        // Y cada tipo trae sus datos, en el orden en que se preguntan.
        $porTipo = [];

        foreach (array_slice($schema, 1) as $campo) {
            $porTipo[$campo['depends_on']['values'][0]][] = $campo['key'];
        }

        $this->assertSame([
            'cuenta_bancaria' => ['banco', 'nombre_del_novio', 'clabe_bbva'],
            'liverpool' => ['nombre_de_la_tienda', 'numero_de_mesa', 'url'],
            'amazon' => ['nombre_de_la_tienda', 'url_de_la_lista_de_regalos', 'texto_del_boton'],
        ], $porTipo);
    }

    public function test_la_tienda_repetida_se_pregunta_una_sola_vez(): void
    {
        /*
        | 'nombre_de_la_tienda' está declarada dos veces —Liverpool y Amazon la
        | piden— y eso es correcto en el catálogo. Lo que no puede pasar es que
        | el organizador vea dos campos iguales: el compilador los funde en uno
        | cuyo required_if nombra a los dos tipos.
        */
        $campos = app(GiftRegistrySection::class)->fields();
        $reglas = $campos['registries']->toValidationRules('');

        $tienda = implode('|', (array) $reglas['registries.*.nombre_de_la_tienda']);

        $this->assertStringContainsString('liverpool', $tienda);
        $this->assertStringContainsString('amazon', $tienda);
    }

    public function test_correrlo_dos_veces_no_duplica_ni_pisa_lo_configurado(): void
    {
        // El superadmin cambió una etiqueta desde el panel.
        $section = SystemSection::where('key', 'registries')->sole();
        $section->update(['title' => 'Mis opciones de regalo']);

        $this->seed(GiftRegistryOptionsSeeder::class);

        $this->assertSame(1, SystemSection::where('key', 'registries')->count());
        $this->assertSame('Mis opciones de regalo', SystemSection::where('key', 'registries')->sole()->title);
    }
}
