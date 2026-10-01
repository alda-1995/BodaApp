<?php

namespace App\Services\Template;

use App\Contracts\Template\TemplateStrategy;
use App\Models\Template;
use App\Strategies\DefaultTemplateStrategy;
use App\Strategies\EditorialWeddingStrategy;
use App\Strategies\EmeraldWeddingStrategy;
use Illuminate\Support\Facades\File;

class TemplateDiscoveryService
{
    /**
     * Mapa estático de asociación entre rutas de vistas Blade y sus clases de Estrategia.
     * Si agregas una nueva plantilla, solo debes registrarla en este array.
     *
     * @var array<string, class-string<TemplateStrategy>>
     */
    protected array $strategiesMap = [
        'build-templates.template-travel.index' => EmeraldWeddingStrategy::class,
        'build-templates.template-editorial.index' => EditorialWeddingStrategy::class,
    ];

    /** La estrategia de una plantilla concreta. */
    public function forTemplate(?Template $template): TemplateStrategy
    {
        return $this->resolveStrategy($template?->view_path);
    }

    /**
     * Resuelve la instancia de la estrategia correspondiente a una vista concreta.
     * Si no tiene una estrategia personalizada asignada, retorna la estrategia por defecto.
     */
    public function resolveStrategy(?string $viewPath): TemplateStrategy
    {
        if (empty($viewPath)) {
            return app(DefaultTemplateStrategy::class);
        }

        // Cada plantilla tiene su clase: ahí declara sus secciones y su orden.
        if (array_key_exists($viewPath, $this->strategiesMap)) {
            return app($this->strategiesMap[$viewPath]);
        }

        return app(DefaultTemplateStrategy::class);
    }

    /**
     * Escanea el directorio de vistas y retorna un mapa con dot-notation global y nombres legibles.
     *
     * @return array<string, string>
     */
    public function getAvailableViews(): array
    {
        $templatesDirectory = resource_path('views/build-templates');
        $availableViews = [];

        if (!File::exists($templatesDirectory)) {
            return $availableViews;
        }

        $files = File::allFiles($templatesDirectory);

        foreach ($files as $file) {
            $relativePath = $file->getRelativePathname(); 
           
            if (!str_ends_with($relativePath, '.blade.php')) {
                continue;
            }

            // Quitar la extensión .blade.php
            $viewName = str_replace('.blade.php', '', $relativePath);
        
            // Convertir barras inclinadas a puntos
            $dotNotation = str_replace(['/', '\\'], '.', $viewName);

            // IMPORTANTE: Anteponer el directorio raíz para que 'View::exists()' funcione globalmente
            $globalDotNotation = 'build-templates.' . $dotNotation;

            // Guardamos: ['build-templates.carpeta.archivo' => 'carpeta/archivo']
            $availableViews[$globalDotNotation] = $viewName;
        }

        return $availableViews;
    }
}