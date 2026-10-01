<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGiftRegistrySectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string'],
            'title' => ['required', 'string'],
            'order' => ['required', 'integer'],
            'is_global' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'type' => ['nullable', 'string'],
            'parent' => ['nullable', 'string'],

            // Validación de la estructura $schema (Plana)
            'schema' => ['required', 'array', 'min:1'],
            'schema.*.key' => ['required', 'string'],
            'schema.*.type' => ['required', 'string', 'in:select,text,url,textarea,number'],
            'schema.*.label' => ['required', 'string'],
            'schema.*.placeholder' => ['nullable', 'string'],
            'schema.*.is_required' => ['nullable'],
            
            // Atributos específicos del elemento selector ("type": "select")
            'schema.*.options' => ['nullable', 'array'],
            'schema.*.options.*.value' => ['sometimes', 'required', 'string'],
            'schema.*.options.*.label' => ['sometimes', 'required', 'string'],
            'schema.*.default' => ['nullable', 'string'],
            'schema.*.messages' => ['nullable', 'array'],

            // Atributos específicos de los campos dinámicos
            'schema.*.depends_on' => ['nullable', 'array'],
            'schema.*.depends_on.field' => ['required_with:schema.*.depends_on', 'string'],
            'schema.*.depends_on.values' => ['required_with:schema.*.depends_on', 'array'],
            'schema.*.depends_on.values.*' => ['string'],
            
            // Reglas de validación generadas por el builder
            'schema.*.rules' => ['nullable', 'array'],
            'schema.*.rules.*' => ['string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $schema = $this->input('schema');

        // Si el schema viene como string JSON desde el input hidden, lo decodificamos a array
        if (is_string($schema)) {
            $schema = json_decode($schema, true) ?? [];
        }

        $this->merge([
            'is_global' => $this->boolean('is_global'),
            'is_active' => $this->boolean('is_active'),
            'schema' => $schema,
        ]);
    }
}