<?php

namespace App\Templates\Sections\Catalog;

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

    public function fields(): array
    {
        return [
            'rsvp_deadline' => DateTimeField::make('rsvp_deadline', 'Fecha límite para confirmar')
                ->placeholder('Seleccionar fecha límite...')
                ->required(),
            'welcome_message' => TextareaField::make('welcome_message', 'Mensaje de bienvenida al formulario')
                ->default('Esperamos contar con tu asistencia, agradecemos tu confirmación antes de la fecha indicada.')
                ->placeholder('Escribe un mensaje de bienvenida...')
                ->required(),
            'ask_dietary_requirements' => BooleanField::make('ask_dietary_requirements', 'Preguntar por restricciones alimenticias')
                ->default(true),
            'enable_open_confirmation_link' => BooleanField::make('enable_open_confirmation_link', 'Habilitar link abierto de confirmación (para invitados sin invitación personalizada)')
                ->default(true),
            'custom_questions' => RepeaterField::make('custom_questions', 'Preguntas personalizadas (opcionales)')
                ->sortable()
                ->schema([
                    'question' => InlineTextField::make('question', 'Pregunta')
                        ->placeholder('Ej. ¿Con qué canción te pararías a bailar?')
                        ->required(),
                ]),
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

        $questions = collect($this->rows($values, 'custom_questions'))
            ->map(fn (array $row) => (string) ($row['question'] ?? ''))
            ->filter()
            ->values()
            ->all();

        return [
            'deadline' => $deadline,
            'welcome_message' => $this->text($values, 'welcome_message'),
            'thank_you_message' => $this->text($values, 'thank_you_message'),
            'ask_dietary_requirements' => $this->boolean($values, 'ask_dietary_requirements', true),
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
            'custom_questions' => ['¿Con qué canción te pararías a bailar?'],
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
