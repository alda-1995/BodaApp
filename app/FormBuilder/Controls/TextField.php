<?php

namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

class TextField extends Field
{
    protected string $inputType = 'text';

    /**
     * Permite cambiar el tipo de input (text, date, time, email, etc.)
     */
    public function type(string $type): static
    {
        $this->inputType = $type;
        return $this;
    }

    protected function defineType(): string
    {
        return $this->inputType;
    }
}