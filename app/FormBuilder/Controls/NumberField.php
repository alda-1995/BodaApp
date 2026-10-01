<?php

namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

class NumberField extends Field
{
    protected ?num $min = null;
    protected ?num $max = null;
    protected ?num $step = null;

    protected function defineType(): string
    {
        return 'number';
    }

    public function min(int|float $min): static
    {
        $this->min = $min;
        return $this;
    }

    public function max(int|float $max): static
    {
        $this->max = $max;
        return $this;
    }

    public function step(int|float $step): static
    {
        $this->step = $step;
        return $this;
    }

    public function getMin(): ?float
    {
        return $this->min;
    }

    public function getMax(): ?float
    {
        return $this->max;
    }

    public function getStep(): ?float
    {
        return $this->step;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'min'  => $this->min,
            'max'  => $this->max,
            'step' => $this->step,
        ]);
    }
}