<?php

namespace App\Services\Invitation;

use App\Models\Event;
use App\Models\EventGuest;
use App\Services\EventWizardService;
use App\Services\Template\TemplateDiscoveryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lo que el organizador ve en "Confirmación de Asistencia".
 *
 * Todo se cuenta sobre las invitaciones del evento (event_guest): una fila por
 * invitado invitado a esta boda, haya respondido o no.
 */
class RsvpReportService
{
    public const TAB_CONFIRMED = 'confirmed';
    public const TAB_NOT_CONFIRMED = 'not_confirmed';

    public function __construct(
        private readonly TemplateDiscoveryService $discovery,
        private readonly EventWizardService $wizard,
    ) {
    }

    /**
     * @return array{responses: int, people: int, pending: int}
     */
    public function statsFor(Event $event): array
    {
        $invitations = $this->query($event)->get();

        return [
            // Cuántas invitaciones respondieron, asistan o no.
            'responses' => $invitations->whereNotNull('rsvp')->count(),
            'people' => (int) $invitations
                ->where('rsvp.attendance', 'confirmed')
                ->sum(fn (EventGuest $invitation) => $invitation->rsvp->confirmed_passes),
            'pending' => $invitations->whereNull('rsvp')->count(),
        ];
    }

    public function paginate(Event $event, string $tab, ?string $search = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($event)
            ->when($search, fn (Builder $query, string $term) => $query->whereHas(
                'guest',
                fn (Builder $guest) => $guest->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
            ))
            ->when(
                $tab === self::TAB_CONFIRMED,
                // Confirmados: los que dijeron que sí.
                fn (Builder $query) => $query->whereHas('rsvp', fn (Builder $rsvp) => $rsvp->where('attendance', 'confirmed')),
                // El resto: quienes no han respondido y quienes dijeron que no.
                fn (Builder $query) => $query->whereDoesntHave('rsvp', fn (Builder $rsvp) => $rsvp->where('attendance', 'confirmed'))
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @return Collection<int, EventGuest> */
    public function confirmed(Event $event): Collection
    {
        return $this->query($event)
            ->whereHas('rsvp', fn (Builder $rsvp) => $rsvp->where('attendance', 'confirmed'))
            ->get();
    }

    /**
     * Las preguntas que titulan las columnas, en orden.
     *
     * Primero las que el organizador configuró en su wizard, en el orden en que
     * las puso, haya contestado alguien o no: así la tabla enseña la misma
     * forma desde el primer día y no cambia de columnas según quién responda.
     *
     * Después, cualquier otra respuesta guardada que no esté configurada. Son
     * las que no salen del repetidor —el recado para los novios, que la
     * plantilla trae fijo, o una pregunta que el organizador borró después—:
     * se contestaron, así que no se pueden esconder.
     *
     * @param  iterable<EventGuest>  $invitations
     * @return array<int, string>
     */
    public function questionLabels(Event $event, iterable $invitations): array
    {
        $preguntas = $this->configuredQuestions($event);

        foreach ($invitations as $invitation) {
            foreach (array_keys($invitation->rsvp?->answers ?? []) as $respondida) {
                if (!in_array($respondida, $preguntas, true)) {
                    $preguntas[] = $respondida;
                }
            }
        }

        return $preguntas;
    }

    /**
     * Las preguntas tal como quedaron en el paso de confirmación.
     *
     * Se le piden a la sección y no a features en crudo, para que valga aquí lo
     * mismo que en la invitación: con las preguntas apagadas no hay ninguna,
     * aunque sigan guardadas.
     *
     * @return array<int, string>
     */
    private function configuredQuestions(Event $event): array
    {
        $strategy = $this->discovery->resolveStrategy($event->template?->view_path);

        if (!method_exists($strategy, 'sections')) {
            return [];
        }

        foreach ($strategy->sections() as $section) {
            if ($section->key() !== 'rsvp') {
                continue;
            }

            $values = $this->wizard->resolveSavedValues($event, $strategy, 'rsvp');

            // Cada pregunta viaja con su aclaración; la columna sólo necesita
            // el texto, que además es la llave con la que se guardó la respuesta.
            return array_column($section->data($values, [])['custom_questions'] ?? [], 'question');
        }

        return [];
    }

    private function query(Event $event): Builder
    {
        return EventGuest::query()
            ->with(['guest', 'rsvp'])
            ->where('event_id', $event->id)
            ->join('guests', 'guests.id', '=', 'event_guest.guest_id')
            ->orderBy('guests.name')
            ->select('event_guest.*');
    }
}
