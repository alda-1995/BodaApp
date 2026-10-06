<?php

namespace App\Templates\Sections\Catalog;

use App\FormBuilder\Controls\TextField;
use App\Templates\BlockType;
use App\Templates\Sections\Section;
use Carbon\Carbon;

/**
 * El destino de la boda, contado como un viaje: a dónde se va, cuánto falta y
 * en qué vuelo se llega.
 *
 * La cuenta regresiva no se pregunta: sale de la fecha de la boda, que ya se
 * capturó en información general. Preguntarla sería pedir dos veces el mismo
 * dato y dejar que se contradigan.
 */
class DestinationSection extends Section
{
    public function key(): string
    {
        return 'destination';
    }

    public function title(): string
    {
        return 'Destino';
    }

    public function blockType(): string
    {
        return BlockType::DESTINATION;
    }

    public function fields(): array
    {
        return [
            'eyebrow' => TextField::make('eyebrow', 'Texto pequeño sobre la ciudad')
                ->placeholder('Ej. Siguiente parada')
                ->default('Siguiente parada')
                ->required()
                ->rules(['string', 'max:60']),

            'city' => TextField::make('city', 'Ciudad del destino')
                ->placeholder('Ej. Mérida')
                ->help('El nombre grande que anuncia a dónde viajan todos.')
                ->required()
                ->rules(['string', 'max:80']),

            /*
            | La ruta del viaje es el guiño del diseño. Cabe el código de tres
            | letras del aeropuerto, pero también el nombre de la ciudad: no
            | toda boda se nombra con códigos, y el límite corto obligaba a
            | abreviar. Las mayúsculas las pone el CSS, así que aquí se guarda
            | tal como se escriba.
            |
            | Son opcionales: a una boda a la que se llega en coche no hay que
            | inventarle un vuelo, y entonces la línea no se pinta.
            */
            'origin_code' => TextField::make('origin_code', 'Desde dónde viajan')
                ->placeholder('Ej. MEX, o Ciudad de México')
                ->help('El código del aeropuerto o el nombre de la ciudad desde donde viaja la mayoría. Opcional.')
                ->nullable()
                ->rules(['string', 'max:40']),

            'destination_code' => TextField::make('destination_code', 'Hacia dónde viajan')
                ->placeholder('Ej. MID, o Mérida')
                ->nullable()
                ->rules(['string', 'max:40']),
        ];
    }

    public function data(array $values, array $all): array
    {
        $origen = $this->code($values, 'origin_code');
        $destino = $this->code($values, 'destination_code');

        return [
            'eyebrow' => $this->text($values, 'eyebrow', 'Siguiente parada'),
            'city' => $this->text($values, 'city'),
            'origin_code' => $origen,
            'destination_code' => $destino,
            // El vuelo necesita las dos puntas: media ruta no se puede dibujar.
            'route' => $origen && $destino ? "{$origen} → {$destino}" : null,
            // La fecha vive en información general; aquí sólo se lee.
            'event_date' => $this->eventDate($all),
        ];
    }

    public function demo(): array
    {
        return [
            'eyebrow' => 'Siguiente parada',
            'city' => 'Mérida',
            'origin_code' => 'MEX',
            'destination_code' => 'MID',
            'route' => 'MEX → MID',
            'event_date' => Carbon::now()->addDays(36)->addHours(8)->addMinutes(42),
        ];
    }

    public function isVisible(array $data): bool
    {
        return filled($data['city']);
    }

    /** Códigos de aeropuerto siempre en mayúsculas, se escriban como se escriban. */
    private function code(array $values, string $key): ?string
    {
        $value = $this->text($values, $key);

        /*
        | Sólo se recortan los espacios. Las mayúsculas son cosa del diseño y
        | las pone el CSS: gritar aquí un nombre de ciudad —"CIUDAD DE
        | MÉXICO"— lo dejaría así guardado para cualquier plantilla que algún
        | día use este dato.
        */
        return $value === null ? null : trim($value);
    }

    private function eventDate(array $all): ?Carbon
    {
        $raw = $all['general']['date_event_person'] ?? null;

        if (blank($raw)) {
            return null;
        }

        try {
            return $raw instanceof Carbon ? $raw : Carbon::parse($raw);
        } catch (\Throwable) {
            // Una fecha ilegible no debe tumbar la invitación: se pinta sin cuenta.
            return null;
        }
    }
}
