<?php

namespace App\FormBuilder\Controls;

use App\Contracts\ValidatableFieldInterface;
use App\FormBuilder\Field;

class RepeaterField extends Field
{
    /** @var ValidatableFieldInterface[]|Field[] */
    protected array $schema = [];

    protected bool $isSortable = false;

    public static function make(string $name, string $label): static
    {
        return new static($name, $label);
    }

    protected function defineType(): string
    {
        return 'repeater';
    }

    /**
     * Setea la estructura de subcampos para cada ítem del repetidor.
     *
     * @param ValidatableFieldInterface[]|Field[] $schema
     */
    public function schema(array $schema): static
    {
        $this->schema = $schema;
        return $this;
    }

    /**
     * Obtiene el esquema de subcampos.
     *
     * @return ValidatableFieldInterface[]|Field[]
     */
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * Resuelve las reglas del arreglo principal y la de sus hijos de manera polimórfica.
     */
    public function toValidationRules(string $parentKey = ''): array
    {
        $baseKey = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;

        // Regla sobre la lista/colección completa
        $rules = [
            $baseKey => $this->getRules($parentKey),
        ];

        // Reglas sobre los ítems internos usando wildcard
        $nestedPrefix = "{$baseKey}.*";

        foreach ($this->schema as $subField) {
            if ($subField instanceof ValidatableFieldInterface) {
                $rules = array_merge($rules, $subField->toValidationRules($nestedPrefix));
            }
        }

        return $rules;
    }

    /**
     * Resuelve los atributos legibles de sí mismo y de sus subcampos.
     */
    public function toValidationAttributes(string $parentKey = ''): array
    {
        $baseKey = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;

        $attributes = [
            $baseKey => mb_strtolower($this->getLabel()),
        ];

        $nestedPrefix = "{$baseKey}.*";

        foreach ($this->schema as $subField) {
            if ($subField instanceof ValidatableFieldInterface) {
                $attributes = array_merge($attributes, $subField->toValidationAttributes($nestedPrefix));
            }
        }

        return $attributes;
    }

    /**
     * Regla por defecto para un Repeater en caso de no definir customRules.
     */
    protected function typeRules(): array
    {
        return ['array'];
    }

    /**
     * Un repeater está respondido si tiene al menos una fila con algún dato.
     */
    public function isFilled(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $row) {
            if (parent::isFilled($row)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Permite reordenar las filas arrastrándolas. El orden resultante es el orden
     * del array enviado, así que no necesita ningún campo extra para persistirse.
     */
    public function sortable(bool $condition = true): static
    {
        $this->isSortable = $condition;
        return $this;
    }

    public function isSortable(): bool
    {
        return $this->isSortable;
    }

    /**
     * Cierto cuando cada fila la dibuja íntegramente su propio control, en cuyo
     * caso el repeater omite su cabecera "#N / Eliminar".
     */
    public function rowsRenderThemselves(): bool
    {
        if (empty($this->schema)) {
            return false;
        }

        foreach ($this->schema as $subField) {
            if (!$subField instanceof Field || !$subField->rendersOwnRow()) {
                return false;
            }
        }

        return true;
    }

    public function toValidationMessages(string $parentKey = ''): array
    {
        $messages = [];
        $prefix = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;

        foreach ($this->schema as $subField) {
            if ($subField instanceof Field) {
                // Genera claves con notación de asterisco: registries.*.external_url.required_if
                $messages = array_merge($messages, $subField->toValidationMessages("{$prefix}.*"));
            }
        }

        return $messages;
    }
}