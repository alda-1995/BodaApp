<?php

namespace App\Strategies;

use App\FormBuilder\Controls\ColorField;
use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Controls\TextareaField;
use App\Templates\Sections\Catalog\DressCodeSection;
use App\Templates\Sections\Catalog\FaqSection;
use App\Templates\Sections\Catalog\GallerySection;
use App\Templates\Sections\Catalog\GeneralSection;
use App\Templates\Sections\Catalog\GiftRegistrySection;
use App\Templates\Sections\Catalog\HotelsSection;
use App\Templates\Sections\Catalog\ItinerarySection;
use App\Templates\Sections\Catalog\MilestonesSection;
use App\Templates\Sections\Catalog\RsvpSection;
use App\Templates\Sections\Catalog\TransportSection;

/**
 * Plantilla "Boda Editorial" (build-templates/template-editorial).
 *
 * Sus bloques viven en components/templates/template-editorial/blocks/, y su
 * CSS y JS en resources/{css,js}/templates/template-editorial/.
 */
class EditorialWeddingStrategy extends DefaultTemplateStrategy
{
    /** Carpeta de sus imágenes de diseño. */
    private const IMG = 'images/assets-editorial/';

    public function getName(): string
    {
        return 'Boda Editorial';
    }

    /** Activa su base de estilos: .te en resources/css/templates/template-editorial. */
    public function bodyClass(): string
    {
        return 'te';
    }

