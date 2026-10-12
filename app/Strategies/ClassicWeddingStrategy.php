<?php

namespace App\Strategies;

use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Controls\TextareaField;
use App\Templates\Sections\Catalog\CountdownSection;
use App\Templates\Sections\Catalog\DressCodeSection;
use App\Templates\Sections\Catalog\FaqSection;
use App\Templates\Sections\Catalog\GallerySection;
use App\Templates\Sections\Catalog\GeneralSection;
use App\Templates\Sections\Catalog\GiftRegistrySection;
use App\Templates\Sections\Catalog\ItinerarySection;
use App\Templates\Sections\Catalog\RsvpSection;

/**
 * Plantilla "Boda Clásica" (build-templates/template-clasica).
 *
 * Una boda de toda la vida contada con serifa y mucho aire: fotos grandes sobre
 * blanco, los datos en letra pequeña y una sola banda color crema para la mesa
 * de regalos. Sus bloques viven en components/templates/template-clasica/
 * blocks/, y su CSS y JS en resources/{css,js}/templates/template-clasica/.
 */
class ClassicWeddingStrategy extends DefaultTemplateStrategy
{
    /** Carpeta de sus imágenes de diseño. */
    private const IMG = 'images/assets-clasica/';

    public function getName(): string
    {
        return 'Boda Clásica';
    }

    /** Activa su base de estilos: .tc en resources/css/templates/template-clasica. */
    public function bodyClass(): string
    {
        return 'tc';
    }

