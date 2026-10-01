<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\Guest;

/**
 * Texto que escribe el organizador, con marcadores que se sustituyen por los
 * datos de cada invitado. Es lo que muestra la vista previa de la pantalla.
 */
final class MessageTemplate
{
    /** Marcadores admitidos y su descripción para la interfaz. */
    public const PLACEHOLDERS = [
        'nombre' => 'Nombre del invitado',
        'url' => 'Enlace personal a su invitación',
    ];

    public function __construct(
        private readonly string $subject,
        private readonly string $body,
    ) {}

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    /**
     * Sustituye los marcadores con los datos del invitado. Acepta {{nombre}} y
     * {{ nombre }}, que es como suele quedar al copiar y pegar.
     */
    public function renderFor(Guest $guest, Event $event): RenderedMessage
    {
        $url = $guest->invitationFor($event)?->invitationUrl() ?? $event->invitationUrl();

        $values = [
            'nombre' => $guest->name,
            'url' => $url,
        ];

        $replace = fn (string $text) => preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/u',
            fn (array $match) => $values[$match[1]] ?? $match[0],
            $text
        );

        return new RenderedMessage(
            subject: $replace($this->subject),
            body: $replace($this->body),
            url: $url,
            guestName: $guest->name,
        );
    }
}
