<?php

namespace App\Templates\Sections\Catalog;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\InlineTextareaField;
use App\FormBuilder\Controls\InlineTextField;
use App\FormBuilder\Controls\RepeaterField;
use App\FormBuilder\Controls\TextField;
use App\FormBuilder\Controls\TextareaField;
use App\Templates\BlockType;

/**
 * Historia contada como línea de tiempo: un año y lo que pasó en él.
 *
 * Es otra forma del bloque "historia": donde una plantilla pone una caja de
 * recuerdos, esta pone los años que llevan juntos.
 */
class MilestonesSection extends Section
{
    public function key(): string
    {
        return 'milestones';
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
                ->default('Nuestra historia')
                ->required(),
            'intro' => TextareaField::make('intro', 'Texto de introducción')
                ->placeholder('Cuéntales en un párrafo cómo empezó todo...')
                ->nullable(),
            'milestones' => RepeaterField::make('milestones', 'Años de su historia')
                ->sortable()
                ->schema([
                    'year' => InlineTextField::make('year', 'Año')
                        ->placeholder('Ej. 2017')
                        ->required(),
                    // La foto de ese año: aparece al pasar por él en la línea.
                    'image' => ImageUploadField::make('image', 'Foto de ese año')
                        ->maxSize(5120)
                        ->allowedMimes(['jpg', 'jpeg', 'png', 'webp'])
                        ->nullable()
                        ->messages([
                            'image' => 'El archivo debe ser una imagen válida.',
                            'mimes' => 'La imagen debe ser de formato: jpg, jpeg, png o webp.',
                            'max' => 'La imagen no debe pesar más de 5MB.',
                        ]),
                ]),
        ];
    }

    public function data(array $values, array $all): array
    {
        $milestones = collect($this->rows($values, 'milestones'))
            ->map(fn (array $row) => [
                'year' => (string) ($row['year'] ?? ''),
                'image' => $this->imageUrl($row['image'] ?? null),
            ])
            ->filter(fn (array $row) => filled($row['year']))
            ->values()
            ->all();

        return [
            'title' => $this->text($values, 'title', 'Nuestra historia'),
            'intro' => $this->text($values, 'intro'),
            'milestones' => $milestones,
        ];
    }

    public function demo(): array
    {
        return [
            'title' => 'Nuestra historia',
            'intro' => 'Nos conocimos una tarde cualquiera y desde entonces cada año trajo algo que contar. Estos son los que nos trajeron hasta aquí.',
            'milestones' => [
                ['year' => '1998', 'image' => asset('images/assets-editorial/historia-foto.png')],
                ['year' => '2004', 'image' => asset('images/assets-editorial/galeria-1.png')],
                ['year' => '2017', 'image' => asset('images/assets-editorial/galeria-2.png')],
                ['year' => '2022', 'image' => asset('images/assets-editorial/galeria-3.png')],
                ['year' => '2026', 'image' => asset('images/assets-editorial/evento-1.png')],
            ],
        ];
    }

    public function isVisible(array $data): bool
    {
        return $data['milestones'] !== [] || filled($data['intro']);
    }
}
