<?php

namespace Tests\Feature\Invitation;

use App\Models\SystemSection;
use App\Models\Template;
use App\Services\Template\TemplateDiscoveryService;
use App\Strategies\DestinationWeddingStrategy;
use App\Templates\BlockType;
use App\Templates\Sections\Catalog\DestinationSection;
use App\Templates\Sections\Catalog\ItinerarySection;
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

    /* ---------------------------------------------------------------------
     | Preguntas frecuentes
     * -------------------------------------------------------------------*/

    public function test_las_preguntas_piden_su_foto_y_el_texto_del_panel(): void
    {
        $secciones = collect(app(DestinationWeddingStrategy::class)->sections());

        $faqs = $secciones->first(fn ($seccion) => $seccion->key() === 'faqs');
        $this->assertSame(['image', 'intro', 'faqs'], array_keys($faqs->fields()));

        /*
        | La banda de foto es de este bloque, no del anterior: si el paso de
        | confirmación siguiera pidiéndola, se vería dos veces.
        */
        $rsvp = $secciones->first(fn ($seccion) => $seccion->key() === 'rsvp');
        $this->assertNotContains('background_image', array_keys($rsvp->fields()));
    }

    /**
     * Las dos columnas no se mezclan: la respuesta se despliega bajo su propia
     * pregunta, y el panel derecho es sólo del texto de la sección.
     */
    public function test_la_respuesta_va_bajo_su_pregunta_y_el_panel_es_del_texto(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        $this->assertStringContainsString('td-faq__photo', $html);
        $this->assertStringContainsString('td-faq__intro', $html);

        // Cada respuesta vive dentro del <li> de su pregunta, en la columna
        // izquierda; el panel derecho sólo lleva el texto.
        $this->assertMatchesRegularExpression(
            '/td-faq__panel[^>]*>\s*(?:<!--.*?-->\s*)*<p class="td-faq__intro"/s',
            $html,
            'El panel derecho debería llevar sólo el texto de la sección.',
        );
        $this->assertSame(
            substr_count($html, 'td-faq__item'),
            substr_count($html, 'td-faq__answer"'),
            'Cada pregunta debe tener su respuesta dentro de su propia fila.',
        );

        // Ninguna abierta de entrada.
        $this->assertStringContainsString('x-data="{ abierta: null }"', $html);

        // Y la banda ya no se pinta desde la confirmación.
        $this->assertStringNotContainsString('td-rsvp__photo', $html);
    }

    /* ---------------------------------------------------------------------
     | Itinerario
     * -------------------------------------------------------------------*/

    /**
     * El paso sólo pide la foto y los momentos.
     *
     * El clima y la hora local vienen rotulados en la propia foto, y el título
     * de la sección sobra: cada momento se nombra solo.
     */
    public function test_el_itinerario_solo_pide_la_foto_y_los_momentos(): void
    {
        $itinerario = collect(app(DestinationWeddingStrategy::class)->sections())
            ->first(fn ($seccion) => $seccion->key() === 'itinerary');

        $this->assertSame(['event_photo', 'events'], array_keys($itinerario->fields()));
    }

    public function test_cada_momento_del_itinerario_lleva_su_propio_titulo(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        // Tres momentos de ejemplo, tres títulos: no uno solo para todos.
        $this->assertSame(3, substr_count($html, 'td-timeline__title'));
        $this->assertStringContainsString('Ceremonia religiosa', $html);
        $this->assertStringContainsString('Ceremonia civil', $html);

        // Y nada del clima ni de la hora local en la página.
        $this->assertStringNotContainsString('td-timeline__weather', $html);
        $this->assertStringNotContainsString('td-timeline__zone', $html);
    }

    /**
     * Las filas se pintan como el organizador las dejó en el wizard, no
     * reordenadas por hora.
     */
    public function test_el_itinerario_respeta_el_orden_del_wizard(): void
    {
        $momentos = (new ItinerarySection())->data([
            'events' => [
                ['name' => 'Recepción', 'place_event' => 'Salón', 'date_event' => '2027-05-20 20:00:00'],
                ['name' => 'Ceremonia', 'place_event' => 'Capilla', 'date_event' => '2027-05-20 17:30:00'],
            ],
        ], [])['moments'];

        $this->assertSame(['Recepción', 'Ceremonia'], array_column($momentos, 'name'));
    }

    /**
     * La tira de "Nuestra historia" da vueltas, y para eso lleva la lista dos
     * veces: la copia es lo que hace que al terminar empiece otra vez sin un
     * salto visible.
     */
    public function test_la_historia_lleva_la_lista_dos_veces_para_dar_la_vuelta(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        $this->assertStringContainsString('data-td-story-original', $html);
        $this->assertStringContainsString('data-td-story-copia', $html);

        // Cinco años en el ejemplo, diez tarjetas pintadas: original y copia.
        $this->assertSame(10, substr_count($html, 'td-story__card'));

        // La copia no se lee dos veces ni se tabula.
        $this->assertStringContainsString('aria-hidden="true" data-td-story-copia', $html);
        $this->assertStringContainsString('tabindex="-1"', $html);
    }

    /**
     * La galería gira igual que la historia, y por lo mismo lleva su lista dos
     * veces: sin la copia, al llegar al final habría un salto.
     */
    public function test_la_galeria_lleva_la_lista_dos_veces_para_dar_la_vuelta(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        $this->assertStringContainsString('data-td-gallery-original', $html);
        $this->assertStringContainsString('aria-hidden="true" data-td-gallery-copia', $html);

        // Seis fotos de ejemplo, doce piezas pintadas: original y copia.
        $this->assertSame(12, substr_count($html, 'td-gallery__item'));

        // Cada foto es un enlace a sí misma, con su posición para la copia.
        $this->assertMatchesRegularExpression('/<a class="td-gallery__link" href="[^"]+\.png"/', $html);
    }

    public function test_cada_foto_de_la_historia_se_puede_abrir(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        // Un enlace de verdad a la propia foto: sin JS abre en el navegador.
        $this->assertMatchesRegularExpression('/<a class="td-story__link" href="[^"]+\.png"/', $html);
        // Y su posición, con la que la copia abre el visor donde toca.
        $this->assertStringContainsString('data-indice="0"', $html);
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
