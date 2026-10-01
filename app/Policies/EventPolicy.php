<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * La información del evento la editan el dueño y sus coadministradores.
     * (Configuración, invitados y notificaciones son sólo del dueño: esas
     * pantallas trabajan con el evento propio del usuario.)
     */
    public function update(User $user, Event $event): bool
    {
        // Una invitación vencida ya no se edita: se compra otra.
        return $event->isAvailable() && $event->isManagedBy($user);
    }
}
