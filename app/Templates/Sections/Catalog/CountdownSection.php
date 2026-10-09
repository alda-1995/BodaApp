<?php

namespace App\Templates\Sections\Catalog;

use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\RepeaterField;
use App\Templates\BlockType;
use App\Templates\Sections\Section;

/**
 * Cuenta regresiva: cuánto falta para la boda, con una o varias fotos.
 *
 * La cuenta no se pregunta —sale sola de la fecha de la boda— así que este paso
 * sólo pide las fotos. Si suben más de una, el bloque las va cambiando en bucle;
 * con una sola se queda quieta, y sin ninguna se lee la cuenta a secas.
 */
class CountdownSection extends Section
{
    public function key(): string
    {
        return 'countdown';
    }

    public function title(): string
    {
        return 'Cuenta regresiva';
    }

    public function blockType(): string
    {
        return BlockType::COUNTDOWN;
    }

    public function fields(): array
    {
        return [
            'photos' => RepeaterField::make('photos', 'Fotos')
                ->help('Si subes varias, se van alternando solas. Con una sola se queda fija.')
                // El orden en que se alternan es el de esta lista.
                ->sortable()
                ->nullable()
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

        return ['images' => $images];
    }

    public function demo(): array
    {
        return ['images' => []];
    }

    /**
     * Siempre se pinta: aunque no suban ninguna foto, la cuenta regresiva vale
     * por sí sola y es lo que de verdad trae este bloque.
     */
    public function isVisible(array $data): bool
    {
        return true;
    }
}
