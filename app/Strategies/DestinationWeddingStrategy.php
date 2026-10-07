<?php

namespace App\Strategies;

use App\FormBuilder\Controls\ColorField;
use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Controls\TextareaField;
use App\Templates\Sections\Catalog\DestinationSection;
use App\Templates\Sections\Catalog\DressCodeSection;
use App\Templates\Sections\Catalog\FaqSection;
use App\Templates\Sections\Catalog\GallerySection;
use App\Templates\Sections\Catalog\GeneralSection;
use App\Templates\Sections\Catalog\GiftRegistrySection;
use App\Templates\Sections\Catalog\HotelsSection;
use App\Templates\Sections\Catalog\ItinerarySection;
use App\Templates\Sections\Catalog\MilestonesSection;
use App\Templates\Sections\Catalog\RsvpSection;

/**
 * Plantilla "Boda Destino" (build-templates/template-destino).
 *
 * Una boda contada como un viaje: a dónde se va, cuánto falta para salir y qué
 * hay al llegar. Sus bloques viven en components/templates/template-destino/
 * blocks/, y su CSS y JS en resources/{css,js}/templates/template-destino/.
 */
class DestinationWeddingStrategy extends DefaultTemplateStrategy
{
    /** Carpeta de sus imágenes de diseño. */
    private const IMG = 'images/assets-destino/';

    public function getName(): string
    {
        return 'Boda Destino';
    }

    /** Activa su base de estilos: .td en resources/css/templates/template-destino. */
    public function bodyClass(): string
    {
        return 'td';
    }

