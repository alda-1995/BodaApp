<?php

namespace App\Services\Invitation;

use App\Models\Event;
use App\Models\EventGuest;
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
     * La pregunta personalizada que respondieron (cada boda pone la suya, por
     * ejemplo la canción), para titular su columna.
     *
     * @param  iterable<EventGuest>  $invitations
     */
    public function questionLabel(iterable $invitations): ?string
    {
        foreach ($invitations as $invitation) {
            $question = array_key_first($invitation->rsvp?->answers ?? []);

            if ($question) {
                return $question;
            }
        }

        return null;
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
