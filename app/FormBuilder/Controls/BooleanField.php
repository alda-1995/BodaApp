<?php

namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

class BooleanField extends Field
{
    protected function defineType(): string
    {
        return 'boolean';
    }
}