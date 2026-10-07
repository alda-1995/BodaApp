<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Entrada "Información del evento" del menú: lleva al wizard de la invitación
 * propia o, si no hay una vigente, invita a comprar una.
 */
class EventInfoController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $event = $user->currentEvent();

        if ($event?->isAvailable()) {
            return redirect()->route('events.wizard.edit', ['event' => $event->slug]);
        }

        return view('organizer.event-info.unavailable', [
            'pastEvents' => $user->pastEvents(),
            'hasSharedEvents' => $user->coadminships()->accepted()->exists(),
        ]);
    }
}
