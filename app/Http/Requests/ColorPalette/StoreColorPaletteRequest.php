<?php

namespace App\Http\Requests\ColorPalette;

use Illuminate\Foundation\Http\FormRequest;

class StoreColorPaletteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'primary_color' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'accent_color' => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la paleta es obligatorio.',
            'primary_color.required' => 'El color primario es obligatorio.',
            'primary_color.regex' => 'El color primario debe ser un código hexadecimal válido (ej. #1E1E1E).',
            'secondary_color.required' => 'El color secundario es obligatorio.',
            'secondary_color.regex' => 'El color secundario debe ser un código hexadecimal válido.',
            'accent_color.required' => 'El color de acento es obligatorio.',
            'accent_color.regex' => 'El color de acento debe ser un código hexadecimal válido.',
        ];
    }
}
