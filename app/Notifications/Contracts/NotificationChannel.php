<?php

namespace App\Notifications\Contracts;

use App\Models\Guest;
use App\Notifications\RenderedMessage;

/**
 * Un medio de envío (correo, WhatsApp, y los que se agreguen).
 *
 * Los canales se registran en config/notifications.php. Nada más del sistema
 * conoce sus clases: se resuelven por su clave a través del ChannelManager.
 */
interface NotificationChannel
{
    /** Clave con la que se guarda y se pide el canal ('email', 'whatsapp'). */
    public function key(): string;

    /** Nombre visible en la interfaz ('Correo', 'WhatsApp'). */
    public function label(): string;

    /** ¿Están configuradas sus credenciales? Si no, no se ofrece en la pantalla. */
    public function isConfigured(): bool;

    /** ¿Este invitado tiene el dato que el canal necesita (correo, teléfono)? */
    public function canReach(Guest $guest): bool;

    /** Motivo corto para mostrar junto al invitado que no se puede alcanzar. */
    public function unreachableReason(): string;

    /**
     * Envía el mensaje. Debe lanzar una excepción si falla: el trabajo en cola
     * la usa para reintentar y para registrar el error del invitado.
     *
     * @return string|null Identificador del proveedor, si lo devuelve.
     */
    public function send(Guest $guest, RenderedMessage $message): ?string;
}
