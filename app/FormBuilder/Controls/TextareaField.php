<?php

namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

class TextareaField extends Field
{
    protected function defineType(): string
    {
        return 'textarea';
    }
}