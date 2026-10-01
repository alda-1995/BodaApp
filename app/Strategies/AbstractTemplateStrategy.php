<?php

namespace App\Strategies;

use App\Contracts\Template\TemplateStrategy;
use App\FormBuilder\Contracts\ValidatableFieldInterface;
use App\FormBuilder\FormSchemaCompiler;
use App\Models\SystemSection;
use App\Templates\Sections\Section;

abstract class AbstractTemplateStrategy implements TemplateStrategy
{
    protected FormSchemaCompiler $compiler;

    public function __construct(?FormSchemaCompiler $compiler = null)
    {
        $this->compiler = $compiler ?? new FormSchemaCompiler();
    }

    /**
     * Compila un SystemSection asociándolo a su sección padre (parent).
     */
    protected function compileSystemSection(SystemSection $section): array
    {
        $compiler = new FormSchemaCompiler($section);
        $field = $compiler->build();

        return [
            'parent' => $section->parent ?? $section->key,
            'key'    => $section->key,
            'title'  => $section->title,
            'field'  => $field,
        ];
    }

    /**
     * Inyecta una sección compilada del sistema dentro de una sección base existente o la crea como nueva.
     */
    protected function mergeSystemSection(array $base, SystemSection $section): array
    {
        $compiled = $this->compileSystemSection($section);
        $parentKey = $compiled['parent'];
        $field = $compiled['field'];

        if (!$field) {
            return $base;
        }

        // Si la sección padre existe en el base, inyecta el campo en sus 'fields'
        if (isset($base[$parentKey])) {
            $base[$parentKey]['fields'][$field->getName()] = $field;
        } else {
            // Si la sección no existe, la crea dinámicamente como un grupo independiente
            $base[$compiled['key']] = [
                'title'  => $compiled['title'],
                'fields' => [$field->getName() => $field],
            ];
        }

        return $base;
    }

    abstract public function getName(): string;

    /**
     * Secciones del catálogo que arman esta plantilla, en el orden del wizard.
     * De aquí salen los pasos, el avance, la vista previa y la página pública.
     *
     * @return array<int, Section>
     */
    public function sections(): array
    {
        return [];
    }

    /**
     * Hojas de fuentes web que necesita la plantilla (Google Fonts u otras).
     *
     * Las fuentes son de cada plantilla, no del sistema: el layout sólo imprime
     * lo que se declare aquí. Más adelante esta lista puede venir de la base,
     * para que el superadmin cambie la tipografía sin tocar código.
     *
     * @return array<int, string>
     */
    public function fonts(): array
    {
        return [];
    }

    /**
     * Campos base: los que declaran las secciones de la plantilla.
     */
    protected function baseClientFields(): array
    {
        $groups = [];

        foreach ($this->sections() as $section) {
            if ($section->fields() !== []) {
                $groups[$section->key()] = $section->toWizardGroup();
            }
        }

        return $groups;
    }

    abstract protected function baseAdminFields(): array;

    public function getClientFields(): array
    {
        return $this->mergeFieldGroups($this->baseClientFields(), $this->customClientFields());
    }

    public function getAdminFields(): array
    {
        return $this->mergeFieldGroups($this->baseAdminFields(), $this->customAdminFields());
    }

    /**
     * Sobrescribir en las estrategias hijas para extender o alterar campos.
     */
    protected function customClientFields(): array
    {
        return [];
    }

    protected function customAdminFields(): array
    {
        return [];
    }

    public function getValidationRules(string $role = 'client'): array
    {
        $rules = [];
        $groups = $role === 'client' ? $this->getClientFields() : $this->getAdminFields();

        foreach ($groups as $groupKey => $group) {
            foreach ($group['fields'] ?? [] as $fieldKey => $field) {
                if ($field instanceof ValidatableFieldInterface) {
                    $rules = array_merge($rules, $field->toValidationRules($groupKey));
                }
            }
        }

        return $rules;
    }

    public function getValidationAttributes(string $role = 'client'): array
    {
        $attributes = [];
        $groups = $role === 'client' ? $this->getClientFields() : $this->getAdminFields();

        foreach ($groups as $groupKey => $group) {
            foreach ($group['fields'] ?? [] as $fieldKey => $field) {
                if ($field instanceof ValidatableFieldInterface) {
                    $attributes = array_merge($attributes, $field->toValidationAttributes($groupKey));
                }
            }
        }

        return $attributes;
    }

    public function getClientWizardSteps(): array
    {
        $steps = [];
        $index = 1;

        foreach ($this->getClientFields() as $groupKey => $group) {
            $steps[$groupKey] = [
                'step' => $index++,
                'title' => $group['title'] ?? ucfirst($groupKey),
                'fields' => $group['fields'] ?? [],
            ];
        }

        return $steps;
    }

    /**
     * Campos que se guardan en columnas de events; los declara cada sección.
     */
    public function getModelAttributesMap(): array
    {
        $map = [];

        foreach ($this->sections() as $section) {
            foreach ($section->modelAttributes() as $field => $column) {
                $map["{$section->key()}.{$field}"] = $column;
            }
        }

        return $map;
    }

    /**
     * Fusiona secciones y campos de forma profunda evitando sobreescrituras completas de arrays.
     */
    protected function mergeFieldGroups(array $base, array $custom): array
    {
        foreach ($custom as $sectionKey => $sectionData) {
            if (!isset($base[$sectionKey])) {
                $base[$sectionKey] = $sectionData;
                continue;
            }

            if (isset($sectionData['title'])) {
                $base[$sectionKey]['title'] = $sectionData['title'];
            }

            if (isset($sectionData['fields'])) {
                $base[$sectionKey]['fields'] = array_merge(
                    $base[$sectionKey]['fields'] ?? [],
                    $sectionData['fields']
                );
            }
        }

        return $base;
    }
}