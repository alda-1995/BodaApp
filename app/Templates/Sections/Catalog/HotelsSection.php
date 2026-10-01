<?php

namespace App\Templates\Sections\Catalog;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\InlineTextField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Controls\TextareaField;
use App\Templates\BlockType;

/**
 * Hoteles recomendados: dónde dormir y con qué código reservar.
 *
 * Es de las cosas que más preguntan los invitados de fuera, así que la tarifa y
 * el código van visibles en la propia invitación.
 */
class HotelsSection extends Section
{
    public function key(): string
    {
        return 'hotels';
    }

    public function title(): string
    {
        return 'Hoteles recomendados';
    }

    public function blockType(): string
    {
        return BlockType::HOTELS;
    }

    public function fields(): array
    {
        return [
            'title' => TextField::make('title', 'Título de la sección')
                ->default('Hoteles recomendados')
                ->required(),
            'message' => TextareaField::make('message', 'Mensaje para tus invitados')
                ->placeholder('Ej. Apartamos habitaciones con tarifa especial...')
                ->nullable(),
            'hotels' => RepeaterField::make('hotels', 'Hoteles')
                ->sortable()
                ->schema([
                    'name' => InlineTextField::make('name', 'Nombre')
                        ->placeholder('Ej. Casa de Sierra Nevada')
                        ->required(),
                    'address' => InlineTextField::make('address', 'Dirección')
                        ->placeholder('Ej. Hospicio 35, Centro')
                        ->nullable(),
                    'rate' => InlineTextField::make('rate', 'Tarifa')
                        ->placeholder('Ej. $4,800 MXN/noche')
                        ->nullable(),
                    'code' => InlineTextField::make('code', 'Código de reservación')
                        ->placeholder('Ej. BODASA2026')
                        ->nullable(),
                    'url' => InlineTextField::make('url', 'Liga para reservar')
                        ->placeholder('https://...')
                        ->nullable(),
                    'image' => ImageUploadField::make('image', 'Foto del hotel')
                        ->maxSize(5120)
                        ->allowedMimes(['jpg', 'jpeg', 'png', 'webp'])
                        ->nullable(),
                ]),
        ];
    }

    public function data(array $values, array $all): array
    {
        $hotels = collect($this->rows($values, 'hotels'))
            ->map(fn (array $row) => [
                'name' => (string) ($row['name'] ?? ''),
                'address' => (string) ($row['address'] ?? ''),
                'rate' => (string) ($row['rate'] ?? ''),
                'code' => (string) ($row['code'] ?? ''),
                'url' => $row['url'] ?? null,
                'image' => $this->imageUrl($row['image'] ?? null),
            ])
            ->filter(fn (array $row) => filled($row['name']))
            ->values()
            ->all();

        return [
            'title' => $this->text($values, 'title', 'Hoteles recomendados'),
            'message' => $this->text($values, 'message'),
            'hotels' => $hotels,
        ];
    }

    public function demo(): array
    {
        return [
            'title' => 'Hoteles recomendados',
            'message' => 'Apartamos habitaciones con tarifa especial. Menciona el código al reservar.',
            'hotels' => [
                [
                    'name' => 'Casa de Sierra Nevada',
                    'address' => 'Hospicio 35, Centro · San Miguel',
                    'rate' => '$4,800 MXN/noche',
                    'code' => 'BODASA2026',
                    'url' => null,
                    'image' => null,
                ],
                [
                    'name' => 'Hotel Matilda',
                    'address' => 'Aldama 53, Centro · San Miguel',
                    'rate' => '$3,200 MXN/noche',
                    'code' => 'SOAND26',
                    'url' => null,
                    'image' => null,
                ],
                [
                    'name' => 'Rosewood San Miguel',
                    'address' => 'Nemesio Díez 11, Centro · San Miguel',
                    'rate' => '$2,400 MXN/noche',
                    'code' => 'BODA-SMA26',
                    'url' => null,
                    'image' => null,
                ],
            ],
        ];
    }

    public function isVisible(array $data): bool
    {
        return $data['hotels'] !== [];
    }
}
