<?php

namespace App\Http\Requests\Template;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\View;
use App\Http\Requests\Concerns\HasTemplateStrategy;

class UpdateTemplateRequest extends FormRequest
{
    use HasTemplateStrategy;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merges = [];

        if ($this->has('active')) {
            $merges['is_active'] = $this->boolean('active');
        }

        if ($template = $this->getTemplateModel()) {
            $merges['view_path'] = $template->view_path;
        }

        if (!empty($merges)) {
            $this->merge($merges);
        }
    }

    public function rules(): array
    {
        $baseRules = [
            'name'           => 'required|string|max:255',
            'price'          => 'required|numeric|min:0',
            'view_path'      => [
                'required',
                'string',
                fn($attribute, $value, $fail) => !View::exists($value) && $fail('La vista seleccionada no existe.'),
            ],
            'is_active'      => 'boolean',
            // Vacío = se usan los días por defecto de config/events.php.
            'duration_days'  => 'nullable|integer|min:1|max:365',
            'admin_fields'   => 'nullable|array',
            'admin_fields.*' => 'nullable|array',
        ];

        return $this->mergeStrategyRules($baseRules, 'admin');
    }

    public function attributes(): array
    {
        $baseAttributes = [
            'name'      => 'nombre de la plantilla',
            'price'     => 'precio',
            'view_path' => 'vista de la plantilla',
            'is_active' => 'estado activo',
            'duration_days' => 'días de vigencia',
        ];

        return $this->mergeStrategyAttributes($baseAttributes, 'admin');
    }

    public function messages(): array
    {
        return array_merge($this->defaultStrategyMessages(), [
            'price.min'          => 'El :attribute no puede ser menor a :min.',
            'view_path.required' => 'Debes seleccionar una :attribute.',
        ]);
    }
}