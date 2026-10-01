<?php

namespace App\Notifications;

/**
 * Mensaje ya personalizado para un invitado concreto.
 */
final class RenderedMessage
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly string $url,
        public readonly string $guestName,
    ) {}
}
