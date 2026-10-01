<?php

namespace App\Templates;

/**
 * Una sección ya resuelta y lista para pintarse: qué bloque la dibuja y con qué
 * datos. La vista nunca recibe "features" crudo, sólo esto.
 */
final class SectionView
{
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        /** Tipo de bloque del catálogo (ver BlockType). */
        public readonly string $blockType,
        /** Vista Blade que lo pinta, ya resuelta a la carpeta de la plantilla. */
        public readonly string $component,
        public readonly array $data,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function filled(string $key): bool
    {
        return filled($this->get($key));
    }
}
