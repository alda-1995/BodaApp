<?php

namespace App\Templates\Sections\Catalog;

use App\Models\Event;
use App\Templates\Sections\Section;

use App\FormBuilder\Controls\BooleanField;
use App\FormBuilder\Controls\DateTimeField;
use App\FormBuilder\Controls\InlineTextField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\TextareaField;
use App\Services\EventSettingsService;
use App\Templates\BlockType;
use Carbon\Carbon;

/**
 * Formulario de confirmación de asistencia.
 *
 * Lo que el organizador ajusta en Configuración (si admite niños y el tope de
 * acompañantes del link abierto) también vive en este paso de features.
 */
class RsvpSection extends Section
{
    public function key(): string
    {
        return 'rsvp';
    }

    public function title(): string
    {
        return 'Formulario de confirmación';
    }

    public function blockType(): string
    {
        return BlockType::CONTACT_FORM;
    }

    /**
     * La fecha límite no puede pasarse de la boda.
     *
     * Confirmar después de la boda no sirve de nada: para entonces ya hubo que
     * cerrar el banquete y repartir los lugares. La fecha de la boda no está en
     * este paso —se captura en el primero y vive en events.event_date— así que
     * la regla no cabe en el campo y se declara aquí.
     *
     * Se compara contra el final de ese día y no contra su hora: el organizador
     * puede poner como límite el día de la boda, y a nadie le importa si lo
     * pone a las 23:00 y la ceremonia es a las 15:30.
     *
     * Sin fecha de boda todavía no hay contra qué comparar: el paso uno está a
     * medias y ahí se le va a exigir de todos modos.
     */
    public function rulesFor(Event $event): array
    {
        if (!$event->event_date) {
            return [];
        }

        return [
            'rsvp_deadline' => ['before_or_equal:' . $event->event_date->copy()->endOfDay()->toDateTimeString()],
        ];
    }

    /**
     * Si movieron la boda a antes de la fecha límite, la límite se recorre.
     *
     * Queda en el mismo día y hora de la boda, que es el último momento válido,
     * y se le avisa al organizador para que la ponga donde de verdad la quiere:
     * confirmar el día de la boda casi nunca es lo que pretendía.
     */
    public function reconcile(Event $event, string $savedStep): ?string
    {
        // Al guardar este mismo paso ya lo revisó la validación.
        if ($savedStep === $this->key() || !$event->event_date) {
            return null;
        }

        $features = is_array($event->features) ? $event->features : [];
        $limite = $features['rsvp']['rsvp_deadline'] ?? null;

        if (blank($limite) || Carbon::parse($limite)->lte($event->event_date->copy()->endOfDay())) {
            return null;
        }

        $features['rsvp']['rsvp_deadline'] = $event->event_date->format('Y-m-d H:i:s');
        $event->features = $features;

        return 'Tu nueva fecha de boda es anterior a la fecha límite para confirmar, '
            . 'así que la recorrimos al día de la boda. Revísala en el paso "Formulario de confirmación".';
    }

    public function fields(): array
    {
        return [
            'rsvp_deadline' => DateTimeField::make('rsvp_deadline', 'Fecha límite para confirmar')
                ->placeholder('Seleccionar fecha límite...')
                ->help('No puede ser después del día de tu boda.')
                ->required()
                // La compara contra la fecha de la boda rulesFor(), aquí abajo.
                ->messages([
                    'before_or_equal' => 'La fecha límite para confirmar no puede ser después del día de tu boda.',
                ]),
            'welcome_message' => TextareaField::make('welcome_message', 'Mensaje de bienvenida al formulario')
                ->default('Esperamos contar con tu asistencia, agradecemos tu confirmación antes de la fecha indicada.')
                ->placeholder('Escribe un mensaje de bienvenida...')
                ->required(),
            /*
            | Este interruptor manda sobre todo lo que se le pregunta al
            | invitado además de si asiste. Apagado, el formulario queda con lo
            | mínimo: nombre, contacto y si viene.
            |
            | La llave conserva el nombre viejo —'ask_dietary_requirements'— a
            | propósito: es la que ya está guardada en las bodas existentes, y
            | renombrarla las dejaría sin su respuesta.
            */
            'ask_dietary_requirements' => BooleanField::make('ask_dietary_requirements', 'Hacerle preguntas a los invitados')
                ->help('Las que agregues abajo aparecerán en su formulario de confirmación.')
                ->default(true),
            /*
            | Va pegado a su interruptor: aparece y desaparece con él, así que
            | ponerlo en otro lugar del paso haría que algo saltara lejos de
            | donde el organizador acaba de hacer clic.
            */
            'custom_questions' => RepeaterField::make('custom_questions', 'Preguntas personalizadas (opcionales)')
                ->sortable()
                ->dependsOn('ask_dietary_requirements', true)
                ->schema([
                    'question' => InlineTextField::make('question', 'Pregunta')
                        ->placeholder('Ej. ¿Tienes alguna restricción alimentaria?')
                        ->required(),
                    /*
                    | Va bajo la pregunta en la invitación, para decirle al
                    | invitado qué se espera que conteste. Es opcional: una
                    | pregunta clara no necesita explicación.
                    */
                    'description' => InlineTextField::make('description', 'Aclaración (opcional)')
                        ->placeholder('Ej. Sin gluten, alergias, vegetariano...')
                        ->nullable(),
                ]),
            'enable_open_confirmation_link' => BooleanField::make('enable_open_confirmation_link', 'Habilitar link abierto de confirmación (para invitados sin invitación personalizada)')
                ->default(true),
            'thank_you_message' => TextareaField::make('thank_you_message', 'Mensaje de agradecimiento tras confirmar')
                ->default('¡Gracias por confirmar! Nos hace muy felices contar contigo en este día tan especial.')
                ->placeholder('Escribe un mensaje de agradecimiento...')
                ->required(),
        ];
    }

