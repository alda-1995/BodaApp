<?php

namespace App\Notifications\Channels;

use App\Mail\GuestNotificationMail;
use App\Models\Guest;
use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\RenderedMessage;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'email';
    }

    public function label(): string
    {
        return 'Correo';
    }

    public function isConfigured(): bool
    {
        return filled(config('mail.from.address'));
    }

    public function canReach(Guest $guest): bool
    {
        return filled($guest->email);
    }

    public function unreachableReason(): string
    {
        return 'Sin correo';
    }

    public function send(Guest $guest, RenderedMessage $message): ?string
    {
        Mail::to($guest->email)->send(new GuestNotificationMail($message));

        // El envío es asíncrono del lado del proveedor: no hay id que guardar aquí.
        return null;
    }
}
