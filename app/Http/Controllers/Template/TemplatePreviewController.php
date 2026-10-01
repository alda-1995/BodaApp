<?php

namespace App\Http\Controllers\Template;

use App\Http\Controllers\Controller;
use App\Services\TemplateService;
use App\Templates\TemplateRenderer;
use Illuminate\Support\Facades\View;

/**
 * Vista previa del catálogo: la misma plantilla y los mismos bloques que verá
 * un invitado, pero con datos de ejemplo.
 */
class TemplatePreviewController extends Controller
{
    public function __construct(
        protected TemplateService $templateService,
        protected TemplateRenderer $renderer,
    ) {
    }

    public function __invoke(string $slug)
    {
        $template = $this->templateService->findBySlug($slug);

        if (!$template) {
            abort(404, 'Plantilla no encontrada');
        }

        $viewPath = $template->view_path;

        if (!$viewPath || !View::exists($viewPath)) {
            abort(404, "La plantilla [{$template->view_path}] no existe en el sistema.");
        }

        // Se pasa la plantilla, no sólo su vista: las armadas desde el panel
        // sacan sus bloques de la composición que traen guardada.
        return view($viewPath, $this->renderer->demo($viewPath, [], $template));
    }
}
