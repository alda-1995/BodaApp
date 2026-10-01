<?php

namespace App\FormBuilder\Controls;

/**
 * Variante multilínea de InlineTextField: en vista muestra sólo la primera línea
 * del texto, y al editar la fila se convierte en un textarea.
 */
class InlineTextareaField extends InlineTextField
{
    protected function defineType(): string
    {
        return 'inline-textarea';
    }

    protected function typeRules(): array
    {
        return ['string', 'max:2000'];
    }
}
