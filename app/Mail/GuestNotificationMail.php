<?php

namespace App\Mail;

use App\Notifications\RenderedMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mensaje que el organizador envía a un invitado (invitación o recordatorio).
 */
class GuestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Se llama $notification y no $message: dentro de una vista de correo,
     * $message ya es el mensaje que arma el mailer, y tapar ese nombre deja a
     * la plantilla sin datos al momento de enviar.
     */
    public function __construct(public readonly RenderedMessage $notification)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notification->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.guest-notification');
    }
}
