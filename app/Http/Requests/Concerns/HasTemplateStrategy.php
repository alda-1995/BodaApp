<?php

namespace App\Http\Requests\Concerns;

use App\Contracts\Template\TemplateStrategy;
use App\Models\Template;
use App\Services\TemplateService;
use App\Services\Template\TemplateDiscoveryService;

trait HasTemplateStrategy
{
    protected ?TemplateStrategy $strategy = null;
    protected ?Template $templateModel = null;

    /**
     * Resuelve el modelo Template desde la ruta o contenedor.
     */
    protected function getTemplateModel(): ?Template
    {
        if ($this->templateModel !== null) {
            return $this->templateModel;
        }

        $templateId = $this->route('id') ?? $this->route('template') ?? $this->input('template_id');

        if (is_object($templateId) && $templateId instanceof Template) {
            $this->templateModel = $templateId;
        } elseif ($templateId) {
            $this->templateModel = app(TemplateService::class)->find((int) $templateId);
        }

        return $this->templateModel;
    }

    /**
     * Resuelve la estrategia asociada al template.
     */
    protected function getStrategy(): ?TemplateStrategy
    {
        if ($this->strategy !== null) {
            return $this->strategy;
        }

        $template = $this->getTemplateModel();

        if (!$template && $this->has('view_path')) {
            // Soporte para cuando se crea o envía view_path directamente en el request
            return app(TemplateDiscoveryService::class)->resolveStrategy($this->input('view_path'));
        }

        if (!$template) {
            return null;
        }

        return app(TemplateDiscoveryService::class)->resolveStrategy($template->view_path);
    }

    /**
     * Combina las reglas base con las reglas dinámicas de la estrategia.
     */
    protected function mergeStrategyRules(array $baseRules, string $context = 'admin'): array
    {
        if ($strategy = $this->getStrategy()) {
            return array_merge($baseRules, $strategy->getValidationRules($context));
        }

        return $baseRules;
    }

    /**
     * Combina los atributos base con los de la estrategia en minúsculas.
     */
    protected function mergeStrategyAttributes(array $baseAttributes, string $context = 'admin'): array
    {
        if ($strategy = $this->getStrategy()) {
            $strategyAttributes = array_map(
                fn($label) => mb_strtolower($label),
                $strategy->getValidationAttributes($context)
            );

            return array_merge($baseAttributes, $strategyAttributes);
        }

        return $baseAttributes;
    }

    /**
     * Mensajes traducidos reutilizables.
     */
    protected function defaultStrategyMessages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string'   => 'El campo :attribute debe ser una cadena de texto.',
            'numeric'  => 'El campo :attribute debe ser un número.',
            'in'       => 'El valor seleccionado para :attribute no es válido.',
            'boolean'  => 'El campo :attribute debe ser verdadero o falso.',
        ];
    }
}