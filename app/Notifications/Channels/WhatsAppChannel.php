<?php

namespace App\Notifications\Channels;

use App\Models\Guest;
use App\Notifications\Contracts\NotificationChannel;
use App\Notifications\RenderedMessage;
use Twilio\Rest\Client;

/**
 * WhatsApp por Twilio.
 *
 * Twilio exige una plantilla aprobada (contentSid) para escribir a alguien fuera
 * de la ventana de 24 horas: no se puede enviar texto libre. Por eso el mensaje
 * del organizador viaja como variables de esa plantilla (nombre y enlace).
 */
class WhatsAppChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'whatsapp';
    }

    public function label(): string
    {
        return 'WhatsApp';
    }

    public function isConfigured(): bool
    {
        return filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.twilio.whatsapp_from'))
            && filled(config('services.twilio.whatsapp_template_id'));
    }

    public function canReach(Guest $guest): bool
    {
        return filled($guest->phone);
    }

    public function unreachableReason(): string
    {
        return 'Sin teléfono';
    }

    public function send(Guest $guest, RenderedMessage $message): ?string
    {
        $sent = $this->client()->messages->create('whatsapp:' . $guest->phone, [
            'from' => config('services.twilio.whatsapp_from'),
            'messagingServiceSid' => config('services.twilio.services_id'),
            'contentSid' => config('services.twilio.whatsapp_template_id'),
            'contentVariables' => json_encode([
                '1' => $message->guestName,
                '2' => $message->url,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        return $sent->sid;
    }

    /**
     * El cliente se crea al enviar: así isConfigured() puede consultarse aunque
     * no haya credenciales, sin reventar al construir la clase.
     */
    protected function client(): Client
    {
        return app(Client::class);
    }
}
