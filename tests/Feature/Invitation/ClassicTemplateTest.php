<?php

namespace Tests\Feature\Invitation;

use App\Models\Template;
use App\Services\Template\TemplateDiscoveryService;
use App\Strategies\ClassicWeddingStrategy;
use App\Templates\BlockType;
use App\Templates\SectionView;
use App\Templates\Sections\Catalog\ItinerarySection;
use App\Templates\TemplateContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Plantilla "Boda Clásica": serifa, mucho aire y una sola banda crema.
 */
class ClassicTemplateTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW = 'build-templates.template-clasica.index';

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

        $this->assertInstanceOf(ClassicWeddingStrategy::class, $estrategia);
        $this->assertSame('Boda Clásica', $estrategia->getName());
        $this->assertSame('tc', $estrategia->bodyClass());
    }

    public function test_el_orden_de_los_pasos_lo_declara_su_estrategia(): void
    {
        $pasos = array_map(
            fn ($seccion) => $seccion->key(),
            app(ClassicWeddingStrategy::class)->sections(),
        );

        $this->assertSame([
            'general',
            'itinerary',
            'gallery',
            'dress_code',
            'gift_registry',
            'countdown',
            'rsvp',
            'faqs',
        ], $pasos);
    }

    /**
     * La cuenta regresiva tiene su propio paso, pero ahí sólo se piden fotos:
     * cuánto falta sale de la fecha de la boda y no se pregunta en ningún lado.
     */
    public function test_el_paso_de_la_cuenta_solo_pide_fotos(): void
    {
        $campos = array_keys($this->seccion('countdown')->fields());

        $this->assertSame(['photos'], $campos);
    }

    /* ---------------------------------------------------------------------
     | Lo que pide cada paso
     * -------------------------------------------------------------------*/

    public function test_la_portada_pide_su_linea_y_sus_dos_fotos(): void
    {
        $campos = array_keys($this->seccion('general')->fields());

        $this->assertContains('invitation_line', $campos);
        $this->assertContains('cover_photo', $campos);
        $this->assertContains('band_photo', $campos);
        // Esta portada no pinta a los padres.
        $this->assertNotContains('family_parents', $campos);
    }

    /**
     * Cada momento lleva su propia foto. Es un campo del repetidor, no de la
     * sección: una boda con tres momentos sube tres fotos.
     */
    public function test_cada_momento_del_itinerario_pide_su_foto(): void
    {
        $repetidor = $this->seccion('itinerary')->fields()['events'];

        $subcampos = array_map(fn ($campo) => $campo->getName(), $repetidor->getSchema());

        $this->assertContains('photo', $subcampos);
        $this->assertContains('location_maps', $subcampos, 'El diseño pone "Ver en mapa" en cada momento.');
    }

    /** La foto del momento es del repetidor y las demás plantillas no la piden. */
    public function test_las_otras_plantillas_no_heredan_esa_foto(): void
    {
        $subcampos = array_map(
            fn ($campo) => $campo->getName(),
            (new ItinerarySection())->fields()['events']->getSchema(),
        );

        $this->assertNotContains('photo', $subcampos);
    }

    public function test_la_galeria_pide_sus_fotos_y_el_texto_que_las_acompana(): void
    {
        $campos = array_keys($this->seccion('gallery')->fields());

        $this->assertContains('photos', $campos);
        $this->assertContains('intro', $campos);
        // El collage no deja sitio para pedirle fotos a los invitados.
        $this->assertNotContains('guest_photos_message', $campos);
    }

    /* ---------------------------------------------------------------------
     | Lo que se pinta
     * -------------------------------------------------------------------*/

    public function test_la_vista_previa_pinta_todas_sus_secciones(): void
    {
        $html = $this->preview();

        foreach ([
            'portada', 'itinerario', 'galeria', 'vestimenta',
            'mesa-de-regalos', 'cuenta-regresiva', 'confirmacion', 'preguntas',
        ] as $bloque) {
            $this->assertStringContainsString('id="' . $bloque . '"', $html, "Falta el bloque {$bloque}.");
        }

        $this->assertStringContainsString('tc-footer', $html, 'El pie cierra la invitación.');
    }

    public function test_usa_sus_propios_estilos_y_sus_propias_fuentes(): void
    {
        $html = $this->preview();

        // Cormorant para los titulares y Manrope para los datos.
        $this->assertStringContainsString('family=Cormorant', $html);
        $this->assertStringContainsString('Manrope', $html);

        // Y no arrastra el CSS ni el JS de las otras plantillas.
        $this->assertStringNotContainsString('templates/template-editorial/', $html);
        $this->assertStringNotContainsString('templates/template-destino/', $html);

        /*
        | Las rutas locales las reescribe Vite al compilar, así que en el HTML
        | no se leen tal cual. Lo que sí se puede comprobar es que la estrategia
        | pide las suyas y sólo las suyas.
        */
        $assets = app(\App\Templates\TemplateRenderer::class)->demo(self::VIEW)['assets'];

        $this->assertContains('resources/css/templates/template-clasica/template.css', $assets);
        $this->assertContains('resources/js/templates/template-clasica/index.js', $assets);
    }

    /**
     * La cuenta llega pintada desde el servidor: quien no tenga JS la ve con su
     * valor correcto aunque no avance, en vez de cuatro huecos.
     */
    public function test_la_cuenta_regresiva_llega_pintada_con_sus_cuatro_cifras(): void
    {
        $html = $this->preview();

        foreach (['days', 'hours', 'minutes', 'seconds'] as $unidad) {
            $this->assertMatchesRegularExpression(
                '/data-countdown-' . $unidad . '>\d{2}</',
                $html,
                "La cifra de {$unidad} debería venir calculada.",
            );
        }

        foreach (['Días', 'Horas', 'Min', 'Seg'] as $etiqueta) {
            $this->assertStringContainsString($etiqueta, $html);
        }
    }

    /**
     * Con varias fotos el marco las cruza en bucle. La primera llega encendida
     * desde el servidor: sin JS se ve ésa y ya, nunca un hueco.
     */
    public function test_el_marco_trae_sus_fotos_y_la_primera_encendida(): void
    {
        $html = $this->preview();

        // El corchete deja fuera al contenedor, que se llama '…__fotos'.
        $this->assertSame(3, preg_match_all('/tc-countdown__foto["\s]/', $html));
        $this->assertStringContainsString('data-carrusel', $html);
        $this->assertSame(1, substr_count($html, 'tc-countdown__foto is-visible'));
    }

    /** Con una sola foto no hay nada que cruzar. */
    public function test_con_una_sola_foto_el_marco_no_se_mueve(): void
    {
        $html = $this->pintarCuenta(['https://example.test/una.jpg']);

        $this->assertStringContainsString('tc-countdown__foto', $html);
        $this->assertStringNotContainsString('data-carrusel', $html);
    }

    public function test_sin_fotos_la_cuenta_se_lee_igual(): void
    {
        $html = $this->pintarCuenta([]);

        $this->assertStringNotContainsString('tc-countdown__marco', $html);
        $this->assertStringContainsString('El gran día llega en', $html);
    }

    public function test_cada_momento_pinta_su_foto_y_su_liga_de_mapa(): void
    {
        $html = $this->preview();

        preg_match_all('/tc-timeline__foto" src="([^"]+)"/', $html, $fotos);

        $this->assertCount(2, $fotos[1], 'El ejemplo trae ceremonia y recepción.');
        $this->assertNotSame($fotos[1][0], $fotos[1][1], 'Cada momento tiene la suya.');
        $this->assertStringContainsString('Ver en mapa', $html);
    }

    public function test_cada_foto_de_la_galeria_se_puede_abrir(): void
    {
        $html = $this->preview();

        $this->assertStringContainsString('data-visor', $html);
        $this->assertSame(5, substr_count($html, 'tc-gallery__enlace'));
    }

    /** Las preguntas van numeradas, como en el diseño. */
    public function test_las_preguntas_van_numeradas(): void
    {
        $html = $this->preview();

        foreach (['01', '02', '03', '04', '05'] as $numero) {
            $this->assertStringContainsString('tc-faq__numero">' . $numero, $html);
        }
    }

    /* ------------------------------------------------------------------ */

    private function seccion(string $clave)
    {
        foreach (app(ClassicWeddingStrategy::class)->sections() as $seccion) {
            if ($seccion->key() === $clave) {
                return $seccion;
            }
        }

        $this->fail("La plantilla no declara la sección '{$clave}'.");
    }

    /**
     * Pinta sólo el bloque de la cuenta con las fotos que se le pasen.
     *
     * Montar un evento entero para comprobar cuántas fotos lleva el marco sería
     * mucho ruido: el bloque sólo necesita su sección y un contexto con fecha.
     */
    private function pintarCuenta(array $fotos): string
    {
        $seccion = new SectionView(
            key: 'countdown',
            title: 'Cuenta regresiva',
            blockType: BlockType::COUNTDOWN,
            component: 'templates.template-clasica.blocks.countdown',
            data: ['images' => $fotos],
        );

        $contexto = new TemplateContext(
            wifeName: 'Ana',
            husbandName: 'Luis',
            eventDate: Carbon::now()->addDays(30),
            parents: null,
            theme: [],
        );

        return Blade::render(
            '<x-templates.template-clasica.blocks.countdown :section="$section" :context="$context" />',
            ['section' => $seccion, 'context' => $contexto],
        );
    }

    private function preview(): string
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        return $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();
    }
}
