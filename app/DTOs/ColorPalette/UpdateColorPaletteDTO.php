<?php

namespace App\DTOs\ColorPalette;

class UpdateColorPaletteDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $primaryColor,
        public readonly string $secondaryColor,
        public readonly string $accentColor,
        public readonly bool $isActive = true
    ) {}
}