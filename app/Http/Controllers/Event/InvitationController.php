<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventGuest;
use App\Services\EventService;
use App\Templates\TemplateRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View as ViewFactory;

/**
 * Invitación digital pública: /invitacion/{url-amigable}/{uuid-del-invitado?}.
 *
 * Con el uuid la invitación saluda a ese invitado por su nombre; sin él es la
 * liga abierta. Las direcciones anteriores del evento redirigen a la actual.
 */
class InvitationController extends Controller
{
    public function __construct(
        protected EventService $eventService,
        protected TemplateRenderer $renderer,
    ) {
    }

    public function show(string $slug, ?string $guest = null): View|RedirectResponse|Response
    {
        $event = Event::with('template')->where('custom_url', $slug)->first();
        // La pareja cambió su dirección: los enlaces ya enviados siguen sirviendo.
        if (!$event) {
            $previous = $this->eventService->findByPreviousUrl($slug);

            abort_unless($previous, 404);

            return redirect()->to($previous->invitationUrl() . ($guest ? '/' . $guest : ''), 301);
        }

        if (!$event->isAvailable()) {
            return response()->view('invitations.unavailable', ['event' => $event], 410);
        }

        $viewPath = $event->template?->view_path;
        abort_unless($viewPath && ViewFactory::exists($viewPath), 404);

        $invitation = $guest
            ? EventGuest::with('guest')->where('event_id', $event->id)->where('uuid', $guest)->first()
            : null;
        // $ver = $this->renderer->forEvent($event, $invitation);
        // dd($ver);
        return view($viewPath, $this->renderer->forEvent($event, $invitation));
    }
}