    /**
     * Sus ocho secciones, en el orden en que se capturan y se pintan.
     *
     * Este orden es el del wizard y el del diseño: la primera es el primer paso
     * que llena el organizador y lo primero que ve el invitado. Reordenar la
     * invitación es mover un bloque de aquí; quitar una sección es borrarlo, y
     * con él desaparece su paso.
     *
     * La cuenta regresiva tiene su propio bloque, pero no se pregunta: sale sola
     * de la fecha de la boda. Ese paso sólo pide las fotos que se alternan
     * dentro del marco.
     */
    public function sections(): array
    {
        return [
            $this->section(GeneralSection::class)
                ->blade('banner')
                ->add('invitation_line', TextField::make('invitation_line', 'Línea sobre los nombres')
                    ->placeholder('Ej. Los invitamos a celebrar')
                    ->help('La línea pequeña que abre la invitación, encima de los nombres.')
                    ->default('Los invitamos a celebrar')
                    ->nullable()
                    ->rules(['string', 'max:60']))
                /*
                | La única foto de la portada es la banda ancha que la cierra y
                | da paso al itinerario. La tarjeta de relieve donde se leen los
                | nombres no se pide: es parte del diseño, como la textura del
                | panel, y va fija en el bloque.
                */
                ->add('band_photo', $this->photo('band_photo', 'Foto de ancho completo')
                    ->help('Apaisada y de borde a borde. Separa la portada del itinerario.'))
                // Esta portada no los pinta en ningún lado.
                ->drop('family_parents')
                ->first('invitation_line', 'band_photo')
                ->example([
                    'invitation_line' => 'Los invitamos a celebrar',
                    'band_photo' => asset(self::IMG . 'banda-2.jpg'),
                ]),

            /*
            | Ceremonia y recepción. Es un repetidor: el diseño enseña dos
            | momentos, pero una boda con misa, cóctel y fiesta añade los suyos
            | sin que nadie toque la plantilla.
            |
            | Aquí sólo se piden los datos. Las tarjetas de relieve en las que se
            | leen son del diseño y van fijas en el bloque, que las va alternando
            | tal como aparecen; el organizador no sube ninguna.
            |
            | Sin título de sección: cada momento se nombra solo ("Ceremonia",
            | "Recepción"), así que un encabezado encima no diría nada nuevo.
            */
            $this->section(ItinerarySection::class)
                /*
                | El ejemplo va en la forma de los datos ya resueltos ('moments'),
                | no en la de los campos del wizard ('events'): es lo que recibe
                | el bloque, y es lo único que mira la vista previa del catálogo.
                */
                ->example([
                    'moments' => [
                        [
                            'name' => 'Ceremonia religiosa',
                            'place' => 'Templo de San Francisco de Asís',
                            'location' => 'Centro Histórico, Santiago de Querétaro',
                            'maps' => 'https://maps.google.com/?q=Templo+de+San+Francisco+de+Asis',
                            'time' => '5:00 pm',
                        ],
                        [
                            'name' => 'Recepción',
                            'place' => 'Hacienda Viborillas',
                            'location' => 'El Marqués, Querétaro',
                            'maps' => 'https://maps.google.com/?q=Hacienda+Viborillas',
                            'time' => '6:30 pm',
                        ],
                    ],
                ]),

            /*
            | El collage y, debajo, el párrafo que lo acompaña. El texto es de la
            | galería y no de un bloque aparte: en el diseño cuelga del collage,
            | sin título propio.
            */
            $this->section(GallerySection::class)
                ->add('intro', TextareaField::make('intro', 'Texto bajo las fotos')
                    ->placeholder('Unas palabras sobre estas fotos, o sobre ustedes.')
                    ->help('Se lee centrado debajo del collage. Déjalo vacío y sólo se ven las fotos.')
                    ->nullable()
                    /*
                    | Aquí se cuenta la historia de la pareja, así que da sitio
                    | para varios párrafos: el bloque crece hacia abajo y no hay
                    | nada que se deforme por escribir de más.
                    */
                    ->rules(['string', 'max:2000']))
                // Las fotos caen en collage; no hay dónde pedirle fotos a nadie.
                ->drop('guest_photos_message')
                ->first('intro')
                ->example([
                    'intro' => 'Cinco años de viajes, mudanzas y domingos sin plan. Estas son algunas de las fotos que nos trajeron hasta aquí, y nos encantaría que la siguiente fuera contigo.',
                    'images' => [
                        asset(self::IMG . 'galeria-1.jpg'),
                        asset(self::IMG . 'galeria-2.jpg'),
                        asset(self::IMG . 'galeria-3.jpg'),
                        asset(self::IMG . 'galeria-4.jpg'),
                        asset(self::IMG . 'galeria-5.jpg'),
                    ],
                ]),

            /*
            | Una foto alta y, al lado, el código en dos líneas. El diseño no
            | separa damas de caballeros en columnas: las escribe seguidas, así
            | que se preguntan las dos pero se leen juntas.
            */
            $this->section(DressCodeSection::class)
                /*
                | Aquí no se sube ninguna imagen de referencia: la etiqueta
                | colgante donde se lee el código es del diseño y va fija en el
                | bloque, así que el catálogo la pediría para nada.
                */
                ->drop('reference_image')
                ->first('dress_code_type', 'men_attire', 'women_attire', 'color_or_theme')
                ->example([
                    'type' => 'Formal Romántico',
                    'men_attire' => 'Caballeros: traje o esmoquin.',
                    'women_attire' => 'Damas: vestido largo o de cóctel elegante.',
                ]),

            // La única banda color crema de la invitación.
            // Las opciones van en tarjetas enmarcadas; no hay foto en este bloque.
            $this->section(GiftRegistrySection::class)
                ->example([
                    'title' => 'Mesa de regalos',
                    'message' => 'Si deseas regalarnos algo, aquí nuestras opciones.',
                ]),

            /*
            | Cuánto falta, sobre el mismo crema. La cuenta no se pregunta: sale
            | de la fecha de la boda. Lo único que pide son las fotos que se
            | alternan dentro del marco.
            */
            $this->section(CountdownSection::class)
                ->example([
                    'images' => [
                        asset(self::IMG . 'contador-1.jpg'),
                        asset(self::IMG . 'galeria-1.jpg'),
                        asset(self::IMG . 'galeria-5.jpg'),
                    ],
                ]),

            /*
            | Aquí no se sube ninguna foto: el papel de relieve sobre el que se
            | escribe el formulario es del diseño y va fijo en el bloque.
            */
            $this->section(RsvpSection::class)
                ->blade('contact-form')
                ->first('rsvp_deadline', 'welcome_message', 'thank_you_message'),

            $this->section(FaqSection::class)
                ->example([
                    'items' => [
                        ['question' => '¿Es evento solo para adultos?', 'content' => 'Sí. Queremos que todos disfruten la noche sin prisas; agradecemos tu comprensión.'],
                        ['question' => '¿Cómo llego a San Miguel desde CDMX?', 'content' => 'Son unas tres horas y media por la carretera 57. También hay autobuses directos desde Terminal Norte.'],
                        ['question' => '¿El clima en marzo en San Miguel?', 'content' => 'Días templados de 22-26°C y noches frescas de 10-14°C. Lleva una capa para la recepción.'],
                        ['question' => '¿Hay estacionamiento disponible?', 'content' => 'Sí, la hacienda cuenta con estacionamiento gratuito y vigilado para todos nuestros invitados.'],
                        ['question' => '¿Política de fotos en la ceremonia?', 'content' => 'Te pedimos guardar el celular durante la ceremonia. Después, todas las fotos que quieras.'],
                    ],
                ]),
        ];
    }

    /**
     * Las dos del diseño, ambas en Google Fonts. Cormorant para los titulares y
     * Manrope para los datos. Del Cormorant se piden los tres pesos que usa el
     * diseño: 300 para la cuenta regresiva, 400 para casi todo y 500 para los
     * nombres de las tiendas.
     */
    public function fonts(): array
    {
        return [
            'https://fonts.googleapis.com/css2?family=Cormorant:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400&family=Manrope:wght@400;500&display=swap',
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
