<?php

namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

/**
 * Campo de texto que se muestra como una sola línea truncada y sólo se vuelve
 * editable al pulsar el lápiz. Pensado para repeaters de una pregunta por fila,
 * donde el input completo con label ocupa demasiado.
 *
 * Renderiza su propia fila (lápiz + eliminar), así que el repeater omite la
 * cabecera "#N / Eliminar" para no duplicar el botón de borrado.
 */
class InlineTextField extends Field
{
    protected function defineType(): string
    {
        return 'inline-text';
    }

    protected function typeRules(): array
    {
        return ['string', 'max:255'];
    }

    public function rendersOwnRow(): bool
    {
        return true;
    }
}
