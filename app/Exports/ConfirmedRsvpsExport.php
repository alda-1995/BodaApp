<?php

namespace App\Exports;

use App\Models\EventGuest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Los invitados que confirmaron una boda, para descargar en Excel.
 *
 * Recibe ya resueltas las invitaciones del evento: el reporte de un organizador
 * nunca debe poder traer las de otro.
 */
class ConfirmedRsvpsExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithCustomValueBinder
{
    /** @param  Collection<int, EventGuest>  $invitations */
    public function __construct(private readonly Collection $invitations)
    {
    }

    public function collection(): Collection
    {
        return $this->invitations->map(fn (EventGuest $invitation) => [
            $invitation->guest?->name ?? '',
            $invitation->guest?->email ?? '',
            $invitation->guest?->phone ?? '',
            $invitation->rsvp?->confirmed_passes ?? 0,
            $invitation->rsvp?->dietary_restrictions ?? '',
            $this->answers($invitation),
            $invitation->rsvp?->confirmed_at?->format('d/m/Y H:i') ?? '',
        ]);
    }

    public function headings(): array
    {
        return [
            'Invitado',
            'Correo',
            'Teléfono',
            'Asistentes',
            'Restricciones',
            'Respuestas',
            'Confirmado el',
        ];
    }

    /** El teléfono es texto: si no, Excel se come el signo y los ceros. */
    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getColumn() === 'C') {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    private function answers(EventGuest $invitation): string
    {
        return collect($invitation->rsvp?->answers ?? [])
            ->map(fn (string $answer, string $question) => "{$question}: {$answer}")
            ->implode("\n");
    }
}
