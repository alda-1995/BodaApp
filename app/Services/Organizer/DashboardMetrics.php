<?php

namespace App\Services\Organizer;

use App\Models\Event;
use App\Models\GuestNotification;
use App\Models\Rsvp;
use App\Services\NotificationService;

/**
 * Las cifras del panel del organizador.
 *
 * Son las cuatro tarjetas de su tablero: cuánto falta para la boda, a cuántos
 * invitados ya les llegó la invitación, cuántos contestaron y cuántos mensajes
 * le quedan este mes.
 *
 * Cada una sale de donde ya vive el dato —los envíos de guest_notification, las
 * respuestas de rsvps, el cupo de NotificationService— para que el panel no
 * tenga su propia versión de la verdad.
 */
class DashboardMetrics
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function for(Event $event): array
    {
        $invitados = $event->guests()->count();
        $enviadas = $this->reached($event);

        return [
            'countdown' => $this->countdown($event),
            'guests' => $invitados,
            'sent' => $enviadas,
            // Nunca negativo: si alguien borra invitados ya notificados, la
            // resta se iría abajo de cero y la tarjeta diría un disparate.
            'pending' => max(0, $invitados - $enviadas),
            'confirmed' => $this->confirmed($event),
            'confirmed_passes' => $this->confirmedPasses($event),
            'remaining_messages' => $this->notifications->remainingQuota($event),
        ];
    }

    /**
     * Cuánto falta para la boda, ya escrito para la tarjeta.
     *
     * Sin fecha no hay cuenta regresiva: el organizador todavía no la eligió y
     * un "0 días" se leería como que es hoy.
     */
    private function countdown(Event $event): string
    {
        $fecha = $event->event_date;

        if ($fecha === null) {
            return 'Sin fecha';
        }

        if ($fecha->isToday()) {
            return '¡Es hoy!';
        }

        if ($fecha->isPast()) {
            return 'Ya se casaron';
        }

        /*
        | Se comparan días de calendario, no instantes. Restando timestamps,
        | una boda dentro de 45 días da 44: Carbon trunca lo que falta para
        | completar el día. Y "faltan 3 días" no depende de la hora.
        */
        $dias = now()->startOfDay()->diffInDays($fecha->copy()->startOfDay());

        return $dias === 1 ? '1 día' : $dias . ' días';
    }

    /**
     * A cuántos invitados les llegó al menos un mensaje.
     *
     * Se cuentan invitados y no envíos: a uno se le puede mandar la invitación
     * y después un recordatorio, y seguiría siendo un solo invitado alcanzado.
     */
    private function reached(Event $event): int
    {
        return GuestNotification::query()
            ->whereHas('notification', fn ($query) => $query->where('event_id', $event->id))
            ->where('status', GuestNotification::STATUS_SENT)
            ->distinct()
            ->count('guest_id');
    }

    /** Invitados que dijeron que sí. */
    private function confirmed(Event $event): int
    {
        return $this->rsvps($event)->where('attendance', 'confirmed')->count();
    }

    /** Lugares apartados por quienes confirmaron: lo que importa para la mesa. */
    private function confirmedPasses(Event $event): int
    {
        return (int) $this->rsvps($event)->where('attendance', 'confirmed')->sum('confirmed_passes');
    }

    private function rsvps(Event $event)
    {
        return Rsvp::query()
            ->whereHas('eventGuest', fn ($query) => $query->where('event_id', $event->id));
    }
}
