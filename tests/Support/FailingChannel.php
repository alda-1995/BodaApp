<?php

namespace Tests\Support;

use App\Models\Guest;
use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\RenderedMessage;
use RuntimeException;

/**
 * Canal de prueba que siempre falla: sirve para comprobar que el error queda
 * registrado en el invitado y que el trabajo lo propaga para reintentar.
 *
 * De paso demuestra que agregar un medio es sólo implementar el contrato y
 * registrarlo en config/notifications.php.
 */
class FailingChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'failing';
    }

    public function label(): string
    {
        return 'Canal de prueba';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function canReach(Guest $guest): bool
    {
        return true;
    }

    public function unreachableReason(): string
    {
        return 'Sin datos';
    }

    public function send(Guest $guest, RenderedMessage $message): ?string
    {
        throw new RuntimeException('El proveedor caído no aceptó el mensaje.');
    }
}
