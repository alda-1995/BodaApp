<?php

namespace Tests\Feature\Invitation;

use App\Models\SystemSection;
use App\Models\Template;
use App\Services\Template\TemplateDiscoveryService;
use App\Strategies\EditorialWeddingStrategy;
use App\Templates\BlockType;
use App\Templates\Sections\Section;
use App\Templates\Sections\Catalog\ItinerarySection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Plantilla "Boda Editorial": por ahora sólo su portada.
 */
class EditorialTemplateTest extends TestCase
{
    use RefreshDatabase;

    private const VIEW = 'build-templates.template-editorial.index';

    /** Seguro: RefreshDatabase vacía la BD; aborta si no es una BD *_test. */
    protected function beforeRefreshingDatabase()
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');

        if (!str_ends_with($database, '_test')) {
            throw new RuntimeException("Abortado: RefreshDatabase iba a vaciar '{$database}'.");
        }
    }

    /**
     * El catálogo de mesa de regalos que configura el superadmin.
     *
     * La sección no inventa opciones: las lee de aquí, y de aquí sale también
     * lo que se ve en la vista previa. Sin este catálogo no hay nada que
     * enseñar, así que los tests que miran ese bloque lo crean primero.
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
                    // Copiable: se enseña con su botón, no como enlace.
                    'is_copyable' => '1',
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
                    'is_copyable' => '1',
                ],
            ],
        ]);
    }

    public function test_el_catalogo_del_superadmin_llega_al_paso_de_mesa_de_regalos(): void
    {
        $strategy = app(TemplateDiscoveryService::class)->resolveStrategy(self::VIEW);

        // Sin catálogo, el paso sólo pide lo suyo: no se rompe ni inventa nada.
        $sinCatalogo = $strategy->getClientWizardSteps()['gift_registry']['fields'];
        $this->assertSame(['section_title', 'message'], array_keys($sinCatalogo));

        $this->seedRegistryCatalog();

        // Con catálogo, los tipos que puso el superadmin son un campo más del
        // mismo paso. Nadie tocó la plantilla para que aparecieran.
        $conCatalogo = app(TemplateDiscoveryService::class)
            ->resolveStrategy(self::VIEW)
            ->getClientWizardSteps()['gift_registry']['fields'];

        $this->assertArrayHasKey('registries', $conCatalogo);

        $reglas = $conCatalogo['registries']->toValidationRules('');

        // Y sus campos validan, incluidos los que sólo aplican a un tipo.
        $this->assertArrayHasKey('registries.*.tienda', $reglas);
        $this->assertArrayHasKey('registries.*.clabe', $reglas);
    }

    public function test_la_vista_previa_pinta_la_portada_con_datos_de_ejemplo(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $this->get(route('templates.preview', $template->slug))
            ->assertOk()
            ->assertSee('Sofía')
            ->assertSee('Alejandro')
            // El diseño pone el título del itinerario en mayúsculas.
            ->assertSee('EL GRAN DÍA');
    }

    public function test_pinta_todas_sus_secciones(): void
    {
        $this->seedRegistryCatalog();

        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $this->get(route('templates.preview', $template->slug))
            ->assertOk()
            ->assertSee('te-banner', false)
            // El itinerario, en tarjetas: cada momento con su nombre.
            ->assertSee('te-itinerary', false)
            ->assertSee('CEREMONIA RELIGIOSA')
            // El título de ejemplo es texto, no una imagen: si se pasara por
            // asset() saldría como '.../EL GRAN DÍA'.
            ->assertSee('<h2 class="te-itinerary__title">EL GRAN DÍA</h2>', false)
            // La historia por años, con su ilustración y sus fotos por año.
            ->assertSee('te-story', false)
            ->assertSee('Nuestra historia')
            ->assertSee('2017')
            ->assertSee('images/assets-editorial/historia-banda.png', false)
            // Las fotos de los años están todas en el HTML: en escritorio las
            // esconde el CSS hasta pasar por su año, y en móvil se ven siempre.
            ->assertSee('te-story__year-photo', false)
            // La galería, en carrusel: sus fotos están en el HTML aunque Swiper
            // no llegue a correr.
            ->assertSee('te-gallery__slide', false)
            ->assertSee('images/assets-editorial/galeria-1.png', false)
            /*
            | Cada foto va dentro de un enlace a sí misma: así el visor la abre
            | en grande, y sin JS el enlace sigue sirviendo para verla.
            */
            ->assertSee('<a class="te-gallery__link" href="' . asset('images/assets-editorial/galeria-1.png'), false)
            // La mesa de regalos: las tiendas con su liga y las cuentas en la
            // tarjeta, que es como su diseño reparte lo que hay configurado.
            ->assertSee('MESA DE REGALOS')
            ->assertSee('te-gifts__store', false)
            ->assertSee('Liverpool')
            ->assertSee('te-gifts__card', false)
            ->assertSee('Transferencia')
            ->assertSee('567836773893834234')
            // Y ninguna de esas opciones está escrita en la plantilla: salen
            // del catálogo del superadmin, incluido su ejemplo.
            // La plantilla decide el control: una liga se enlaza...
            ->assertSee('te-gifts__store-link', false)
            ->assertSee('https://mesaderegalos.liverpool.com.mx/', false)
            // ...y una clave larga se copia.
            ->assertSee('data-copy-group', false)
            // El código de vestimenta: la nota de la novia y las muestras de
            // color, que son lo propio de este diseño.
            ->assertSee('te-dress__columns', false)
            ->assertSee('Reservado para la novia: tonos blancos, marfil, champagne y crema.')
            ->assertSee('te-dress__swatch-dot', false)
            ->assertSee('Azul noche')
            // Los hoteles, en tarjetas con su tarifa y su código.
            ->assertSee('te-hotels__card', false)
            ->assertSee('Casa de Sierra Nevada')
            ->assertSee('Tarifa: $4,800 MXN/noche · Código: BODASA2026')
            // El transporte: su mapa ilustrado y las corridas en carrusel.
            ->assertSee('te-transport__ride', false)
            ->assertSee('images/assets-editorial/transporte-mapa.png', false)
            ->assertSee('Hoteles → Parroquia', false)
            // La confirmación: su diseño parte el nombre en dos campos y rsvp.js
            // los junta al enviar, así que el contrato con el servidor no cambia.
            ->assertSee('name="first_name"', false)
            ->assertSee('name="last_name"', false)
            ->assertSee('name="answers[Mensaje para los novios]"', false)
            ->assertSee('Confirmar asistencia')
            // Las preguntas, en acordeón.
            ->assertSee('te-faq__item', false)
            ->assertSee('Preguntas frecuentes')
            ->assertSee('¿Se admiten niños?');
    }

    public function test_su_estrategia_es_la_suya(): void
    {
        $strategy = app(TemplateDiscoveryService::class)->resolveStrategy(self::VIEW);

        $this->assertInstanceOf(EditorialWeddingStrategy::class, $strategy);
        $this->assertSame('Boda Editorial', $strategy->getName());
        // Sin esta clase la invitación se pintaría sin su base de estilos.
        $this->assertSame('te', $strategy->bodyClass());
    }

    public function test_el_orden_lo_declara_su_estrategia(): void
    {
        $strategy = app(TemplateDiscoveryService::class)->resolveStrategy(self::VIEW);

        // El orden en que la estrategia lista sus bloques es el de los pasos.
        $steps = array_keys($strategy->getClientWizardSteps());

        $this->assertSame(
            ['general', 'itinerary', 'milestones', 'gallery', 'gift_registry',
             'dress_code', 'hotels', 'transport', 'rsvp', 'faqs'],
            $steps,
        );
    }

    public function test_no_pide_los_padres_de_familia_porque_no_los_pinta(): void
    {
        $strategy = app(TemplateDiscoveryService::class)->resolveStrategy(self::VIEW);
        $paso = $strategy->getClientWizardSteps()['general']['fields'];

        $this->assertArrayNotHasKey('family_parents', $paso);
        // Lo demás del paso sigue igual: sólo se fue ese.
        $this->assertArrayHasKey('name_wife', $paso);
        $this->assertArrayHasKey('event_city', $paso);

        // Y la otra plantilla sí los usa, así que los sigue preguntando.
        $travel = app(TemplateDiscoveryService::class)
            ->resolveStrategy('build-templates.template-travel.index')
            ->getClientWizardSteps()['general']['fields'];

        $this->assertArrayHasKey('family_parents', $travel);
    }

    public function test_el_bloque_puede_llamarse_distinto_a_su_tipo(): void
    {
        $itinerario = $this->seccion('itinerary');

        // Su archivo es itinerary.blade.php y su tipo sigue siendo 'timeline'.
        $this->assertSame('itinerary', $itinerario->componentFile());
        $this->assertSame(BlockType::TIMELINE, $itinerario->blockType());

        // Y eso no alcanza a las demás: la del catálogo usa el nombre del tipo.
        $this->assertNull((new ItinerarySection())->componentFile());
    }

    public function test_usa_sus_propios_estilos_y_sus_propias_fuentes(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        $this->assertStringContainsString('templates/template-editorial/template.css', $html);
        $this->assertStringContainsString('templates/template-editorial/index.js', $html);
        // Playfair Display sustituye a Boska mientras se licencia.
        $this->assertStringContainsString('family=Playfair+Display', $html);
        // Y no arrastra el CSS ni el JS de la otra plantilla.
        $this->assertStringNotContainsString('templates/template-travel/', $html);
    }

    public function test_el_pie_cierra_con_monograma_nombres_fecha_y_ciudad(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $this->get(route('templates.preview', $template->slug))
            ->assertOk()
            // El monograma es el mismo de la portada: lo sube el organizador.
            ->assertSee('class="te-footer__monogram" src="' . asset('images/assets-editorial/monograma-footer.png'), false)
            ->assertSee('<p class="te-footer__names">Sofía &amp; Alejandro</p>', false)
            // La ciudad la captura el organizador; no está escrita en el diseño.
            ->assertSee('<p class="te-footer__place">San Miguel de Allende, Gto.</p>', false);
    }

    public function test_sin_monograma_la_invitacion_no_cuelga_iniciales_ajenas(): void
    {
        /*
        | El monograma son las iniciales de la pareja. Si el organizador no sube
        | ninguno, ni la portada ni el pie deben rellenar el hueco con el del
        | diseño: serían las iniciales de otra boda.
        */
        $this->assertNull($this->seccion('general')->data([], [])['monogram']);

        $campos = app(TemplateDiscoveryService::class)
            ->resolveStrategy(self::VIEW)
            ->getClientWizardSteps()['general']['fields'];

        $this->assertArrayHasKey('monogram', $campos);
        $this->assertFalse($campos['monogram']->isRequired());
    }

    /** Una sección de esta plantilla, ya con lo que su estrategia le declara. */
    private function seccion(string $key): Section
    {
        $sections = app(TemplateDiscoveryService::class)->resolveStrategy(self::VIEW)->sections();

        return collect($sections)->firstWhere(fn (Section $s) => $s->key() === $key)
            ?? throw new RuntimeException("La plantilla no trae la sección '{$key}'.");
    }

    public function test_la_portada_y_el_pie_escriben_la_misma_fecha(): void
    {
        $template = Template::factory()->create(['view_path' => self::VIEW, 'is_active' => true]);

        $html = $this->get(route('templates.preview', $template->slug))->assertOk()->getContent();

        // El ejemplo del catálogo es siempre dentro de 45 días, así que la
        // fecha exacta cambia cada día: lo que se fija es que las dos salgan
        // del mismo formato y digan lo mismo.
        $fecha = mb_strtoupper(now()->addDays(45)->locale('es')->isoFormat('dddd · D [DE] MMMM · YYYY'));

        $this->assertStringContainsString('<p class="te-banner__date">' . $fecha . '</p>', $html);
        $this->assertStringContainsString('<p class="te-footer__date">' . $fecha . '</p>', $html);
    }

    public function test_la_ciudad_se_pregunta_en_el_paso_de_la_portada(): void
    {
        $paso = app(TemplateDiscoveryService::class)
            ->resolveStrategy(self::VIEW)
            ->getClientWizardSteps()['general']['fields'];

        $this->assertArrayHasKey('event_city', $paso);
        $this->assertTrue($paso['event_city']->isRequired());
    }
}
