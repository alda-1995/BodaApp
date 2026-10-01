<?php

namespace App\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\View;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('active')) {
            $this->merge(['is_active' => $this->boolean('active')]);
        }
    }

    public function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'price'           => 'required|numeric|min:0',
            'view_path' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!View::exists($value)) {
                        $fail('La vista seleccionada no existe en el directorio de recursos.');
                    }
                },
            ],
            'is_active'       => 'boolean',
            // Vacío = se usan los días por defecto de config/events.php.
            'duration_days'   => 'nullable|integer|min:1|max:365',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'            => 'El nombre de la plantilla es obligatorio.',
            'name.string'              => 'El nombre debe ser una cadena de texto válida.',
            'name.max'                 => 'El nombre no puede superar los 255 caracteres.',
            
            'price.required'           => 'El precio de la plantilla es obligatorio.',
            'price.numeric'            => 'El precio debe ser un valor numérico válido.',
            'price.min'                => 'El precio no puede ser menor a 0.',

            'view_path.required'  => 'Debes seleccionar una carpeta/vista para la plantilla.',
            'view_path.view_exists'=> 'La vista seleccionada no existe en el directorio de recursos.',
            
            'is_active.boolean'        => 'El estado de activación debe ser verdadero o falso.',

            'duration_days.integer'    => 'Los días de vigencia deben ser un número entero.',
            'duration_days.min'        => 'Los días de vigencia deben ser al menos :min.',
            'duration_days.max'        => 'Los días de vigencia no pueden ser más de :max.',
        ];
    }
}
