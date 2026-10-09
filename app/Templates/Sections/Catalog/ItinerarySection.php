<?php

namespace App\Templates\Sections\Catalog;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\DateTimeField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Controls\UrlField;
use App\Templates\BlockType;
use Carbon\Carbon;

/**
 * Momentos del día: ceremonia, civil, fiesta...
 */
class ItinerarySection extends Section
{
    public function key(): string
    {
        return 'itinerary';
    }

    public function title(): string
    {
        return 'Itinerario';
    }

    public function blockType(): string
    {
        return BlockType::TIMELINE;
    }

    public function fields(): array
    {
        return [
            'events' => RepeaterField::make('events', 'Momentos del Itinerario')
                // El orden que deje el organizador es el que se pinta.
                ->sortable()
                ->schema([
                    'name' => TextField::make('name', 'Nombre del momento')
                        ->placeholder('Ej. Boda Ceremonial, Recepción')
                        ->rules(['required', 'string', 'max:255']),
                    'place_event' => TextField::make('place_event', 'Lugar del evento')
                        ->placeholder('Ej. Iglesia de Nuestra Señora de Covadonga')
                        ->required(),
                    'location_event' => TextField::make('location_event', 'Ubicación')
                        ->placeholder('Ej. Av. Paseo de las Palmas 406...')
                        ->required(),
                    'location_maps' => UrlField::make('location_maps', 'Ubicación Google Maps')
                        ->placeholder('https://maps.app.goo.gl/...')
                        ->nullable(),
                    'date_event' => DateTimeField::make('date_event', 'Fecha y hora del evento')
                        ->placeholder('Seleccionar fecha y hora...')
                        ->required(),
                ]),
        ];
    }

    public function data(array $values, array $all): array
    {
        $moments = collect($this->rows($values, 'events'))
            ->map(function (array $row) {
                $date = filled($row['date_event'] ?? null) ? Carbon::parse($row['date_event']) : null;

                return [
                    'name' => $row['name'] ?? '',
                    'place' => $row['place_event'] ?? '',
                    'location' => $row['location_event'] ?? '',
                    'maps' => $row['location_maps'] ?? null,
                    // Sólo la piden los diseños que la añaden al repetidor; en
                    // los demás llega null y su bloque ni la mira.
                    'photo' => $this->imageUrl($row['photo'] ?? null),
                    'date' => $date,
                    // Hora corta ("3:30 pm"): en español saldría "3:30 p. m.".
                    'time' => $date?->locale('en')->isoFormat('h:mm a'),
                ];
            })
            ->filter(fn (array $moment) => filled($moment['name']) || filled($moment['place']))
            /*
            | Manda el orden del wizard, no la hora.
            |
            | Antes se reordenaba por fecha, y eso ignoraba lo que el organizador
            | había puesto: movía una fila y la invitación salía igual. Ahora las
            | filas se pueden arrastrar y el orden que deje es el que se pinta,
            | aunque no sea el cronológico: hay bodas donde el civil se cuenta
            | aparte, o donde se quiere abrir con la fiesta.
            */
            ->values()
            ->all();

        return ['moments' => $moments];
    }

    public function demo(): array
    {
        $day = Carbon::now()->addDays(45);

        return [
            'moments' => [
                [
                    'name' => 'Ceremonia religiosa',
                    'place' => 'Iglesia de Nuestra Señora de Covadonga',
                    'location' => 'Av. Paseo de las Palmas 406',
                    'maps' => 'https://maps.google.com',
                    'date' => $day->copy()->setTime(15, 30),
                    'time' => '3:30 pm',
                ],
                [
                    'name' => 'Ceremonia civil',
                    'place' => 'Roof Top Hotel Barceló Santa Fé, Piso KYO',
                    'location' => 'Av. Santa Fe 1, Ciudad de México',
                    'maps' => 'https://maps.google.com',
                    'date' => $day->copy()->setTime(18, 15),
                    'time' => '6:15 pm',
                ],
                [
                    'name' => 'Cena y fiesta',
                    'place' => 'Hotel Barceló Santa Fé, Salón México III',
                    'location' => 'Av. Santa Fe 1, Ciudad de México',
                    'maps' => null,
                    'date' => $day->copy()->setTime(20, 0),
                    'time' => '8:00 pm',
                ],
            ],
        ];
    }

    public function isVisible(array $data): bool
    {
        return count($data['moments']) > 0;
    }
}
