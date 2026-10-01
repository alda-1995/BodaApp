<?php

namespace App\Services\Template;

use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Las imágenes de la plantilla que el superadmin reemplaza para una boda.
 *
 * Cada sección declara las suyas en templateAssets(): si una plantilla no
 * declara ninguna, su pantalla aparece vacía y no hay nada que subir. Lo que se
 * guarda va a events.template_assets, que el wizard del organizador no toca.
 */
class TemplateAssetService
{
    public const DISK = 'public';

    public function __construct(private readonly TemplateDiscoveryService $discovery)
    {
    }

    /**
     * Lo que esa boda puede personalizar, con su valor actual.
     *
     * @return array<int, array{section: string, title: string, assets: array<string, array<string, mixed>>}>
     */
    public function declaredFor(Event $event): array
    {
        $strategy = $this->discovery->resolveStrategy($event->template?->view_path);
        $overrides = (array) ($event->template_assets ?? []);
        $groups = [];

        foreach ($this->sectionsOf($strategy) as $section) {
            if ($section->templateAssets() === []) {
                continue;
            }

            $assets = [];

            foreach ($section->templateAssets() as $key => $asset) {
                $current = $overrides[$section->key()][$key] ?? null;

                // Una imagen suelta es lo normal; sólo las secuencias dicen
                // cuántos cuadros traen y en qué formato.
                $assets[$key] = $asset + [
                    'current' => $current ?? asset($asset['default']),
                    'is_overridden' => filled($current),
                    'help' => '',
                    'kind' => 'image',
                    'count' => null,
                    'extension' => null,
                ];
            }

            $groups[] = [
                'section' => $section->key(),
                'title' => $section->title(),
                'assets' => $assets,
            ];
        }

        return $groups;
    }

    /** Una imagen suelta: reemplaza la de la plantilla para esta boda. */
    public function storeImage(Event $event, string $section, string $key, UploadedFile $file): string
    {
        $path = $file->store($this->directory($event, $section), self::DISK);

        return $this->remember($event, $section, $key, Storage::disk(self::DISK)->url($path));
    }

    /**
     * Una secuencia de cuadros. Se guardan como frame1, frame2... en el orden
     * en que vienen los archivos: si se suben desordenados, la animación sale
     * desordenada y hay que volver a subirla.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function storeSequence(Event $event, string $section, string $key, array $files, string $extension): string
    {
        $folder = $this->directory($event, $section) . '/' . $key;

        // Se limpia antes: una secuencia nueva no debe mezclarse con la anterior.
        Storage::disk(self::DISK)->deleteDirectory($folder);

        $ordered = collect($files)->sortBy(fn (UploadedFile $file) => $file->getClientOriginalName(), SORT_NATURAL)->values();

        foreach ($ordered as $index => $file) {
            $file->storeAs($folder, 'frame' . ($index + 1) . '.' . $extension, self::DISK);
        }

        return $this->remember($event, $section, $key, Storage::disk(self::DISK)->url($folder) . '/');
    }

    /** Quita el reemplazo: la boda vuelve a ver la imagen de la plantilla. */
    public function reset(Event $event, string $section, string $key): void
    {
        $assets = (array) ($event->template_assets ?? []);

        unset($assets[$section][$key]);

        if (($assets[$section] ?? []) === []) {
            unset($assets[$section]);
        }

        $event->update(['template_assets' => $assets ?: null]);
    }

    private function remember(Event $event, string $section, string $key, string $url): string
    {
        $assets = (array) ($event->template_assets ?? []);
        $assets[$section][$key] = $url;

        $event->update(['template_assets' => $assets]);

        return $url;
    }

    private function directory(Event $event, string $section): string
    {
        return "plantillas/{$event->id}/{$section}";
    }

    /** @return array<int, object> */
    private function sectionsOf(object $strategy): array
    {
        return method_exists($strategy, 'sections') ? $strategy->sections() : [];
    }
}
