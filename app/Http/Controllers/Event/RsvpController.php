<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\StoreRsvpRequest;
use App\Models\Event;
use App\Models\EventGuest;
use App\Services\Invitation\RsvpService;
use App\Services\Template\TemplateDiscoveryService;
use App\Services\EventWizardService;
use Illuminate\Http\JsonResponse;

/**
 * Confirmaciones que manda el formulario de la invitación pública.
 *
 * Responde siempre JSON: la invitación no recarga, sólo cambia de mensaje.
 */
class RsvpController extends Controller
{
    public function __construct(
        protected RsvpService $rsvps,
        protected TemplateDiscoveryService $discovery,
        protected EventWizardService $wizard,
    ) {
    }

    public function store(StoreRsvpRequest $request, string $slug): JsonResponse
    {
        $event = Event::with('template')->where('custom_url', $slug)->first();

        if (!$event || !$event->isAvailable()) {
            return $this->fail('Esta invitación ya no está disponible.', 410);
        }

        $settings = $this->rsvpSettings($event);

        if ($settings['closed']) {
            return $this->fail('La fecha límite para confirmar ya pasó. Escríbele a los novios.', 422);
        }

        $data = $request->validated();
        $invitation = $this->invitationFor($event, $data['uuid'] ?? null);

        // Sin invitación personal sólo se confirma si el enlace abierto está activo.
        if (!$invitation && !$settings['open_link_enabled']) {
            return $this->fail('Lo sentimos, esta es una celebración privada. Sólo se puede confirmar desde la invitación personal que envían los novios.', 403);
        }

        /*
         * Los novios mirando su propia invitación. Entran a ver cómo va quedando
         * y la liga abierta no identifica a nadie, así que una confirmación suya
         * sería un invitado inventado en su propia lista.
         *
         * Sólo por la liga abierta: con una liga personal están respondiendo por
         * ese invitado, que es algo que hacen de verdad.
         */
        if (!$invitation && ($user = $request->user()) && $event->isManagedBy($user)) {
            return $this->fail('Estás viendo tu propia invitación: desde aquí no se confirma. Comparte el enlace con tus invitados.', 403);
        }

        if ($invitation?->rsvp) {
            return $this->fail('Esta invitación ya había confirmado. Si necesitas cambiar algo, avísale a los novios.', 409);
        }

        $maxPasses = $invitation
            ? (int) $invitation->max_passes
            : (int) $settings['open_link_max_passes'];

        if (($data['attendance'] === 'confirmed') && (int) ($data['passes'] ?? 0) > $maxPasses) {
            return response()->json([
                'message' => 'Revisa los datos del formulario.',
                'errors' => ['passes' => ["Tu invitación considera {$maxPasses} lugares."]],
            ], 422);
        }

        $invitation ??= $this->rsvps->invitationForOpenLink($event, $data, $maxPasses);

        $this->rsvps->confirm($invitation, [
            'attendance' => $data['attendance'],
            'passes' => (int) ($data['passes'] ?? 1),
            'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
            'answers' => array_filter($data['answers'] ?? []),
        ]);

        return response()->json([
            'attendance' => $data['attendance'],
            'message' => $data['attendance'] === 'confirmed'
                ? '¡Gracias por confirmar tu asistencia!'
                : 'Gracias por avisarnos.',
        ]);
    }

    private function invitationFor(Event $event, ?string $uuid): ?EventGuest
    {
        if (!filled($uuid)) {
            return null;
        }

        return EventGuest::with('rsvp')
            ->where('event_id', $event->id)
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Lo que el organizador dejó capturado en el paso de confirmación.
     *
     * @return array<string, mixed>
     */
    private function rsvpSettings(Event $event): array
    {
        $strategy = $this->discovery->resolveStrategy($event->template?->view_path);
        $section = collect($strategy->sections())->firstWhere(fn ($section) => $section->key() === 'rsvp');

        if (!$section) {
            return ['closed' => false, 'open_link_enabled' => true, 'open_link_max_passes' => 1];
        }

        $values = $this->wizard->resolveSavedValues($event, $strategy, 'rsvp', $event->files()->get());

        return $section->data($values, []);
    }

    private function fail(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
