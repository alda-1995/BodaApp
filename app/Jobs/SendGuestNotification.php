<?php

namespace App\Jobs;

use App\Models\GuestNotification;
use App\Notifications\ChannelManager;
use App\Notifications\DeliveryIssue;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envía el mensaje a un invitado por el medio elegido.
 *
 * Uno por invitado: si un envío falla, se reintenta solo ese y los demás siguen
 * su curso. El resultado queda guardado en su fila de seguimiento.
 */
class SendGuestNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Espera entre reintentos: el proveedor puede estar saturado. */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $deliveryId)
    {
    }

    public function handle(ChannelManager $channels): void
    {
        $delivery = GuestNotification::with(['guest', 'notification.event'])->find($this->deliveryId);

        if (!$delivery || $delivery->status === GuestNotification::STATUS_SENT) {
            return; // ya se envió o el registro se borró
        }

        /*
         * La invitación pudo vencer —o el superadmin apagarla— entre que el
         * organizador mandó el lote y la cola llegó a este envío. No se manda un
         * mensaje con un enlace que ya responde "no disponible": queda omitido,
         * que no se reintenta ni consume cupo, y con su motivo a la vista.
         */
        if (!$delivery->notification->event?->isAvailable()) {
            $delivery->markSkipped(
                'La invitación ya no estaba activa al momento del envío.',
                DeliveryIssue::EVENT_UNAVAILABLE,
            );

            Log::info('Envío omitido: la invitación ya no estaba activa', [
                'delivery_id' => $delivery->id,
                'event_id' => $delivery->notification->event_id,
            ]);

            return;
        }

        $channel = $channels->get($delivery->notification->channel);
        $message = $delivery->notification->template()->renderFor($delivery->guest, $delivery->notification->event);

        try {
            $delivery->markSent($channel->send($delivery->guest, $message));
        } catch (Throwable $e) {
            // Sigue en espera: la cola lo reintentará. Sólo pasa a error cuando se
            // agotan los reintentos (ver failed()), para no mostrar como fallido
            // algo que todavía puede llegar.
            $delivery->recordAttemptError($e->getMessage(), DeliveryIssue::classify($e));

            Log::warning('Falló un intento de envío a invitado', [
                'delivery_id' => $delivery->id,
                'channel' => $delivery->notification->channel,
                'error' => $e->getMessage(),
            ]);

            throw $e; // deja que la cola reintente
        }
    }

    /**
     * Se agotaron los reintentos: el envío queda como error definitivo.
     */
    public function failed(Throwable $e): void
    {
        GuestNotification::find($this->deliveryId)?->markFailed($e->getMessage(), DeliveryIssue::classify($e));

        Log::error('Envío a invitado fallido tras agotar los reintentos', [
            'delivery_id' => $this->deliveryId,
            'error' => $e->getMessage(),
        ]);
    }
}
