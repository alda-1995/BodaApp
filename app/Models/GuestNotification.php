<?php

namespace App\Models;

use App\Notifications\DeliveryIssue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Seguimiento del envío a un invitado: en qué estado quedó y por qué falló.
 */
class GuestNotification extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'guest_notification';

    protected $fillable = [
        'guest_id',
        'notification_id',
        'status',
        'error_message',
        'failure_code',
        'provider_message_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    /**
     * Envíos de un evento concreto.
     *
     * Esta tabla cuelga del invitado, que es del organizador y no de la boda, así
     * que un contacto arrastra los envíos de todas sus bodas. El evento se alcanza
     * por el Notification al que pertenece el envío.
     */
    public function scopeForEvent(Builder $query, int $eventId): Builder
    {
        return $query->whereHas('notification', fn ($notification) => $notification->where('event_id', $eventId));
    }

    public function markSent(?string $providerMessageId = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'provider_message_id' => $providerMessageId,
            'error_message' => null,
            'failure_code' => null,
            'sent_at' => now(),
        ]);
    }

    /**
     * No se envió y no tiene sentido reintentarlo. No cuenta para el cupo: el
     * mensaje nunca salió.
     */
    public function markSkipped(string $reason, string $code): void
    {
        $this->update([
            'status' => self::STATUS_SKIPPED,
            'error_message' => mb_substr($reason, 0, 250),
            'failure_code' => $code,
        ]);
    }

    public function markFailed(string $error, string $code = DeliveryIssue::UNKNOWN): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            // El texto del proveedor es sólo para soporte: no se muestra.
            'error_message' => mb_substr($error, 0, 250),
            'failure_code' => $code,
        ]);
    }

    /**
     * Un intento falló pero la cola lo reintentará: sigue en espera, guardando
     * ya el motivo por si se agotan los reintentos.
     */
    public function recordAttemptError(string $error, string $code = DeliveryIssue::UNKNOWN): void
    {
        $this->update([
            'error_message' => mb_substr($error, 0, 250),
            'failure_code' => $code,
        ]);
    }

    /**
     * Estado para mostrar al organizador. "Reintentando" distingue un envío en
     * espera que ya tuvo un intento fallido.
     */
    public function displayStatus(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'Enviado',
            self::STATUS_FAILED => 'Error',
            self::STATUS_SKIPPED => 'Omitido',
            default => $this->failure_code ? 'Reintentando' : 'En espera',
        };
    }

    /**
     * Explicación para el organizador. Nunca devuelve el texto del proveedor.
     *
     * @param string $channelLabel nombre del medio: "Correo", "WhatsApp"
     */
    public function issueMessage(string $channelLabel): string
    {
        if ($this->status === self::STATUS_QUEUED && !$this->failure_code) {
            return 'Se enviará en unos momentos.';
        }

        $explanation = DeliveryIssue::message($this->failure_code, $channelLabel);

        return $this->status === self::STATUS_QUEUED
            ? 'Estamos reintentándolo. ' . $explanation
            : $explanation;
    }
}