    /**
     * Sus diez secciones, en el orden en que se capturan y se pintan.
     *
     * Este orden es el del wizard y el del diseño: la primera es el primer paso
     * que llena el organizador y lo primero que ve el invitado. Reordenar la
     * invitación es mover un bloque de aquí; quitar una sección es borrarlo, y
     * con él desaparece su paso.
     *
     * Cada bloque dice qué le agrega al catálogo, qué no pinta y en qué orden
     * pregunta. Lo agregado llega solo a la vista.
     */
    public function sections(): array
    {
        return [
            $this->section(GeneralSection::class)
                ->blade('banner')
                /*
                | La ciudad no la pinta la portada sino el pie, que cierra la
                | invitación con los mismos datos de esta sección.
                */
                ->add('event_city', TextField::make('event_city', 'Ciudad del evento')
                    ->placeholder('San Miguel de Allende, Gto.')
                    ->help('Aparece al cierre de la invitación, debajo de la fecha.')
                    ->required()
                    ->rules(['string', 'max:120']))
                ->add('cover_photo', $this->photo('cover_photo', 'Foto de portada'))
                /*
                | El monograma son las iniciales de la pareja, así que es suyo.
                | Va sin valor por defecto a propósito: si no sube ninguno, la
                | invitación se pinta sin él en vez de colgarle a esta boda las
                | iniciales de otra. Cuelga del borde de la foto y se repite en
                | el pie, así que conviene PNG sólido con fondo transparente.
                */
                ->add('monogram', $this->photo('monogram', 'Monograma de la pareja')
                    ->help('Opcional. Las iniciales que cuelgan de la foto de portada. PNG con fondo transparente.')
                    ->allowedMimes(['png', 'webp']))
                // Esta portada no los pinta en ningún lado.
                ->drop('family_parents')
                ->example([
                    'cover_photo' => asset(self::IMG . 'portada.png'),
                    'monogram' => asset(self::IMG . 'monograma-footer.png'),
                    'event_city' => 'San Miguel de Allende, Gto.',
                ]),

            // Su archivo se llama 'itinerary'; su tipo sigue siendo 'timeline'.
            $this->section(ItinerarySection::class)
                ->blade('itinerary')
                ->add('title', TextField::make('title', 'Título de la sección')
                    ->placeholder('Ej. El gran día')
                    ->help('El encabezado que va arriba de las tarjetas de ceremonia y recepción.')
                    ->default('El gran día')
                    ->required()
                    ->rules(['string', 'max:500']))
                ->add('event_photo', $this->photo('event_photo', 'Foto para esta sección'))
                // Primero el encabezado y la foto; al final los momentos.
                ->first('title', 'event_photo')
                ->example([
                    'title' => 'EL GRAN DÍA',
                    'event_photo' => asset(self::IMG . 'evento-1.png'),
                ]),

            $this->section(MilestonesSection::class)
                ->blade('story')
                ->asset('illustration', 'Ilustración de la sección', self::IMG . 'historia-banda.png', [
                    'help' => 'La banda ancha que va bajo el título. La prepara el equipo de diseño.',
                ]),

            $this->section(GallerySection::class)
                ->add('background_image', $this->photo('background_image', 'Imagen de fondo de la galería')
                    ->help('Opcional. Va detrás del carrusel, oscurecida para que las fotos resalten.'))
                // Su diseño no pinta el mensaje para invitados.
                ->drop('guest_photos_message')
                ->first('background_image', 'photos')
                /*
                | Sin fondo de ejemplo a propósito: es opcional, y la vista
                | previa tiene que enseñar cómo se ve sin él.
                */
                ->example([
                    'message' => 'Guardamos estos momentos. Después de la boda subiremos los tuyos.',
                    'images' => [
                        asset(self::IMG . 'galeria-1.png'),
                        asset(self::IMG . 'galeria-2.png'),
                        asset(self::IMG . 'galeria-3.png'),
                    ],
                ]),

            /*
            | Las dos ilustraciones se turnan: la primera tienda lleva una, la
            | segunda la otra, la tercera la primera otra vez.
            */
            $this->section(GiftRegistrySection::class)
                ->blade('gift-registry')
                ->asset('store_icon_a', 'Ilustración de tienda (1)', self::IMG . 'logo-liverpool.png', [
                    'help' => 'Acompaña a la primera tienda y se repite en las impares.',
                ])
                ->asset('store_icon_b', 'Ilustración de tienda (2)', self::IMG . 'logo-amazon.png', [
                    'help' => 'Acompaña a la segunda tienda y se repite en las pares.',
                ]),

            $this->section(DressCodeSection::class)
                ->blade('dress-code')
                /*
                | La nota para la novia estaba escrita en el Blade, pero no
                | todas las bodas la quieren ni la dicen igual.
                */
                ->add('reserved_note', TextareaField::make('reserved_note', 'Nota reservada para la novia')
                    ->placeholder('Ej. Reservado para la novia: tonos blancos, marfil, champagne y crema.')
                    ->help('Va enmarcada bajo las indicaciones. Déjala vacía si no quieres pedir nada.')
                    ->nullable()
                    ->rules(['string', 'max:300']))
                /*
                | El catálogo ya pide los colores como texto; este diseño los
                | enseña además como muestras, y para eso hace falta el nombre
                | y el color de cada una.
                */
                ->add('palette', RepeaterField::make('palette', 'Colores sugeridos')
                    ->sortable()
                    ->nullable()
                    ->schema([
                        'name' => TextField::make('name', 'Nombre del color')
                            ->placeholder('Ej. Azul noche')
                            ->nullable()
                            ->rules(['string', 'max:60']),
                        'color' => ColorField::make('color', 'Color')->required(),
                    ]))
                ->first('dress_code_type', 'men_attire', 'women_attire', 'reserved_note', 'color_or_theme', 'palette')
                ->example([
                    'reserved_note' => 'Reservado para la novia: tonos blancos, marfil, champagne y crema.',
                    'reference_image' => asset(self::IMG . 'evento-1.png'),
                    'palette' => [
                        ['name' => 'Vino', 'color' => '#4a2023'],
                        ['name' => 'Azul noche', 'color' => '#1f2740'],
                        ['name' => 'Café', 'color' => '#6b3a1f'],
                        ['name' => 'Olivo', 'color' => '#5f6b45'],
                        ['name' => 'Negro', 'color' => '#262626'],
                    ],
                ]),

            /*
            | Va del título directo a las tarjetas, y la tarjeta enseña tarifa y
            | código en vez de un botón de reservar.
            */
            $this->section(HotelsSection::class)
                ->drop('message', 'hotels.url')
                /*
                | El catálogo deja la foto vacía —una boda puede no tener foto de
                | su hotel—, pero la vista previa tiene que enseñar la tarjeta
                | completa, que es su diseño.
                */
                ->example([
                    'hotels' => [
                        [
                            'name' => 'Casa de Sierra Nevada',
                            'address' => 'Hospicio 35, Centro · San Miguel',
                            'rate' => '$4,800 MXN/noche',
                            'code' => 'BODASA2026',
                            'url' => null,
                            'image' => asset(self::IMG . 'galeria-1.png'),
                        ],
                        [
                            'name' => 'Hotel Matilda',
                            'address' => 'Aldama 53, Centro · San Miguel',
                            'rate' => '$3,200 MXN/noche',
                            'code' => 'SOAND26',
                            'url' => null,
                            'image' => asset(self::IMG . 'galeria-2.png'),
                        ],
                        [
                            'name' => 'Rosewood San Miguel',
                            'address' => 'Nemesio Díez 11, Centro · San Miguel',
                            'rate' => '$2,400 MXN/noche',
                            'code' => 'BODA-SMA26',
                            'url' => null,
                            'image' => asset(self::IMG . 'galeria-3.png'),
                        ],
                    ],
                ]),

            // Del mapa directo a las corridas: no tiene dónde poner un mensaje.
            $this->section(TransportSection::class)
                ->drop('message')
                ->asset('map', 'Mapa ilustrado', self::IMG . 'transporte-mapa.png', [
                    'help' => 'El recorrido dibujado que va sobre las corridas. Lo prepara el equipo de diseño.',
                ]),

            $this->section(RsvpSection::class)
                ->blade('contact-form')
                ->add('background_image', $this->photo('background_image', 'Imagen de fondo de la confirmación')
                    ->help('Opcional. Va detrás del formulario, atenuada para que se lea.'))
                // Primero la fecha límite y el fondo; al final lo largo.
                ->first('rsvp_deadline', 'background_image', 'welcome_message', 'thank_you_message')
                ->example(['background_image' => asset(self::IMG . 'portada.png')]),

            // Las preguntas son las de siempre: el catálogo tal cual.
            $this->section(FaqSection::class),
        ];
    }

    /**
     * Inter es la del diseño. Playfair Display está en lugar de Boska, que es
     * de pago: cuando se licencie se cambia aquí y en --font-display.
     */
    public function fonts(): array
    {
        return [
            'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600&family=Inter:wght@400;600&display=swap',
        ];
    }

    /** Una foto que sube el organizador. Todas se piden igual. */
    private function photo(string $name, string $label): ImageUploadField
    {
        return ImageUploadField::make($name, $label)
            ->maxSize(5120)
            ->allowedMimes(['jpg', 'jpeg', 'png', 'webp'])
            ->nullable()
            ->messages([
                'image' => 'El archivo debe ser una imagen válida.',
                'mimes' => 'El formato no es válido.',
                'max' => 'La imagen no debe pesar más de 5MB.',
            ]);
    }
}
