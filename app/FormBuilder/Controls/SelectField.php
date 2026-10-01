<?php

namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

class SelectField extends Field
{
    protected array $options = [];

    public function getOptions(): array
    {
        return $this->options;
    }

    protected function defineType(): string
    {
        return 'select';
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['options'] = $this->options;

        return $data;
    }
}