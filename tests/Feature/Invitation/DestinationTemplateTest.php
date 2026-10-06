<?php

namespace Tests\Feature\Invitation;

use App\Models\SystemSection;
use App\Models\Template;
use App\Services\Template\TemplateDiscoveryService;
use App\Strategies\DestinationWeddingStrategy;
use App\Templates\BlockType;
use App\Templates\Sections\Catalog\DestinationSection;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Plantilla "Boda Destino": la boda contada como un viaje.
 */
class DestinationTemplateTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW = 'build-templates.template-destino.index';

    /** Seguro: RefreshDatabase vacía la BD; aborta si no es una BD *_test. */
    protected function beforeRefreshingDatabase()
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException("Abortado: RefreshDatabase iba a vaciar '{$database}'.");
        }
    }

    /* ---------------------------------------------------------------------
     | Estrategia y pasos
     * -------------------------------------------------------------------*/

    public function test_su_vista_resuelve_su_estrategia(): void
    {
        $estrategia = app(TemplateDiscoveryService::class)->resolveStrategy(self::VIEW);

        $this->assertInstanceOf(DestinationWeddingStrategy::class, $estrategia);
        $this->assertSame('Boda Destino', $estrategia->getName());
        $this->assertSame('td', $estrategia->bodyClass());
    }

    public function test_el_orden_de_los_pasos_lo_declara_su_estrategia(): void
    {
        $pasos = array_map(
            fn ($seccion) => $seccion->key(),
            app(DestinationWeddingStrategy::class)->sections(),
        );

        $this->assertSame([
            'general',
            'destination',
            'milestones',
            'itinerary',
            'hotels',
            'dress_code',
            'gift_registry',
            'gallery',
            'rsvp',
            'faqs',
        ], $pasos);
    }

    public function test_el_paso_de_destino_pregunta_lo_suyo_y_no_la_fecha(): void
    {
        $destino = collect(app(DestinationWeddingStrategy::class)->sections())
            ->first(fn ($seccion) => $seccion->key() === 'destination');

        $campos = array_keys($destino->fields());

        $this->assertSame(['eyebrow', 'city', 'origin_code', 'destination_code'], $campos);

        /*
        | La cuenta regresiva sale de la fecha de la boda, que ya se capturó en
        | información general. Si este paso la volviera a pedir, las dos podrían
        | contradecirse.
        */
        $this->assertNotContains('date_event_person', $campos);
        $this->assertNotContains('event_date', $campos);
    }

    /* ---------------------------------------------------------------------
     | El bloque nuevo del catálogo
     * -------------------------------------------------------------------*/

    public function test_la_cuenta_regresiva_sale_de_la_fecha_de_la_boda(): void
    {
        $boda = Carbon::parse('2030-05-20 17:00:00');

        $datos = (new DestinationSection())->data(
            ['city' => 'Mérida'],
            ['general' => ['date_event_person' => $boda->toDateTimeString()]],
        );

        $this->assertTrue($boda->equalTo($datos['event_date']));
    }

    public function test_sin_fecha_legible_el_bloque_se_pinta_sin_cuenta(): void
    {
        $seccion = new DestinationSection();

        $this->assertNull($seccion->data(['city' => 'Mérida'], [])['event_date']);
        $this->assertNull(
            $seccion->data(['city' => 'Mérida'], ['general' => ['date_event_person' => 'no es fecha']])['event_date'],
        );
    }

    /**
     * Las versales son del diseño y las pone el CSS. Aquí se guarda lo que se
     * escribió, porque en la ruta cabe tanto un código de aeropuerto como el
     * nombre de una ciudad, y ése no se grita.
     */
    public function test_la_ruta_se_guarda_como_se_escribio(): void
    {
        $datos = (new DestinationSection())->data(
            ['city' => 'Mérida', 'origin_code' => ' Ciudad de México ', 'destination_code' => 'Mérida'],
            [],
        );

        $this->assertSame('Ciudad de México', $datos['origin_code']);
        $this->assertSame('Mérida', $datos['destination_code']);
        $this->assertSame('Ciudad de México → Mérida', $datos['route']);
    }

    public function test_en_la_ruta_cabe_mas_que_un_codigo_de_tres_letras(): void
    {
        $campos = collect(app(DestinationWeddingStrategy::class)->sections())
            ->first(fn ($seccion) => $seccion->key() === 'destination')
            ->fields();

        foreach (['origin_code', 'destination_code'] as $campo) {
            $reglas = $campos[$campo]->getRules();

            // Un aeropuerto son tres letras, pero "Ciudad de México" son 16.
            $this->assertContains('max:40', $reglas, "El campo {$campo} sigue limitado de más.");
        }
    }

    public function test_media_ruta_no_se_dibuja(): void
    {
        $seccion = new DestinationSection();

        // Una boda a la que se llega en coche no tiene vuelo que anunciar.
        $this->assertNull($seccion->data(['city' => 'Mérida', 'origin_code' => 'MEX'], [])['route']);
        $this->assertNull($seccion->data(['city' => 'Mérida'], [])['route']);
    }

    public function test_sin_ciudad_el_bloque_no_se_pinta(): void
    {
        $seccion = new DestinationSection();

        $this->assertFalse($seccion->isVisible($seccion->data([], [])));
        $this->assertTrue($seccion->isVisible($seccion->data(['city' => 'Mérida'], [])));
    }

    public function test_su_tipo_de_bloque_esta_en_el_catalogo(): void
    {
        $this->assertTrue(BlockType::exists(BlockType::DESTINATION));
        $this->assertSame(BlockType::DESTINATION, (new DestinationSection())->blockType());
    }

    /* ---------------------------------------------------------------------
     | La página
     * -------------------------------------------------------------------*/

    /**
     * El catálogo de mesa de regalos que configura el superadmin.
     *
     * La sección no inventa opciones: las lee de aquí, y de aquí sale también
     * lo que se ve en la vista previa.
     */
    private function seedRegistryCatalog(): void
    {
        SystemSection::create([
            'key' => 'registries',
            'title' => 'Opciones de regalo',
            'type' => 'repeater',
            'is_global' => true,
            'is_active' => true,
            'schema' => [
                [
                    'key' => 'type',
                    'type' => 'select',
                    'label' => 'Tipo',
                    'options' => [
                        ['value' => 'tienda', 'label' => 'Tienda departamental'],
                        ['value' => 'cuenta_bancaria', 'label' => 'Transferencia'],
                    ],
                ],
                [
                    'key' => 'tienda',
                    'type' => 'text',
                    'label' => 'Tienda',
                    'placeholder' => 'Ej. Liverpool',
                    'depends_on' => ['field' => 'type', 'values' => ['tienda']],
                ],
                [
                    'key' => 'liga',
                    'type' => 'url',
                    'label' => 'Liga de la mesa',
                    'placeholder' => 'Ej. https://mesaderegalos.liverpool.com.mx/',
                    'depends_on' => ['field' => 'type', 'values' => ['tienda']],
                ],
                [
                    'key' => 'banco',
                    'type' => 'text',
                    'label' => 'Banco',
                    'placeholder' => 'Ej. BBVA',
                    'depends_on' => ['field' => 'type', 'values' => ['cuenta_bancaria']],
                ],
                [
                    'key' => 'clabe',
                    'type' => 'number',
                    'label' => 'CLABE',
                    'placeholder' => 'Ej. 567836773893834234',
                    'depends_on' => ['field' => 'type', 'values' => ['cuenta_bancaria']],
                ],
            ],
        ]);
    }

    public function test_la_vista_previa_pinta_todas_sus_secciones(): void
    {
        // La mesa de regalos no inventa opciones: sin el catálogo del superadmin
        // su bloque no tiene nada que enseñar y, con razón, no se pinta.
        $this->seedRegistryCatalog();

        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        // Un bloque por sección, cada uno con su id de ancla.
        foreach ([
            'portada', 'destino', 'historia', 'itinerario', 'hospedaje',
            'vestimenta', 'mesa-de-regalos', 'galeria', 'confirmacion', 'preguntas',
        ] as $ancla) {
            $this->assertStringContainsString('id="' . $ancla . '"', $html, "Falta el bloque #{$ancla}.");
        }

        // Y el pie, que no es un paso sino la firma de la plantilla.
        $this->assertStringContainsString('td-footer', $html);
    }

    public function test_usa_sus_propios_estilos_y_sus_propias_fuentes(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        $this->assertStringContainsString('family=Bricolage+Grotesque', $html);
        $this->assertStringContainsString('DM+Sans', $html);
        // La itálica se pide explícitamente: el diseño la usa.
        $this->assertStringContainsString('ital,', $html);

        // Y no arrastra el CSS ni el JS de las otras plantillas.
        $this->assertStringNotContainsString('templates/template-editorial/', $html);
        $this->assertStringNotContainsString('templates/template-travel/', $html);
    }

    /**
     * El contador avanza en el navegador, pero los números los calcula el
     * servidor: así quien llega sin JS ve la cuenta correcta y no ceros.
     */
    public function test_el_contador_llega_pintado_con_sus_cuatro_cifras(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        foreach (['days', 'hours', 'minutes', 'seconds'] as $unidad) {
            // Dos cifras ya escritas, no un hueco que rellene el JS.
            $this->assertMatchesRegularExpression(
                '/data-countdown-' . $unidad . '>\d{2}</',
                $html,
                "El contador no trae los {$unidad} calculados.",
            );
        }

        // Y la fecha, que es con lo que el navegador sigue contando.
        $this->assertMatchesRegularExpression('/data-fecha="\d{4}-\d{2}-\d{2}T/', $html);
    }

    public function test_la_portada_escribe_la_fecha_como_un_boleto(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        // "24 — 10 — 2026" en la portada y "24 - 10 - 2026" en el pie.
        $this->assertMatchesRegularExpression('/td-banner__date">\s*\d{2} — \d{2} — \d{4}/', $html);
        $this->assertMatchesRegularExpression('/td-footer__date">\s*\d{2} - \d{2} - \d{4}/', $html);
    }
}
