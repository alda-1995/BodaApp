<?php
namespace App\Strategies;

use App\FormBuilder\Controls\BooleanField;
use App\FormBuilder\Controls\ColorField;
use App\FormBuilder\Controls\NumberField;
use App\FormBuilder\Controls\SelectField;
use App\FormBuilder\Controls\TextareaField;
use App\Services\SystemSectionService;
use App\Templates\Sections\Catalog\DressCodeSection;
use App\Templates\Sections\Catalog\FaqSection;
use App\Templates\Sections\Catalog\GallerySection;
use App\Templates\Sections\Catalog\GeneralSection;
use App\Templates\Sections\Catalog\GiftRegistrySection;
use App\Templates\Sections\Catalog\ItinerarySection;
use App\Templates\Sections\Catalog\RsvpSection;
use App\Templates\Sections\Section;
use App\Templates\Sections\TemplateSection;

/**
 * Plantilla base: usa el catálogo de secciones completo. Las plantillas nuevas
 * heredan de aquí y quitan, agregan o reordenan secciones; cada una pinta los
 * tipos de bloque que tenga en su carpeta.
 */
class DefaultTemplateStrategy extends AbstractTemplateStrategy
{
    public function __construct(
        protected ?SystemSectionService $sectionService = null
    ) {
        parent::__construct();
    }

    public function getName(): string
    {
        return 'Plantilla Estándar';
    }

    public function sections(): array
    {
        return [
            new GeneralSection(),
            new ItinerarySection(),
            new DressCodeSection(),
            new GiftRegistrySection($this->sectionService),
            new RsvpSection(),
            new GallerySection(),
            new FaqSection(),
        ];
    }

    /**
     * Una sección del catálogo, lista para que la plantilla declare lo suyo.
     *
     * @param  class-string<Section>  $class
     */
    protected function section(string $class): TemplateSection
    {
        /*
        | La mesa de regalos necesita el catálogo del superadmin, de donde salen
        | las etiquetas de cada dato. Se compara con is_a() y no con ===, para
        | que una variante que la extienda también lo reciba: sin el catálogo,
        | sus datos se pintarían sin nombre.
        */
        $section = is_a($class, GiftRegistrySection::class, true)
            ? new $class($this->sectionService)
            : new $class();

        return new TemplateSection($section);
    }

    /*
    | Las opciones de mesa de regalo ya no se inyectan aquí: las pide
    | GiftRegistrySection, que es quien las usa. Así entran por el paso al que
    | pertenecen y sólo en las plantillas que traen ese bloque, en vez de
    | aparecer en todas por venir de la estrategia.
    */

    protected function baseAdminFields(): array
    {
        return [
            'design' => [
                'title' => 'Configuraciones de Diseño Base',
                'fields' => [
                    'primary_color' => ColorField::make('primary_color', 'Color Primario por Defecto')
                        ->default('#2d3748')
                        ->required(),
                    'secondary_color' => ColorField::make('secondary_color', 'Color Secundario por Defecto')
                        ->default('#4a5568')
                        ->nullable(),
                    'font_family' => SelectField::make('font_family', 'Tipografía Base')
                        ->placeholder('Elige una fuente')
                        ->options([
                            'Playfair Display' => 'Playfair Display',
                            'Montserrat' => 'Montserrat',
                            'Great Vibes' => 'Great Vibes',
                        ])
                        ->default('Montserrat')
                        ->required(),
                    'max_gallery_images' => NumberField::make('max_gallery_images', 'Límite de Imágenes en Galería')
                        ->default(10)
                        ->required(),
                ]
            ],
            'system' => [
                'title' => 'Ajustes del Sistema',
                'fields' => [
                    'is_premium' => BooleanField::make('is_premium', '¿Es Plantilla Premium?')
                        ->default(false),
                    'developer_notes' => TextareaField::make('developer_notes', 'Notas del Desarrollador')
                        ->nullable(),
                ]
            ]
        ];
    }
}
