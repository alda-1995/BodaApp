<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitación para administrar un evento junto a su dueño.
 */
class CoadminInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $inviterName,
        public readonly string $coupleNames,
        public readonly string $acceptUrl,
        public readonly int $expiresInDays,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->inviterName} te invitó a administrar la boda de {$this->coupleNames}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.coadmin-invitation');
    }
}
