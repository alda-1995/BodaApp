<?php

namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

class ColorField extends Field
{
    protected function defineType(): string
    {
        return 'color';
    }
}