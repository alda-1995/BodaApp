<?php

namespace App\Templates\Sections\Catalog;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\DateTimeField;
use App\FormBuilder\Controls\TextareaField;
use App\FormBuilder\Controls\TextField;
use App\Models\Event;
use App\Templates\BlockType;
use Carbon\Carbon;
use Closure;
use Exception;

/**
 * Portada: los nombres de la pareja, sus padres y la fecha.
 *
 * Los dos primeros momentos del itinerario (ceremonia y fiesta) se muestran
 * aquí también, así que los toma de esa sección en vez de volver a pedirlos.
 */
class GeneralSection extends Section
{
    public function key(): string
    {
        return 'general';
    }

    public function title(): string
    {
        return 'Información General';
    }

    public function blockType(): string
    {
        return BlockType::BANNER;
    }

    public function fields(): array
    {
        return [
            'name_event' => TextField::make('name_event', 'Nombre del evento')
                ->placeholder('Escribir el nombre del evento')
                ->required(),
            'name_wife' => TextField::make('name_wife', 'Nombre de la esposa')
                ->placeholder('Escribir el nombre de la esposa')
                ->required(),
            'name_husband' => TextField::make('name_husband', 'Nombre del esposo')
                ->placeholder('Escribir el nombre del esposo')
                ->required(),
            'family_parents' => TextareaField::make('family_parents', 'Padres de familia')
                ->placeholder('Escribe aquí')
                ->required(),
            'date_event_person' => DateTimeField::make('date_event_person', 'Fecha y hora del evento')
                ->placeholder('Seleccionar fecha y hora...')
                ->help('Tu invitación seguirá activa :days días después de la fecha del evento:until. Después se deshabilitará.')
                ->required(),
        ];
    }

    /**
     * La boda no puede ponerse en una fecha que ya pasó.
     *
     * No basta con 'after_or_equal:today': cuando la boda ya ocurrió y la
     * invitación sigue en pie —corren los días de vigencia posteriores—, el
     * organizador todavía entra a su panel, y al guardar este paso esa regla le
     * rechazaría su propia fecha, dejándolo sin poder tocar nada más. Así que
     * conservar la que ya tenía se permite; moverla al pasado, no.
     */
    public function rulesFor(Event $event): array
    {
        $guardada = $event->event_date?->toDateTimeString();

        return [
            'date_event_person' => [
                function (string $attribute, $value, Closure $fail) use ($guardada) {
                    if (blank($value)) {
                        return; // de lo vacío ya se queja el 'required'.
                    }

                    try {
                        $fecha = Carbon::parse($value);
                    } catch (Exception) {
                        return; // de lo que no es fecha ya se queja el 'date'.
                    }

                    if ($fecha->isToday() || $fecha->isFuture()) {
                        return;
                    }

                    if ($guardada !== null && $fecha->toDateTimeString() === $guardada) {
                        return; // es la que ya tenía: se le deja conservarla.
                    }

                    $fail('La fecha del evento no puede ser anterior a hoy.');
                },
            ],
        ];
    }

    public function modelAttributes(): array
    {
        return [
            'date_event_person' => 'event_date',
            'name_event' => 'title',
        ];
    }

    public function data(array $values, array $all): array
    {
        $moments = (new ItinerarySection())->data($all['itinerary'] ?? [], $all)['moments'] ?? [];

        return [
            'wife_name' => $this->text($values, 'name_wife', ''),
            'husband_name' => $this->text($values, 'name_husband', ''),
            'parents' => $this->text($values, 'family_parents'),
            'event_date' => $this->date($values['date_event_person'] ?? null),
            // Primer momento: la ceremonia. Segundo: la fiesta o el civil.
            'ceremony' => $moments[0] ?? null,
            'party' => $moments[1] ?? null,
        ];
    }

    public function demo(): array
    {
        $day = Carbon::now()->addDays(45);

        return [
            'wife_name' => 'Sofía',
            'husband_name' => 'Alejandro',
            'parents' => "Sr. Carlos Mendoza & Sra. Elena Ríos\nSr. Roberto Silva & Sra. Luisa Patri",
            'event_date' => $day->copy()->setTime(15, 30),
            'ceremony' => [
                'name' => 'Ceremonia religiosa',
                'place' => 'Catedral Metropolitana',
                'location' => 'Av. Principal #123, Col. Centro',
                'maps' => 'https://maps.google.com',
                'date' => $day->copy()->setTime(15, 30),
                'time' => '3:30 pm',
            ],
            'party' => [
                'name' => 'Ceremonia civil y fiesta',
                'place' => 'Jardín de Eventos Las Flores',
                'location' => 'Camino Real Km 4.5',
                'maps' => 'https://maps.google.com',
                'date' => $day->copy()->setTime(19, 30),
                'time' => '7:30 pm',
            ],
        ];
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        return filled($value) ? Carbon::parse((string) $value) : null;
    }
}
