<?php
namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

class DateTimeField extends Field
{
    protected function defineType(): string
    {
        return 'datetime';
    }

    /**
     * Que sea una fecha de verdad.
     *
     * El control manda 'Y-m-d H:i:s', pero deja escribir a mano, así que sin
     * esta regla cualquier texto se guardaba tal cual y reventaba después, al
     * leerlo con Carbon para pintar la invitación.
     */
    protected function typeRules(): array
    {
        return ['date'];
    }

    public function toValidationMessages(string $parentKey = ''): array
    {
        $key = $parentKey ? "{$parentKey}.{$this->name}" : $this->name;

        return array_merge([
            "{$key}.date" => "El campo '{$this->label}' debe ser una fecha válida.",
        ], parent::toValidationMessages($parentKey));
    }
}
