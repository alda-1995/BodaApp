<?php

namespace App\Http\Requests\Concerns;

use App\Contracts\ValidatableFieldInterface;
use App\Models\Event;
use App\Services\Template\TemplateDiscoveryService;

trait HasEventWizardStrategy
{
    /**
     * Resuelve el objeto Event de la ruta.
     */
    protected function getEventModel(): ?Event
    {
        $event = $this->route('event');

        return $event instanceof Event ? $event : null;
    }

    /**
     * Obtiene la clave del paso actual (vía ruta o query).
     */
    protected function getStepKey(): string
    {
        return (string) ($this->route('stepKey') ?? $this->route('step') ?? $this->input('step', 'general'));
    }

    /**
     * Obtiene la lista de campos del paso actual.
     * @return ValidatableFieldInterface[]
     */
    protected function getStepFields(): array
    {
        $event = $this->getEventModel();
        if (!$event || !$event->template) {
            return [];
        }

        $strategy = app(TemplateDiscoveryService::class)->resolveStrategy($event->template->view_path);
        $steps = $strategy->getClientWizardSteps();
        $stepKey = $this->getStepKey();

        return $steps[$stepKey]['fields'] ?? [];
    }

    /**
     * Extrae las reglas de validación delegando a cada campo.
     */
    protected function getWizardStepRules(): array
    {
        $rules = [];

        foreach ($this->getStepFields() as $field) {
            if ($field instanceof ValidatableFieldInterface) {
                // Se pasa string vacío como prefijo porque el FormRequest valida 
                // directamente sobre la raíz de los inputs del paso actual.
                $rules = array_merge($rules, $field->toValidationRules(''));
            }
        }

        return $rules;
    }

    /**
     * Extrae las etiquetas para los mensajes de validación legibles.
     */
    protected function getWizardStepAttributes(): array
    {
        $attributes = [];

        foreach ($this->getStepFields() as $field) {
            if ($field instanceof ValidatableFieldInterface) {
                $attributes = array_merge($attributes, $field->toValidationAttributes(''));
            }
        }

        return $attributes;
    }

    /**
     * Mensajes traducidos en español.
     */
    protected function getWizardMessages(): array
    {
        $fieldMessages = [];

        foreach ($this->getStepFields() as $field) {
            if ($field instanceof ValidatableFieldInterface && method_exists($field, 'toValidationMessages')) {
                $fieldMessages = array_merge($fieldMessages, $field->toValidationMessages(''));
            }
        }

        return array_merge([
            'required' => 'El campo :attribute es obligatorio.',
            'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
            'string' => 'El campo :attribute debe ser texto.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'email' => 'El campo :attribute debe ser un correo electrónico válido.',
            'url' => 'El campo :attribute debe ser una liga válida que empiece con http:// o https://.',
            'boolean' => 'El campo :attribute debe ser verdadero o falso.',
            'array' => 'El campo :attribute debe contener una lista válida de elementos.',
            'min' => 'El campo :attribute no cumple con el valor mínimo requerido.',
            'max' => 'El campo :attribute excede el tamaño máximo permitido.',
        ], $fieldMessages);
    }
}