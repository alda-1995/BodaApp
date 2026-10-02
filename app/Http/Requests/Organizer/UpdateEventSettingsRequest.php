<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateEventSettingsRequest extends FormRequest
{
    private const HEX_COLOR = 'regex:/^#[0-9a-fA-F]{6}$/';

    public function authorize(): bool
    {
        // La ruta ya exige el rol; el evento es siempre el propio del usuario.
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Casillas sin marcar no se envían: se interpretan como "no".
        $this->merge([
            'custom_colors' => $this->boolean('custom_colors'),
            'allow_children' => $this->boolean('allow_children'),
            'allow_guest_uploads' => $this->boolean('allow_guest_uploads'),
            // La dirección se normaliza igual que al generarla: "Ana y Luis" → "ana-y-luis".
            'custom_url' => Str::slug((string) $this->input('custom_url')) ?: null,
        ]);
    }

    public function rules(): array
    {
        $eventId = $this->user()->currentEvent()?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'partner_1_name' => ['required', 'string', 'max:100'],
            'partner_2_name' => ['required', 'string', 'max:100'],

            // Dirección de la invitación: única entre los eventos y entre las
            // direcciones anteriores (esas siguen redirigiendo a su dueño).
            'custom_url' => [
                'nullable',
                'string',
                'min:3',
                'max:80',
                Rule::unique('events', 'custom_url')->ignore($eventId),
                Rule::unique('event_url_redirects', 'custom_url')
                    ->where(fn ($query) => $query->where('event_id', '!=', $eventId)),
            ],

            'custom_colors' => ['boolean'],
            'palette_id' => [
                'exclude_if:custom_colors,true',
                'required',
                'integer',
                Rule::exists('color_palettes', 'id')->where('is_active', true),
            ],
            'primary_color' => ['exclude_unless:custom_colors,true', 'required', self::HEX_COLOR],
            'secondary_color' => ['exclude_unless:custom_colors,true', 'required', self::HEX_COLOR],

            'allow_children' => ['boolean'],
            'open_link_max_passes' => ['required', 'integer', 'min:0', 'max:20'],
            'allow_guest_uploads' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe tu nombre.',
            'partner_1_name.required' => 'Escribe el nombre de la primera persona de la pareja.',
            'partner_2_name.required' => 'Escribe el nombre de la segunda persona de la pareja.',
            'custom_url.unique' => 'Esa dirección ya está ocupada. Prueba con otra.',
            'custom_url.min' => 'La dirección debe tener al menos :min caracteres.',
            'custom_url.max' => 'La dirección no puede tener más de :max caracteres.',
            'palette_id.required' => 'Elige una paleta de colores o personaliza tus colores.',
            'palette_id.exists' => 'Esa paleta ya no está disponible. Elige otra.',
            'primary_color.required' => 'Elige el color primario.',
            'primary_color.regex' => 'El color primario debe tener el formato #1E1E1E.',
            'secondary_color.required' => 'Elige el color secundario.',
            'secondary_color.regex' => 'El color secundario debe tener el formato #F5E9DC.',
            'open_link_max_passes.required' => 'Indica el máximo de acompañantes.',
            'open_link_max_passes.integer' => 'El máximo de acompañantes debe ser un número entero.',
            'open_link_max_passes.min' => 'El máximo de acompañantes no puede ser negativo.',
            'open_link_max_passes.max' => 'El máximo de acompañantes no puede ser mayor a :max.',
        ];
    }
}
