<?php
namespace App\Strategies;

use App\Templates\Sections\Catalog\DressCodeSection;
use App\Templates\Sections\Catalog\FaqSection;
use App\Templates\Sections\Catalog\GallerySection;
use App\Templates\Sections\Catalog\GeneralSection;
use App\Templates\Sections\Catalog\GiftRegistrySection;
use App\Templates\Sections\Catalog\ItinerarySection;
use App\Templates\Sections\Catalog\RsvpSection;
use App\Templates\Sections\Travel\HistorySection;

/**
 * Plantilla "Boda Barco" (build-templates/template-travel).
 *
 * Sus bloques viven en components/templates/template-travel/blocks/, uno por
 * tipo, y su CSS y JS en resources/{css,js}/templates/template-travel/.
 */
class EmeraldWeddingStrategy extends DefaultTemplateStrategy
{
    /** Carpeta de sus imágenes de diseño. */
    private const IMG = 'images/assets-travel/';

    public function getName(): string
    {
        return 'Boda Barco';
    }

    /**
     * Sus secciones y el orden en que se capturan.
     *
     * Casi todas son las del catálogo tal cual: lo propio de esta plantilla son
     * sus dos animaciones, que se dibujan cuadro por cuadro sobre un canvas.
     * Las secuencias las prepara el equipo de diseño y las sube el superadmin;
     * aquí sólo se declara dónde viven y cuántos cuadros tienen, para que el JS
     * deje de traer la ruta escrita a mano.
     *
     * No se valida que una secuencia esté completa: si falta un cuadro, la
     * animación se traba y hay que volver a subirla.
     *
     * La historia (la caja de recuerdos) es exclusiva de esta plantilla: no
     * forma parte del catálogo base.
     */
    public function sections(): array
    {
        return [
            $this->section(GeneralSection::class)
                ->blade('banner')
                ->asset('frames_card', 'Secuencia del sobre', self::IMG . 'frame-card/', [
                    'help' => 'Los cuadros numerados del sobre que se abre. Se suben todos juntos.',
                    'kind' => 'sequence',
                    'count' => 25,
                    'extension' => 'png',
                ]),

            new HistorySection(),

            $this->section(ItinerarySection::class)
                ->blade('timeline')
                ->asset('frames_time', 'Secuencia del barco (escritorio)', self::IMG . 'frame-time/', [
                    'help' => 'Los cuadros numerados del recorrido. Se suben todos juntos.',
                    'kind' => 'sequence',
                    'count' => 36,
                    'extension' => 'jpg',
                ])
                ->asset('frames_time_mobile', 'Secuencia del barco (móvil)', self::IMG . 'frame-time-mobile/', [
                    'help' => 'La misma secuencia en tamaño reducido, para celulares.',
                    'kind' => 'sequence',
                    'count' => 36,
                    'extension' => 'jpg',
                ]),

            new DressCodeSection(),
            new GiftRegistrySection($this->sectionService),
            new RsvpSection(),
            new GallerySection(),
            new FaqSection(),
        ];
    }

    /**
     * Sus tipografías. La Antura (local) la carga su propia hoja de estilos;
     * aquí van las que vienen de Google.
     */
    public function fonts(): array
    {
        return [
            'https://fonts.googleapis.com/css2?family=Montaga&family=Onest:wght@100..900&family=Aboreto&display=swap',
        ];
    }
}
