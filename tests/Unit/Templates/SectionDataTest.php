<?php

namespace Tests\Unit\Templates;

use App\Models\SystemSection;
use App\Services\SystemSectionService;
use App\Templates\BlockType;
use App\Templates\Sections\Catalog\DressCodeSection;
use App\Templates\Sections\Catalog\FaqSection;
use App\Templates\Sections\Catalog\GallerySection;
use App\Templates\Sections\Catalog\GeneralSection;
use App\Templates\Sections\Catalog\GiftRegistrySection;
use App\Templates\Sections\Catalog\ItinerarySection;
use App\Templates\Sections\Catalog\RsvpSection;
use Tests\TestCase;

/**
 * Cada sección traduce lo guardado en el wizard a lo que pinta el bloque.
 * Son funciones puras: array de entrada, array de salida.
 */
class SectionDataTest extends TestCase
{
    public function test_cada_seccion_apunta_a_un_tipo_de_bloque_del_catalogo(): void
    {
        $sections = [
            new GeneralSection(),
            new ItinerarySection(),
            new DressCodeSection(),
            new GiftRegistrySection(),
            new GallerySection(),
            new FaqSection(),
            new RsvpSection(),
        ];

        foreach ($sections as $section) {
            $this->assertTrue(
                BlockType::exists($section->blockType()),
                "La sección {$section->key()} apunta a un tipo de bloque desconocido.",
            );
        }

        $this->assertSame(BlockType::BANNER, (new GeneralSection())->blockType());
        $this->assertSame(BlockType::CONTACT_FORM, (new RsvpSection())->blockType());
    }

    /**
     * El orden lo pone el organizador arrastrando las filas, y se respeta
     * aunque no sea el cronológico: hay bodas donde el civil se cuenta aparte,
     * o donde se quiere abrir con la fiesta. Antes se reordenaba por hora y lo
     * que él dejara daba igual.
     */
    public function test_el_itinerario_respeta_el_orden_del_wizard_y_descarta_filas_vacias(): void
    {
        $data = (new ItinerarySection())->data([
            'events' => [
                ['name' => 'Fiesta', 'place_event' => 'Salón', 'date_event' => '2027-01-10 20:00:00'],
                ['name' => '', 'place_event' => ''],
                ['name' => 'Ceremonia', 'place_event' => 'Parroquia', 'date_event' => '2027-01-10 15:30:00'],
            ],
        ], []);

        // Tal como venían, aunque la fiesta sea más tarde.
        $this->assertSame(['Fiesta', 'Ceremonia'], array_column($data['moments'], 'name'));
        // Y la fila sin nombre ni lugar no se pinta.
        $this->assertCount(2, $data['moments']);
        $this->assertSame('8:00 pm', $data['moments'][0]['time']);
    }

    public function test_la_portada_toma_ceremonia_y_fiesta_del_itinerario(): void
    {
        $all = [
            'itinerary' => [
                'events' => [
                    ['name' => 'Ceremonia', 'place_event' => 'Parroquia', 'date_event' => '2027-01-10 15:30:00'],
                    ['name' => 'Fiesta', 'place_event' => 'Salón', 'date_event' => '2027-01-10 20:00:00'],
                ],
            ],
        ];

        $data = (new GeneralSection())->data([
            'name_wife' => 'Zoe',
            'name_husband' => 'Harry',
            'date_event_person' => '2027-01-10 15:30:00',
        ], $all);

        $this->assertSame('Zoe', $data['wife_name']);
        $this->assertSame('Parroquia', $data['ceremony']['place']);
        $this->assertSame('Salón', $data['party']['place']);
        $this->assertSame('2027-01-10', $data['event_date']->format('Y-m-d'));
    }

    public function test_la_galeria_solo_conserva_imagenes_subidas(): void
    {
        $section = new GallerySection();

        $data = $section->data([
            'guest_photos_message' => 'Comparte tus fotos',
            'photos' => [
                ['image' => ['uuid' => 'abc', 'url' => 'https://cdn.test/1.jpg']],
                ['image' => null],
            ],
        ], []);

        $this->assertSame(['https://cdn.test/1.jpg'], $data['images']);
        $this->assertTrue($section->isVisible($data));
        $this->assertFalse($section->isVisible($section->data([], [])));
    }

    public function test_las_preguntas_sin_texto_no_entran(): void
    {
        $section = new FaqSection();

        $data = $section->data([
            'faqs' => [
                ['question' => '¿Hay estacionamiento?', 'content' => 'Sí.'],
                ['question' => '', 'content' => 'Sin pregunta'],
            ],
        ], []);

        $this->assertCount(1, $data['items']);
        $this->assertFalse($section->isVisible($section->data([], [])));
    }

    public function test_la_vestimenta_se_oculta_si_no_hay_nada_capturado(): void
    {
        $section = new DressCodeSection();

        $this->assertFalse($section->isVisible($section->data([], [])));
        $this->assertTrue($section->isVisible($section->data(['dress_code_type' => 'Formal'], [])));
    }

    public function test_la_confirmacion_se_cierra_pasada_la_fecha_limite(): void
    {
        $section = new RsvpSection();

        $abierta = $section->data(['rsvp_deadline' => now()->addWeek()->toDateTimeString()], []);
        $cerrada = $section->data(['rsvp_deadline' => now()->subWeek()->toDateTimeString()], []);

        $this->assertFalse($abierta['closed']);
        $this->assertTrue($cerrada['closed']);
    }

    public function test_la_mesa_de_regalos_arma_pares_de_etiqueta_y_valor(): void
    {
        $data = (new GiftRegistrySection())->data([
            'section_title' => 'Mesa de regalos',
            'registries' => [
                ['type' => 'cuenta_bancaria', 'banco' => 'BBVA', 'clabe' => '0121800123'],
            ],
        ], []);

        $option = $data['options'][0];
        // Sin la sección del sistema cargada, las llaves se muestran legibles.
        $this->assertSame('Cuenta Bancaria', $option['type']);
        $this->assertSame(['Banco', 'Clabe'], array_column($option['details'], 'label'));
        $this->assertSame(['BBVA', '0121800123'], array_column($option['details'], 'value'));
    }

    public function test_entrega_el_tipo_de_cada_dato_y_el_tipo_elegido(): void
    {
        // La sección no decide cómo se pinta nada: entrega el dato con el tipo
        // que le puso el superadmin, y cada plantilla elige si lo enlaza, lo
        // copia o lo deja en texto.
        $section = new GiftRegistrySection($this->sectionsWithSchema([
            ['key' => 'banco', 'label' => 'Banco', 'type' => 'text'],
            ['key' => 'liga', 'label' => 'Liga', 'type' => 'url'],
        ]));

        $data = $section->data([
            'registries' => [
                ['type' => 'cuenta_bancaria', 'banco' => 'BBVA', 'liga' => 'https://banco.mx'],
            ],
        ], []);

        $option = $data['options'][0];

        $this->assertSame(['text', 'url'], array_column($option['details'], 'type'));
        $this->assertArrayNotHasKey('copy', $option['details'][0]);

        // Y el valor crudo del tipo, con el que la plantilla decide su diseño.
        $this->assertSame('cuenta_bancaria', $option['type_value']);
    }

    private function sectionsWithSchema(array $schema): SystemSectionService
    {
        $section = new SystemSection(['key' => 'registries', 'schema' => $schema]);

        return new class($section) extends SystemSectionService {
            public function __construct(private SystemSection $section)
            {
            }

            public function findByKey(string $key): ?SystemSection
            {
                return $this->section;
            }
        };
    }
}
