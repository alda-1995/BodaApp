<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Template\TemplateAssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Imágenes de la plantilla de una boda concreta, vistas por el superadmin.
 *
 * El formulario no está escrito a mano: sale de los manifiestos de los bloques
 * de esa plantilla. Si mañana un bloque declara otra imagen, aparece sola.
 */
class EventTemplateAssetController extends Controller
{
    public function __construct(private readonly TemplateAssetService $assets)
    {
    }

    /**
     * Las bodas que se pueden personalizar: las que ya tienen plantilla.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', '')) ?: null;

        $events = Event::query()
            ->with(['template', 'user'])
            ->whereNotNull('template_id')
            ->when($search, fn ($query, $term) => $query->where(
                fn ($q) => $q->where('custom_url', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%"))
            ))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.event-assets.index', compact('events', 'search'));
    }

    public function edit(Event $event): View
    {
        return view('admin.event-assets.edit', [
            'event' => $event->load('template', 'user'),
            'groups' => $this->assets->declaredFor($event),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $declared = $this->declaredAssets($event);

        $data = $request->validate([
            'section' => ['required', 'string'],
            'asset' => ['required', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'frames' => ['nullable', 'array'],
            'frames.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $asset = $declared[$data['section']][$data['asset']] ?? null;

        if (!$asset) {
            return back()->with('error', 'Esa imagen no es de esta plantilla.');
        }

        if ($asset['kind'] === 'sequence') {
            $files = $request->file('frames', []);

            if ($files === []) {
                return back()->with('error', 'Selecciona los cuadros de la secuencia.');
            }

            // Se avisa, pero no se bloquea: el diseño manda y a veces cambia.
            $warning = count($files) !== $asset['count']
                ? "Subiste " . count($files) . " cuadros y la secuencia espera {$asset['count']}. Revísala en la invitación."
                : null;

            $this->assets->storeSequence($event, $data['section'], $data['asset'], $files, $asset['extension'] ?? 'png');

            return back()
                ->with('success', 'Secuencia actualizada.')
                ->with('warning', $warning);
        }

        if (!$request->hasFile('image')) {
            return back()->with('error', 'Selecciona una imagen.');
        }

        $this->assets->storeImage($event, $data['section'], $data['asset'], $request->file('image'));

        return back()->with('success', 'Imagen actualizada.');
    }

    public function reset(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'section' => ['required', 'string'],
            'asset' => ['required', 'string'],
        ]);

        $this->assets->reset($event, $data['section'], $data['asset']);

        return back()->with('success', 'La imagen volvió a la de la plantilla.');
    }

    /** @return array<string, array<string, array<string, mixed>>> */
    private function declaredAssets(Event $event): array
    {
        return collect($this->assets->declaredFor($event))
            ->mapWithKeys(fn (array $group) => [$group['section'] => $group['assets']])
            ->all();
    }
}