    public function data(array $values, array $all): array
    {
        $deadline = filled($values['rsvp_deadline'] ?? null)
            ? Carbon::parse($values['rsvp_deadline'])
            : null;

        $preguntar = $this->boolean($values, 'ask_dietary_requirements', true);

        /*
        | Con las preguntas apagadas no se pinta ninguna, aunque queden guardadas
        | de antes: así el organizador puede apagarlas y volverlas a encender sin
        | tener que escribirlas otra vez.
        */
        /*
        | Cada pregunta viaja con su aclaración. La llave de la respuesta sigue
        | siendo el texto de la pregunta, así que lo ya contestado no se mueve
        | de sitio.
        */
        $questions = $preguntar
            ? collect($this->rows($values, 'custom_questions'))
                ->map(fn (array $row) => [
                    'question' => trim((string) ($row['question'] ?? '')),
                    'description' => trim((string) ($row['description'] ?? '')) ?: null,
                ])
                ->filter(fn (array $row) => $row['question'] !== '')
                ->values()
                ->all()
            : [];

        return [
            'deadline' => $deadline,
            'welcome_message' => $this->text($values, 'welcome_message'),
            'thank_you_message' => $this->text($values, 'thank_you_message'),
            'ask_dietary_requirements' => $preguntar,
            'open_link_enabled' => $this->boolean($values, 'enable_open_confirmation_link', true),
            'allow_children' => $this->boolean($values, 'allow_children', true),
            'open_link_max_passes' => (int) ($values['open_link_max_passes'] ?? EventSettingsService::DEFAULT_OPEN_LINK_MAX_PASSES),
            'custom_questions' => $questions,
            // Pasada la fecha límite el formulario deja de recibir respuestas.
            'closed' => $deadline?->isPast() ?? false,
            'calendar' => $this->calendar($all),
        ];
    }

    /**
     * Lo que necesitan los botones de "agregar a mi calendario": el evento se
     * arma con la pareja y el primer momento del itinerario.
     *
     * @return array<string, mixed>
     */
    private function calendar(array $all): array
    {
        $general = $all['general'] ?? [];

        $first = collect($this->rows($all['itinerary'] ?? [], 'events'))
            ->map(fn (array $row) => $row + [
                'starts_at' => filled($row['date_event'] ?? null) ? Carbon::parse($row['date_event']) : null,
            ])
            ->sortBy(fn (array $row) => $row['starts_at']?->timestamp ?? PHP_INT_MAX)
            ->first() ?? [];

        $couple = trim(implode(' y ', array_filter([
            $general['name_wife'] ?? null,
            $general['name_husband'] ?? null,
        ])));

        return [
            'title' => filled($couple) ? "Boda de {$couple}" : 'Nuestra boda',
            'description' => 'Nos encantará contar contigo en este día.',
            'location' => trim(($first['place_event'] ?? '') . ' ' . ($first['location_event'] ?? '')),
            'starts_at' => $first['starts_at'] ?? null,
        ];
    }

    public function demo(): array
    {
        return [
            'deadline' => Carbon::now()->addDays(30),
            'welcome_message' => 'Esperamos contar con tu asistencia, agradecemos tu confirmación antes de la fecha indicada.',
            'thank_you_message' => '¡Gracias por confirmar! Nos hace muy felices contar contigo en este día tan especial.',
            'ask_dietary_requirements' => true,
            'open_link_enabled' => true,
            'allow_children' => true,
            'open_link_max_passes' => EventSettingsService::DEFAULT_OPEN_LINK_MAX_PASSES,
            'custom_questions' => [
                [
                    'question' => '¿Alguna restricción alimentaria?',
                    'description' => 'Sin gluten, alergias, vegetariano...',
                ],
                [
                    'question' => '¿Con qué canción te pararías a bailar?',
                    'description' => null,
                ],
            ],
            'closed' => false,
            'calendar' => [
                'title' => 'Boda de Sofía y Alejandro',
                'description' => 'Nos encantará contar contigo en este día.',
                'location' => 'Hacienda San Gabriel, Cuernavaca',
                'starts_at' => Carbon::now()->addDays(45)->setTime(15, 30),
            ],
        ];
    }
}
