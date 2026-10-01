<?php
namespace App\Contracts\Notification;

interface NotificationStrategyInterface
{
    /**
     * Envía un mensaje/notificación al destinatario.
     *
     * @param string $to
     * @param string $message
     * @param array $payload
     * @return bool
     */
    public function send(string $to, string $message, array $payload = []): bool;
}