<?php

namespace App\DTOs\Template;

use Illuminate\Http\UploadedFile;

class UpdateTemplateDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public string $viewPath,
        public bool $isActive = true,
        public array $adminFields = [],
        /** Días de vigencia tras la boda; null usa los de config/events.php. */
        public ?int $durationDays = null,
        /** Foto nueva; null significa que no subieron ninguna en este envío. */
        public ?UploadedFile $previewImage = null,
        public ?string $description = null,
        /**
         * El control de imagen manda la URL de la que ya había. Si llega vacía y
         * no viene archivo nuevo, es que la quitaron con el bote de basura: sin
         * esto no habría forma de distinguirlo de "no la toqué".
         */
        public bool $keepPreviewImage = true
    ) {}
}
