<?php

namespace App\DTOs\Template;

class CreateTemplateDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public string $viewPath,
        public bool $isActive = true,
        public ?int $durationDays = null
    ) {}
}