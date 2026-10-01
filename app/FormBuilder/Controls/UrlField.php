<?php
namespace App\FormBuilder\Controls;

use App\FormBuilder\Field;

class UrlField extends Field
{
    /**
     * Define el tipo del campo como 'url'
     */
    protected function defineType(): string
    {
        return 'url';
    }

    /**
     * Sólo http/https: son ligas que los invitados abren en el navegador. La regla
     * 'url' a secas también acepta esquemas como ftp://.
     */
    protected function typeRules(): array
    {
        return ['url:http,https'];
    }
}