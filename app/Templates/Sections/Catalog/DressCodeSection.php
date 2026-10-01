<?php

namespace App\Templates\Sections\Catalog;

use App\Templates\Sections\Section;

use App\FormBuilder\Controls\ImageUploadField;
use App\FormBuilder\Controls\SelectField;
use App\FormBuilder\Controls\TextareaField;
use App\FormBuilder\Controls\TextField;
use App\Templates\BlockType;

/**
 * Código de vestimenta.
 */
class DressCodeSection extends Section
{
    public function key(): string
    {
        return 'dress_code';
    }

    public function title(): string
    {
        return 'Código de vestimenta';
    }

    public function blockType(): string
    {
        return BlockType::DRESS_CODE;
    }

    public function fields(): array
    {
        return [
            'dress_code_type' => SelectField::make('dress_code_type', 'Tipo de vestimenta')
                ->placeholder('Elige una opción')
                ->options([
                    'Formal' => 'Formal',
                    'Rigurosa Etiqueta' => 'Rigurosa Etiqueta',
                    'Etiqueta' => 'Etiqueta',
                    'Semicasual' => 'Semicasual',
                    'Casual' => 'Casual',
                    'Playa / Guayabera' => 'Playa / Guayabera',
                ])
                ->default('Formal')
                ->required(),
            'color_or_theme' => TextField::make('color_or_theme', 'Color o tema (opcional)')
                ->placeholder('Ej. Tonos tierra, Pastel, etc.')
                ->nullable(),
            'women_attire' => TextareaField::make('women_attire', 'Vestimenta para mujeres')
                ->placeholder('Ej. Vestido largo o midi, en tonos tierra o neutros. Evitar blanco.')
                ->nullable(),
            'men_attire' => TextareaField::make('men_attire', 'Vestimenta para hombres')
                ->placeholder('Ej. Traje o guayabera en tonos neutros. Evitar jeans y tenis.')
                ->nullable(),
            'reference_image' => ImageUploadField::make('reference_image', 'Imagen de referencia')
                ->maxSize(5120)
                ->allowedMimes(['jpg', 'jpeg', 'png', 'webp'])
                ->nullable()
                ->messages([
                    'image' => 'El archivo debe ser una imagen válida.',
                    'mimes' => 'La imagen debe ser de formato: jpg, jpeg, png o webp.',
                    'max' => 'La imagen no debe pesar más de 5MB.',
                ]),
        ];
    }

    public function data(array $values, array $all): array
    {
        return [
            'type' => $this->text($values, 'dress_code_type'),
            'color_or_theme' => $this->text($values, 'color_or_theme'),
            'women_attire' => $this->text($values, 'women_attire'),
            'men_attire' => $this->text($values, 'men_attire'),
            'reference_image' => $this->imageUrl($values['reference_image'] ?? null),
        ];
    }

    public function demo(): array
    {
        return [
            'type' => 'Formal / Traje coctel',
            'color_or_theme' => 'Tonos tierra',
            'women_attire' => 'Vestido largo o de coctel. El evento cuenta con zona de pasto.',
            'men_attire' => 'Traje completo oscuro, corbata opcional. Evitar tenis deportivos.',
            'reference_image' => null,
        ];
    }

    public function isVisible(array $data): bool
    {
        return filled($data['type']) || filled($data['women_attire']) || filled($data['men_attire']);
    }
}
