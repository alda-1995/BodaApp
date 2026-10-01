<?php

namespace App\Templates\Sections\Catalog;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\InlineTextareaField;
use App\FormBuilder\Controls\InlineTextField;
use App\FormBuilder\Controls\RepeaterField;
use App\Templates\BlockType;
use App\Templates\Support\Autolink;

/**
 * Preguntas frecuentes.
 */
class FaqSection extends Section
{
    public function key(): string
    {
        return 'faqs';
    }

    public function title(): string
    {
        return 'Preguntas frecuentes';
    }

    public function blockType(): string
    {
        return BlockType::FAQ;
    }

    public function fields(): array
    {
        return [
            'faqs' => RepeaterField::make('faqs', 'Preguntas frecuentes')
                ->required()
                ->sortable()
                ->schema([
                    'question' => InlineTextField::make('question', 'Pregunta')
                        ->placeholder('Ej. ¿Se admiten niños?')
                        ->required(),
                    'content' => InlineTextareaField::make('content', 'Respuesta')
                        ->placeholder('Ej. Sí, todos son bienvenidos.')
                        ->help('Escribe normal: los correos, teléfonos y ligas que incluyas se vuelven enlaces en la invitación.')
                        ->required(),
                ]),
        ];
    }

    public function data(array $values, array $all): array
    {
        $items = collect($this->rows($values, 'faqs'))
            ->map(fn (array $row) => [
                'question' => (string) ($row['question'] ?? ''),
                'content' => (string) ($row['content'] ?? ''),
                'content_html' => Autolink::toHtml((string) ($row['content'] ?? '')),
            ])
            ->filter(fn (array $item) => filled($item['question']))
            ->values()
            ->all();

        return [
            'title' => 'Todo lo que necesitas saber para acompañarnos en este gran viaje',
            'items' => $items,
        ];
    }

    public function demo(): array
    {
        return [
            'title' => 'Todo lo que necesitas saber para acompañarnos en este gran viaje',
            'items' => collect([
                ['question' => '¿Se admiten niños?', 'content' => 'Sí, todos son bienvenidos.'],
                ['question' => '¿Hay estacionamiento?', 'content' => 'El hotel cuenta con valet parking sin costo.'],
                [
                    'question' => '¿Dónde me hospedo?',
                    'content' => "Código de reservación en Hotel Barceló Santa Fé: Boda Alfonso y Elena\n"
                        . "Reservaciones vía e-mail: mexicosantafe.res@barcelo.com\n"
                        . 'Reservaciones vía telefónica: 55 5004 1616 Ext. 2812',
                ],
                ['question' => '¿Hasta cuándo puedo confirmar?', 'content' => 'Un mes antes de la boda.'],
            ])->map(fn (array $item) => $item + ['content_html' => Autolink::toHtml($item['content'])])->all(),
        ];
    }

    public function isVisible(array $data): bool
    {
        return $data['items'] !== [];
    }
}
