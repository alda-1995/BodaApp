<?php
namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

class DateTimeField extends Field
{
    protected function defineType(): string
    {
        return 'datetime';
    }
}