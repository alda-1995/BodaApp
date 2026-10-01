<?php

namespace App\Templates;

use App\Contracts\Template\TemplateStrategy;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\Template;
use App\Services\EventWizardService;
use App\Services\Template\TemplateDiscoveryService;
use App\Templates\Sections\Section;
use App\Templates\Sections\VariantSection;
use App\Templates\Variants\VariantManifest;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;

/**
 * Arma lo que la plantilla necesita para pintarse: el contexto del evento, sus
 * secciones resueltas y los assets propios de esa plantilla.
 *
 * La invitación real y la vista previa pasan por aquí, así que no se pueden
 * desincronizar: lo único que cambia es de dónde salen los datos.
 */
class TemplateRenderer
{
    public function __construct(
        private readonly EventWizardService $wizard,
        private readonly TemplateDiscoveryService $discovery,
    ) {
    }

    /**
     * @return array{context: TemplateContext, sections: array<string, SectionView>, assets: array<int, string>}
     */
    public function forEvent(Event $event, ?EventGuest $invitation = null): array
    {
        $viewPath = $event->template?->view_path;
        $strategy = $this->discovery->forTemplate($event->template);
        $files = $event->files()->get();
        $sections = $this->sectionsOf($strategy);

        // Valores guardados de cada paso: features + columnas nativas + archivos.
        $values = [];
        foreach ($sections as $section) {
            $values[$section->key()] = $this->wizard->resolveSavedValues($event, $strategy, $section->key(), $files);
        }
        // dd($values);

        // Imágenes de la plantilla que el superadmin cambió para este evento.
        $overrides = (array) ($event->template_assets ?? []);

        $views = [];
        foreach ($sections as $section) {
            // Las imágenes de la plantilla entran con su valor por defecto y el
            // superadmin las reemplaza si subió otra para este evento.
            $data = $section->data($values[$section->key()] ?? [], $values) + $section->assetDefaults();
            $data = array_merge($data, array_filter($overrides[$section->key()] ?? []));

            if (!$section->isVisible($data)) {
                continue;
            }

            if ($view = $this->toView($section, $data, $viewPath)) {
                $views[$section->key()] = $view;
            }
        }

        $general = $values['general'] ?? [];

        return [
            'context' => new TemplateContext(
                wifeName: (string) ($general['name_wife'] ?? ''),
                husbandName: (string) ($general['name_husband'] ?? ''),
                eventDate: $event->event_date?->copy(),
                parents: $general['family_parents'] ?? null,
                theme: $event->theme_colors,
                guest: $invitation ? $this->guestData($invitation) : null,
                rsvpUrl: route('invitation.rsvp', $event->custom_url),
            ),
            'sections' => $views,
            'assets' => $this->assetsFor($viewPath, $this->foldersOf($strategy)),
            'fonts' => $strategy->fonts(),
            'bodyClass' => method_exists($strategy, 'bodyClass') ? $strategy->bodyClass() : '',
        ];
    }

    /**
     * Misma estructura con datos de ejemplo, para la vista previa del catálogo.
     *
     * @return array{context: TemplateContext, sections: array<string, SectionView>, assets: array<int, string>}
     */
    public function demo(?string $viewPath, array $theme = [], ?Template $template = null): array
    {
        $strategy = $template
            ? $this->discovery->forTemplate($template)
            : $this->discovery->resolveStrategy($viewPath);
        $views = [];

        foreach ($this->sectionsOf($strategy) as $section) {
            if ($view = $this->toView($section, $section->demo() + $section->assetDefaults(), $viewPath)) {
                $views[$section->key()] = $view;
            }
        }

        $cover = $views['general']->data ?? [];

        return [
            'context' => new TemplateContext(
                wifeName: (string) ($cover['wife_name'] ?? 'Sofía'),
                husbandName: (string) ($cover['husband_name'] ?? 'Alejandro'),
                eventDate: $cover['event_date'] ?? Carbon::now()->addDays(45),
                parents: $cover['parents'] ?? null,
                theme: $theme ?: ['primary' => '#4D3D7E', 'secondary' => '#F5E9DC', 'accent' => '#FFFFFF'],
                guest: (object) ['name' => 'Familia Martínez López', 'max_passes' => 4, 'uuid' => null, 'has_confirmed' => false],
                isDemo: true,
            ),
            'sections' => $views,
            'assets' => $this->assetsFor($viewPath, $this->foldersOf($strategy)),
            'fonts' => $strategy->fonts(),
            'bodyClass' => method_exists($strategy, 'bodyClass') ? $strategy->bodyClass() : '',
        ];
    }

    /**
     * Las carpetas que aportan bloques: una sola en las plantillas escritas a
     * mano, varias en las que se arman desde el panel.
     *
     * @return array<int, string>
     */
    private function foldersOf(object $strategy): array
    {
        return method_exists($strategy, 'folders') ? $strategy->folders() : [];
    }

    /**
     * Carpeta de la plantilla dentro de build-templates:
     * "build-templates.template-travel.index" => "template-travel".
     */
    public function folderFor(?string $viewPath): ?string
    {
        $segments = explode('.', (string) $viewPath);

        return $segments[1] ?? null;
    }

    /**
     * Vista del bloque: primero la de la plantilla, si no la compartida.
     *
     * components/templates/{plantilla}/blocks/{tipo}.blade.php
     * components/templates/shared/blocks/{tipo}.blade.php
     *
     * Un bloque puede llamarse distinto a su tipo: entonces él dice su archivo
     * y se busca ése. El respaldo compartido siempre usa el del tipo.
     */
    public function componentFor(string $blockType, ?string $viewPath, ?string $file = null): ?string
    {
        $folder = $this->folderFor($viewPath);
        // Por convención el archivo se llama como su tipo; la sección lo dice
        // cuando su plantilla lo nombró de otra forma.
        $file ??= $blockType;

        $candidates = array_filter([
            $folder ? "templates.{$folder}.blocks.{$file}" : null,
            "templates.shared.blocks.{$blockType}",
        ]);

        foreach ($candidates as $candidate) {
            if (View::exists('components.' . $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * CSS y JS propios de la plantilla, si existen.
     *
     * @return array<int, string>
     */
    public function assetsFor(?string $viewPath, array $folders = []): array
    {
        $folders = $folders ?: array_filter([$this->folderFor($viewPath)]);

        return collect($folders)
            ->flatMap(fn (string $folder) => [
                "resources/css/templates/{$folder}/template.css",
                "resources/js/templates/{$folder}/index.js",
            ])
            ->filter(fn (string $asset) => file_exists(base_path($asset)))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, Section>
     */
    private function sectionsOf(TemplateStrategy $strategy): array
    {
        return method_exists($strategy, 'sections') ? $strategy->sections() : [];
    }

    private function toView(Section $section, array $data, ?string $viewPath): ?SectionView
    {
        $component = $this->componentFor($section->blockType(), $viewPath, $section->componentFile());

        // Si la plantilla no tiene ese tipo de bloque, la sección no se pinta.
        if (!$component) {
            return null;
        }

        return new SectionView(
            key: $section->key(),
            title: $section->title(),
            blockType: $section->blockType(),
            component: $component,
            data: $data,
        );
    }

    private function guestData(EventGuest $invitation): object
    {
        return (object) [
            'name' => $invitation->guest?->name ?? '',
            'max_passes' => (int) ($invitation->max_passes ?? 1),
            'uuid' => $invitation->uuid,
            // Si ya respondió, la invitación muestra el agradecimiento en vez del formulario.
            'has_confirmed' => $invitation->rsvp()->exists(),
        ];
    }
}