    /**
     * Sus diez secciones, en el orden en que se capturan y se pintan.
     *
     * Este orden es el del wizard y el del diseño: la primera es el primer paso
     * que llena el organizador y lo primero que ve el invitado. Reordenar la
     * invitación es mover un bloque de aquí; quitar una sección es borrarlo, y
     * con él desaparece su paso.
     */
    public function sections(): array
    {
        return [
            $this->section(GeneralSection::class)
                ->blade('banner')
                ->add('cover_photo', $this->photo('cover_photo', 'Foto de portada')
                    ->help('Ocupa la pantalla completa. Horizontal y con aire en el centro, que es donde caen los nombres.'))
                /*
                | El monograma son las iniciales de la pareja, así que es suyo.
                | Va sin valor por defecto a propósito: si no sube ninguno, la
                | invitación se pinta sin él en vez de colgarle a esta boda las
                | iniciales de otra. Se repite grande en el pie, así que conviene
                | PNG sólido con fondo transparente.
                */
                ->add('monogram', $this->photo('monogram', 'Monograma de la pareja')
                    ->help('Opcional. Va sobre la foto de portada. PNG con fondo transparente.')
                    ->allowedMimes(['png', 'webp']))
                /*
                | El del cierre va aparte porque no se usa igual: en la portada
                | es pequeño sobre una foto, y en el pie se repite enorme como
                | marca de agua. Suele ser otra versión del mismo dibujo, con más
                | trazo. Si no suben ninguno se usa el de la portada, que es
                | mejor que cerrar sin firma.
                */
                ->add('footer_monogram', $this->photo('footer_monogram', 'Monograma del pie')
                    ->help('Opcional. El que cierra la invitación; se repite enorme de fondo, atenuado. Súbelo sólido, no pálido. Si lo dejas vacío se usa el de la portada.')
                    ->allowedMimes(['png', 'webp']))
                ->add('scroll_hint', TextField::make('scroll_hint', 'Invitación a bajar')
                    ->placeholder('Ej. Scrolldown para descubrir')
                    ->help('La línea pequeña al pie de la portada.')
                    ->default('Scrolldown para descubrir')
                    ->nullable()
                    ->rules(['string', 'max:60']))
                // Esta portada no los pinta en ningún lado.
                ->drop('family_parents')
                ->example([
                    'cover_photo' => asset(self::IMG . 'portada.png'),
                    'monogram' => asset(self::IMG . 'monograma.png'),
                    /*
                    | El mismo dibujo sólido que la portada: el pie lo usa dos
                    | veces —firma y marca de agua— y la marca la atenúa el CSS.
                    | Un PNG ya pálido se perdería como firma.
                    */
                    'footer_monogram' => asset(self::IMG . 'monograma.png'),
                    'scroll_hint' => 'Scrolldown para descubrir',
                ]),

            /*
            | El bloque que le da el tono a la plantilla. La cuenta regresiva se
            | calcula sola desde la fecha de la boda: no se pregunta aquí.
            */
            $this->section(DestinationSection::class)
                ->example([
                    'city' => 'Mérida',
                    'origin_code' => 'MEX',
                    'destination_code' => 'MID',
                ]),

            $this->section(MilestonesSection::class)
                // Las fotos caen en abanico; un párrafo de introducción no cabe.
                ->drop('intro')
                ->example([
                    'title' => 'Dónde empezamos',
                    'milestones' => [
                        ['year' => '2008', 'image' => asset(self::IMG . 'historia-1.png')],
                        ['year' => '2012', 'image' => asset(self::IMG . 'historia-2.png')],
                        ['year' => '2017', 'image' => asset(self::IMG . 'historia-3.png')],
                        ['year' => '2021', 'image' => asset(self::IMG . 'historia-4.png')],
                        ['year' => '2024', 'image' => asset(self::IMG . 'historia-5.png')],
                    ],
                ]),

            /*
            | Sin título de sección: cada momento se nombra solo ("Ceremonia",
            | "Recepción"), así que un encabezado encima no diría nada nuevo.
            |
            | El clima y la hora local tampoco se preguntan: vienen rotulados en
            | la propia foto, que la prepara el equipo de diseño.
            */
            $this->section(ItinerarySection::class)
                ->add('event_photo', $this->photo('event_photo', 'Foto para esta sección')
                    ->help('Ocupa toda la mitad izquierda. Vertical. Si tu destino tiene un dato de viaje —el clima, la hora local— va rotulado en ella.'))
                // Primero la foto; al final los momentos.
                ->first('event_photo')
                ->example([
                    'event_photo' => asset(self::IMG . 'itinerario.png'),
                ]),

            /*
            | Una tarjeta sobre la foto del hotel: nombre, botón de reserva y la
            | nota de la tarifa. Dirección, código e imagen propia no caben en
            | ese diseño, así que no se preguntan.
            */
            $this->section(HotelsSection::class)
                ->drop('message', 'hotels.address', 'hotels.code', 'hotels.image')
                ->add('background_image', $this->photo('background_image', 'Foto del hospedaje')
                    ->help('La foto ancha sobre la que se apoya la tarjeta del hotel.'))
                ->first('title', 'background_image', 'hotels')
                ->example([
                    'title' => 'Hospedaje',
                    'background_image' => asset(self::IMG . 'hospedaje.png'),
                    'hotels' => [
                        [
                            'name' => 'Hotel Merilia',
                            'rate' => 'Tarifa especial para nuestros invitados',
                            'url' => 'https://example.com/reserva',
                            'address' => null,
                            'code' => null,
                            'image' => null,
                        ],
                    ],
                ]),

            $this->section(DressCodeSection::class)
                /*
                | La nota para la novia y las muestras de color son del diseño,
                | pero cada boda las dice a su manera.
                */
                ->add('reserved_note', TextareaField::make('reserved_note', 'Nota reservada para la novia')
                    ->placeholder('Ej. Reservado para la novia: tonos blancos, marfil, champagne y crema.')
                    ->help('Va en letra pequeña bajo las muestras. Déjala vacía si no quieres pedir nada.')
                    ->nullable()
                    ->rules(['string', 'max:300']))
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
                ->first('dress_code_type', 'reference_image', 'men_attire', 'women_attire', 'color_or_theme', 'palette', 'reserved_note')
                ->example([
                    'reference_image' => asset(self::IMG . 'vestimenta.png'),
                    'reserved_note' => '*Reservado para la novia: tonos blancos, marfil, champagne y crema.',
                    'palette' => [
                        ['name' => 'Azul noche', 'color' => '#1b2235'],
                        ['name' => 'Café', 'color' => '#3d2b1f'],
                        ['name' => 'Mauve', 'color' => '#8b6e6e'],
                        ['name' => 'Olivo', 'color' => '#5c7a5c'],
                        ['name' => 'Rosa palo', 'color' => '#c4899a'],
                    ],
                ]),

            $this->section(GiftRegistrySection::class),

            $this->section(GallerySection::class)
                // Las fotos caen escalonadas sobre negro; no hay dónde escribir.
                ->drop('guest_photos_message')
                ->example([
                    'images' => [
                        asset(self::IMG . 'galeria-1.png'),
                        asset(self::IMG . 'galeria-2.png'),
                        asset(self::IMG . 'galeria-3.png'),
                        asset(self::IMG . 'galeria-4.png'),
                        asset(self::IMG . 'galeria-5.png'),
                        asset(self::IMG . 'galeria-6.png'),
                    ],
                ]),

            $this->section(RsvpSection::class)
                ->blade('contact-form')
                ->first('rsvp_deadline', 'welcome_message', 'thank_you_message'),

            /*
            | La banda de foto que separa la confirmación de las preguntas es de
            | este bloque, no del anterior: va pegada a él y es lo primero que
            | se ve al llegar.
            |
            | El texto del panel es lo que se lee a la derecha mientras no se
            | haya elegido ninguna pregunta; al tocar una, su respuesta ocupa
            | ese sitio.
            */
            $this->section(FaqSection::class)
                ->add('image', $this->photo('image', 'Foto sobre las preguntas')
                    ->help('La banda ancha que abre esta sección.'))
                ->add('intro', TextareaField::make('intro', 'Texto del panel derecho')
                    ->placeholder('Ej. Primavera perfecta: días 22-26°C, noches 10-14°C. Te recomendamos llevar una capa para la recepción al aire libre.')
                    ->help('Se lee a la derecha mientras no se elige ninguna pregunta.')
                    ->nullable()
                    ->rules(['string', 'max:400']))
                ->first('image', 'intro')
                ->example([
                    'image' => asset(self::IMG . 'rsvp-playa.png'),
                    'intro' => 'Primavera perfecta: días 22-26°C, noches 10-14°C. Te recomendamos llevar una capa para la recepción al aire libre.',
                ]),
        ];
    }

    /**
     * Las dos del diseño, ambas en Google Fonts. Bricolage Grotesque para los
     * titulares y DM Sans para el cuerpo; la itálica se pide explícitamente
     * porque el diseño la usa y sin ella el navegador la inventa.
     */
    public function fonts(): array
    {
        return [
            'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,200;12..96,300;12..96,400;12..96,500;12..96,600&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400;1,9..40,500&display=swap',
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
