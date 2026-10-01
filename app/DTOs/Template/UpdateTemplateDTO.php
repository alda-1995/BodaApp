<?php

namespace App\DTOs\Template;

class UpdateTemplateDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public string $viewPath,
        public bool $isActive = true,
        public array $adminFields = [],
        /** Días de vigencia tras la boda; null usa los de config/events.php. */
        public ?int $durationDays = null
    ) {}
}