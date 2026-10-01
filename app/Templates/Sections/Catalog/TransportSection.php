<?php

namespace App\Templates\Sections\Catalog;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\InlineTextField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Controls\TextareaField;
use App\Templates\BlockType;

/**
 * Transporte: las corridas que la pareja pone para mover a sus invitados.
 */
class TransportSection extends Section
{
    public function key(): string
    {
        return 'transport';
    }

    public function title(): string
    {
        return 'Transporte';
    }

    public function blockType(): string
    {
        return BlockType::TRANSPORT;
    }

    public function fields(): array
    {
        return [
            'title' => TextField::make('title', 'Título de la sección')
                ->default('Transporte')
                ->required(),
            'message' => TextareaField::make('message', 'Mensaje para tus invitados')
                ->placeholder('Ej. Habrá camiones saliendo de los hoteles...')
                ->nullable(),
            'rides' => RepeaterField::make('rides', 'Corridas')
                ->sortable()
                ->schema([
                    'time' => InlineTextField::make('time', 'Hora')
                        ->placeholder('Ej. 16:15')
                        ->required(),
                    'route' => InlineTextField::make('route', 'Recorrido')
                        ->placeholder('Ej. Hoteles → Parroquia')
                        ->required(),
                    'detail' => InlineTextField::make('detail', 'Detalle')
                        ->placeholder('Ej. Camiones Coloniales. 15 min antes de la ceremonia.')
                        ->nullable(),
                ]),
        ];
    }

    public function data(array $values, array $all): array
    {
        $rides = collect($this->rows($values, 'rides'))
            ->map(fn (array $row) => [
                'time' => (string) ($row['time'] ?? ''),
                'route' => (string) ($row['route'] ?? ''),
                'detail' => (string) ($row['detail'] ?? ''),
            ])
            ->filter(fn (array $row) => filled($row['time']) || filled($row['route']))
            ->values()
            ->all();

        return [
            'title' => $this->text($values, 'title', 'Transporte'),
            'message' => $this->text($values, 'message'),
            'rides' => $rides,
        ];
    }

    public function demo(): array
    {
        return [
            'title' => 'Transporte',
            'message' => 'Habrá camiones saliendo de los hoteles del centro. No necesitas auto.',
            'rides' => [
                ['time' => '16:15', 'route' => 'Hoteles → Parroquia', 'detail' => 'Camiones Coloniales. 15 min antes de la ceremonia.'],
                ['time' => '19:30', 'route' => 'Parroquia → Casa de Sierra Nevada', 'detail' => 'Salidas cada 20 minutos.'],
                ['time' => '02:00', 'route' => 'Casa de Sierra Nevada → Hoteles', 'detail' => 'Última salida a las 03:00.'],
            ],
        ];
    }

    public function isVisible(array $data): bool
    {
        return $data['rides'] !== [];
    }
}
