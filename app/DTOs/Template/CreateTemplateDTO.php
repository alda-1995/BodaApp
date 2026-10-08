<?php

namespace App\DTOs\Template;

use Illuminate\Http\UploadedFile;

class CreateTemplateDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public string $viewPath,
        public bool $isActive = true,
        public ?int $durationDays = null,
        /** Con qué se presenta antes de comprarla: su foto y su texto. */
        public ?UploadedFile $previewImage = null,
        public ?string $description = null
    ) {}
}
