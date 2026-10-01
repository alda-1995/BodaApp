<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\InviteCoadminRequest;
use App\Http\Requests\Organizer\UpdateEventSettingsRequest;
use App\Models\Event;
use App\Services\CoadminService;
use App\Services\EventService;
use App\Services\EventSettingsService;
use App\Services\GuestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

/**
 * Configuración del evento. Sólo el dueño: trabaja siempre con su propio evento.
 */
class SettingsController extends Controller
{
    public function __construct(
        protected GuestService $guestService,
        protected EventService $eventService,
        protected EventSettingsService $settingsService,
        protected CoadminService $coadminService,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $event = $this->guestService->eventFor($user);

        return view('organizer.settings.index', [
            'user' => $user,
            'event' => $event,
            'values' => $event ? $this->settingsService->currentValues($event, $user) : [],
            'palettes' => $this->settingsService->palettes(),
            'coadmins' => $event ? $event->coadmins()->with('user')->oldest('id')->get() : collect(),
            'canInviteMore' => $event && $this->coadminService->canInviteMore($event),
            'maxCoadmins' => CoadminService::MAX_PER_EVENT,
        ]);
    }

    public function update(UpdateEventSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $event = $this->guestService->eventFor($user);

        if (!$event) {
            return back()->with('error', 'Necesitas un evento para guardar la configuración.');
        }

        try {
            $this->settingsService->update($event, $user, $request->validated());
        } catch (Throwable $e) {
            Log::error('Error guardando la configuración del evento', ['event_id' => $event->id, 'error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'No pudimos guardar los cambios. Inténtalo de nuevo.');
        }

        return redirect()->route('organizer.settings.index')->with('success', 'Cambios guardados.');
    }

    /**
     * La URL que quedaría con los nombres escritos (sin guardar nada).
     */
    public function urlPreview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'partner_1_name' => ['nullable', 'string', 'max:100'],
            'partner_2_name' => ['nullable', 'string', 'max:100'],
            'custom_url' => ['nullable', 'string', 'max:100'],
        ]);

        $event = $this->guestService->eventFor($request->user());

        if (!$event) {
            return response()->json(['slug' => null, 'url' => null, 'available' => true]);
        }

        $typed = Str::slug((string) ($data['custom_url'] ?? ''));

        // Si escribió su propia dirección, se revisa tal cual y se sugiere una libre.
        if ($typed !== '') {
            $available = !$this->eventService->customUrlTaken($typed, $event);

            return response()->json([
                'slug' => $typed,
                'url' => Event::invitationUrlFor($typed),
                'available' => $available,
                'suggestion' => $available ? null : $this->eventService->uniqueCustomUrlFrom($typed, $event),
                'changed' => $typed !== $event->custom_url,
            ]);
        }

        $partner1 = trim((string) ($data['partner_1_name'] ?? ''));
        $partner2 = trim((string) ($data['partner_2_name'] ?? ''));

        if ($partner1 === '' || $partner2 === '') {
            return response()->json([
                'slug' => $event->custom_url,
                'url' => $event->custom_url ? $event->invitationUrl() : null,
                'available' => true,
            ]);
        }

        // Derivada de los nombres: misma regla que al guardar (única, con sufijo si hace falta).
        $slug = $this->eventService->customUrlFor($event, $partner1, $partner2);

        return response()->json([
            'slug' => $slug,
            'url' => Event::invitationUrlFor($slug),
            'available' => true,
            'changed' => $slug !== $event->custom_url,
        ]);
    }

    public function inviteCoadmin(InviteCoadminRequest $request): RedirectResponse
    {
        $user = $request->user();
        $event = $this->guestService->eventFor($user);

        if (!$event) {
            return back()->with('error', 'Necesitas un evento para invitar coadministradores.');
        }

        if (!$this->coadminService->canInviteMore($event)) {
            return back()->with('error', 'Puedes tener hasta ' . CoadminService::MAX_PER_EVENT . ' coadministradores.');
        }

        $invitation = $this->coadminService->invite($event, $user, $request->validated('email'));

        return redirect()->route('organizer.settings.index')
            ->with('success', "Enviamos la invitación a {$invitation->email}.");
    }

    public function removeCoadmin(Request $request, int $coadmin): RedirectResponse
    {
        $event = $this->guestService->eventFor($request->user());
        abort_unless($event, 404);

        // Sólo invitaciones del propio evento: las ajenas no se encuentran.
        $invitation = $event->coadmins()->findOrFail($coadmin);
        $this->coadminService->remove($invitation);

        return redirect()->route('organizer.settings.index')
            ->with('success', "Se quitó el acceso a {$invitation->email}.");
    }
}
