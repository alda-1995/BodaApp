<?php

namespace App\Templates\Sections\Travel;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\TextareaField;
use App\FormBuilder\Controls\TextField;
use App\Templates\BlockType;

/**
 * Historia de la pareja: el título y el subtítulo los captura el organizador;
 * la caja y su texto ("Toca la caja") son parte del diseño de la plantilla.
 */
class HistorySection extends Section
{
    public function key(): string
    {
        return 'history';
    }

    public function title(): string
    {
        return 'Nuestra historia';
    }

    public function blockType(): string
    {
        return BlockType::STORY;
    }

    public function fields(): array
    {
        return [
            'title' => TextField::make('title', 'Título de la sección')
                ->default('Un poco de nuestra historia')
                ->placeholder('Un poco de nuestra historia')
                ->required(),
            'subtitle' => TextareaField::make('subtitle', 'Subtítulo')
                ->default('Nuestro amor, lleno de ingredientes únicos y momentos especiados...')
                ->placeholder('Escribe una frase sobre su historia...')
                ->required(),
        ];
    }

    public function data(array $values, array $all): array
    {
        return [
            'title' => $this->text($values, 'title', 'Un poco de nuestra historia'),
            'subtitle' => $this->text(
                $values,
                'subtitle',
                'Nuestro amor, lleno de ingredientes únicos y momentos especiados...',
            ),
            'action_text' => $this->text($values, 'action_text', '(Toca la caja)'),
        ];
    }

    public function demo(): array
    {
        return $this->data([], []);
    }
}
