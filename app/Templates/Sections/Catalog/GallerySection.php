<?php

namespace App\Templates\Sections\Catalog;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\BooleanField;
use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\TextareaField;
use App\Templates\BlockType;

/**
 * Galería de fotos de la pareja.
 */
class GallerySection extends Section
{
    public function key(): string
    {
        return 'gallery';
    }

    public function title(): string
    {
        return 'Galería de fotos';
    }

    public function blockType(): string
    {
        return BlockType::GALLERY;
    }

    public function fields(): array
    {
        return [
            'guest_photos_message' => TextareaField::make('guest_photos_message', 'Mensaje para invitados sobre compartir fotos')
                ->default('')
                ->placeholder('Escribe un mensaje para tus invitados...')
                ->required(),
            'allow_guest_uploads' => BooleanField::make('allow_guest_uploads', 'Permitir que los invitados suban fotos desde el sitio')
                ->default(true),
            'photos' => RepeaterField::make('photos', 'Fotos de la pareja')
                ->schema([
                    'image' => ImageUploadField::make('image', 'Imagen')
                        ->maxSize(5120)
                        ->allowedMimes(['jpg', 'jpeg', 'png', 'webp'])
                        ->required()
                        ->messages([
                            'image' => 'El archivo debe ser una imagen válida.',
                            'mimes' => 'La imagen debe ser de formato: jpg, jpeg, png o webp.',
                            'max' => 'La imagen no debe pesar más de 5MB.',
                        ]),
                ]),
        ];
    }

    public function data(array $values, array $all): array
    {
        $images = collect($this->rows($values, 'photos'))
            ->map(fn (array $row) => $this->imageUrl($row['image'] ?? null))
            ->filter()
            ->values()
            ->all();

        return [
            'message' => $this->text($values, 'guest_photos_message'),
            'allow_guest_uploads' => $this->boolean($values, 'allow_guest_uploads', true),
            'images' => $images,
        ];
    }

    public function demo(): array
    {
        return [
            'message' => 'Comparte con nosotros las fotos que tomes durante la boda.',
            'allow_guest_uploads' => true,
            'images' => [
                asset('images/assets-travel/gallery/1.jpeg'),
                asset('images/assets-travel/gallery/2.jpg'),
                asset('images/assets-travel/gallery/3.jpg'),
                asset('images/assets-travel/gallery/4.jpg'),
                asset('images/assets-travel/gallery/5.jpg'),
                asset('images/assets-travel/gallery/6.jpg'),
                asset('images/assets-travel/gallery/7.jpg'),
                asset('images/assets-travel/gallery/8.jpeg'),
            ],
        ];
    }

    public function isVisible(array $data): bool
    {
        return $data['images'] !== [] || filled($data['message']);
    }
}
