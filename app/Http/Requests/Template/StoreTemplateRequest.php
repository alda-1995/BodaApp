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
            // Con qué se presenta antes de comprarla: su foto y su texto.
            'preview_image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            // La manda el control de imagen con la que ya había; aquí nunca hay.
            'preview_image_url' => 'nullable|string',
            'description'       => 'nullable|string|max:500',
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

            'preview_image.image'      => 'La imagen de presentación debe ser un archivo de imagen.',
            'preview_image.mimes'      => 'La imagen de presentación debe ser JPG, PNG o WebP.',
            'preview_image.max'        => 'La imagen de presentación no puede pesar más de 5 MB.',
            'preview_image.uploaded'   => 'No pudimos subir la imagen de presentación. Revisa que pese menos de 5 MB e inténtalo de nuevo.',

            'description.string'       => 'La descripción debe ser texto.',
            'description.max'          => 'La descripción no puede superar los :max caracteres.',
        ];
    }
}
