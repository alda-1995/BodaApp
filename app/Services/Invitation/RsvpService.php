<?php

namespace App\Services\Invitation;

use App\Models\Event;
use App\Models\EventGuest;
use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Confirmaciones que llegan desde la invitación pública.
 *
 * Cada confirmación pertenece a la invitación del invitado (event_guest), no al
 * invitado suelto: la misma persona puede estar en varias bodas.
 */
class RsvpService
{
    /**
     * Guarda (o actualiza) la respuesta de un invitado.
     *
     * @param  array{attendance: string, passes: int, dietary_restrictions: ?string, answers: array<string, string>}  $data
     */
    public function confirm(EventGuest $invitation, array $data): Rsvp
    {
        $attendance = $data['attendance'];

        return Rsvp::updateOrCreate(
            ['event_guest_id' => $invitation->id],
            [
                'attendance' => $attendance,
                // Quien no asiste no aparta lugares.
                'confirmed_passes' => $attendance === 'confirmed' ? $data['passes'] : 0,
                'dietary_restrictions' => $data['dietary_restrictions'] ?? null,
                'comments' => $data['comments'] ?? null,
                'answers' => $data['answers'] ?: null,
                'confirmed_at' => now(),
            ]
        );
    }

    /**
     * Invitación para quien llegó por el enlace abierto: se da de alta como
     * invitado del organizador y se le arma su propio enlace personal.
     */
    public function invitationForOpenLink(Event $event, array $guestData, int $maxPasses): EventGuest
    {
        return DB::transaction(function () use ($event, $guestData, $maxPasses) {
            $guest = Guest::create([
                'user_id' => $event->user_id,
                'name' => $guestData['name'],
                'phone' => $guestData['phone'] ?? null,
                'email' => $guestData['email'] ?? null,
            ]);

            $guest->events()->attach($event->id, [
                'uuid' => (string) Str::uuid(),
                'max_passes' => $maxPasses,
            ]);

            return $guest->invitationFor($event->fresh());
        });
    }
}
