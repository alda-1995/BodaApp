<?php

namespace App\Services;

use App\Jobs\SendGuestNotification;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\GuestNotification;
use App\Models\Notification;
use App\Notifications\ChannelManager;
use App\Notifications\DeliveryIssue;
use App\Notifications\MessageTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta los envíos a los invitados: arma el registro, decide a quién se puede
 * alcanzar por el medio elegido y encola un trabajo por invitado.
 *
 * No conoce ningún medio en concreto: los pide al ChannelManager por su clave.
 */
class NotificationService
{
    public const FILTER_ALL = 'all';
    public const FILTER_PENDING = 'pending';
    public const FILTER_NOT_NOTIFIED = 'not_notified';

    /** Pestaña de seguimiento: envíos en espera, con error u omitidos. */
    public const FILTER_DELIVERIES = 'envios';

    /** Estados que requieren atención del organizador. */
    private const ATTENTION_STATUSES = [
        GuestNotification::STATUS_QUEUED,
        GuestNotification::STATUS_FAILED,
        GuestNotification::STATUS_SKIPPED,
    ];

    public function __construct(private readonly ChannelManager $channels)
    {
    }

    /**
     * Invitaciones del evento con su confirmación y cuántos envíos han recibido.
     *
     * @return Collection<int, EventGuest>
     */
    public function invitationsFor(Event $event, string $filter = self::FILTER_ALL): Collection
    {
        return EventGuest::query()
            ->select('event_guest.*')
            ->join('guests', 'guests.id', '=', 'event_guest.guest_id')
            ->where('event_guest.event_id', $event->id)
            ->with([
                'rsvp',
                // Sólo los envíos de ESTA boda: el invitado es del organizador y
                // arrastra los de las anteriores.
                'guest' => fn ($query) => $query->withCount([
                    'notifications as sent_notifications_count' => fn ($sent) => $sent
                        ->forEvent($event->id)
                        ->where('status', GuestNotification::STATUS_SENT),
                ]),
            ])
            ->when($filter === self::FILTER_PENDING, fn ($query) => $query->where(
                fn ($pending) => $pending->whereDoesntHave('rsvp')
                    ->orWhereHas('rsvp', fn ($rsvp) => $rsvp->where('attendance', '!=', 'confirmed'))
            ))
            ->when($filter === self::FILTER_NOT_NOTIFIED, fn ($query) => $query->whereDoesntHave(
                'guest.notifications',
                fn ($sent) => $sent
                    ->forEvent($event->id)
                    ->where('status', GuestNotification::STATUS_SENT)
            ))
            ->orderBy('guests.name')
            ->get();
    }

    /**
     * Registra el envío y encola un trabajo por invitado alcanzable. Los que no
     * tienen el dato del medio (correo o teléfono) quedan como omitidos, con su
     * motivo, y no consumen cupo.
     */
    public function send(Event $event, string $channelKey, Collection $guests, MessageTemplate $template): Notification
    {
        $channel = $this->channels->get($channelKey);

        [$notification, $queuedIds] = DB::transaction(function () use ($event, $channel, $guests, $template) {
            $notification = Notification::create([
                'event_id' => $event->id,
                'title' => $template->subject(),
                'message' => $template->body(),
                'channel' => $channel->key(),
            ]);

            $queuedIds = [];

            foreach ($guests as $guest) {
                $reachable = $channel->canReach($guest);

                $delivery = $notification->deliveries()->create([
                    'guest_id' => $guest->id,
                    'status' => $reachable ? GuestNotification::STATUS_QUEUED : GuestNotification::STATUS_SKIPPED,
                    'error_message' => $reachable ? null : $channel->unreachableReason(),
                    'failure_code' => $reachable ? null : DeliveryIssue::MISSING_CONTACT,
                ]);

                if ($reachable) {
                    $queuedIds[] = $delivery->id;
                }
            }

            return [$notification, $queuedIds];
        });

        // Fuera de la transacción: ningún trabajo puede empezar antes de que sus
        // filas existan, y no depende de callbacks de commit.
        foreach ($queuedIds as $deliveryId) {
            SendGuestNotification::dispatch($deliveryId);
        }

        return $notification;
    }

    /**
     * Envíos del evento que no llegaron (todavía): en espera, con error u
     * omitidos. Los enviados con éxito no aparecen. Lo más reciente primero.
     */
    public function deliveriesNeedingAttention(Event $event, int $perPage = 20): LengthAwarePaginator
    {
        return GuestNotification::query()
            ->with(['guest', 'notification'])
            ->forEvent($event->id)
            ->whereIn('status', self::ATTENTION_STATUSES)
            ->latest('updated_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Cuántos envíos siguen en espera o fallaron (los omitidos no cuentan: no
     * hay nada que esperar de ellos).
     */
    public function attentionCount(Event $event): int
    {
        return GuestNotification::query()
            ->forEvent($event->id)
            ->whereIn('status', [GuestNotification::STATUS_QUEUED, GuestNotification::STATUS_FAILED])
            ->count();
    }

    /** Mensajes usados este mes por el evento, sumando todos los medios. */
    public function usedQuota(Event $event): int
    {
        return GuestNotification::query()
            ->forEvent($event->id)
            ->where('status', '!=', GuestNotification::STATUS_SKIPPED)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    public function remainingQuota(Event $event): int
    {
        return max(0, (int) config('notifications.monthly_quota') - $this->usedQuota($event));
    }
}
