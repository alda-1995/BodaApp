<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\SendNotificationsRequest;
use App\Models\GuestNotification;
use App\Notifications\ChannelManager;
use App\Notifications\MessageTemplate;
use App\Services\GuestService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected GuestService $guestService,
        protected NotificationService $notificationService,
        protected ChannelManager $channels,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $event = $this->guestService->eventFor($user);

        $filter = in_array($request->query('filtro'), [
            NotificationService::FILTER_PENDING,
            NotificationService::FILTER_NOT_NOTIFIED,
            NotificationService::FILTER_DELIVERIES,
        ], true) ? $request->query('filtro') : NotificationService::FILTER_ALL;

        $showingDeliveries = $filter === NotificationService::FILTER_DELIVERIES;

        return view('organizer.notifications.index', [
            'event' => $event,
            'invitations' => $event && !$showingDeliveries
                ? $this->notificationService->invitationsFor($event, $filter)
                : collect(),
            'deliveries' => $event && $showingDeliveries
                ? $this->notificationService->deliveriesNeedingAttention($event)
                : null,
            'attentionCount' => $event ? $this->notificationService->attentionCount($event) : 0,
            'channelLabels' => $this->channels->labels(),
            'filter' => $filter,
            'channels' => $this->channels->available(),
            'remaining' => $event ? $this->notificationService->remainingQuota($event) : 0,
            'defaultMessage' => 'Hola {{nombre}}, esperamos que estés muy bien. No olvides confirmar tu asistencia a nuestra boda.',
        ]);
    }

    public function send(SendNotificationsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $event = $this->guestService->eventFor($user);

        if (!$event) {
            return back()->with('error', 'Necesitas un evento para enviar notificaciones.');
        }

        // Sólo invitados del organizador: ids ajenos simplemente no se encuentran.
        $guests = $user->guests()->whereIn('id', $request->validated('guest_ids'))->get();

        if ($guests->isEmpty()) {
            return back()->with('error', 'No se encontraron los invitados seleccionados.');
        }

        $remaining = $this->notificationService->remainingQuota($event);

        if ($guests->count() > $remaining) {
            return back()->with('error', "Te quedan {$remaining} mensajes este mes y seleccionaste {$guests->count()}.");
        }

        $channel = $this->channels->get($request->validated('channel'));

        $notification = $this->notificationService->send(
            $event,
            $channel->key(),
            $guests,
            new MessageTemplate($event->title ?: 'Invitación', $request->validated('message')),
        );

        $queued = $notification->deliveries()->where('status', GuestNotification::STATUS_QUEUED)->count();
        $skipped = $notification->deliveries()->where('status', GuestNotification::STATUS_SKIPPED)->count();

        $message = "Se están enviando {$queued} mensajes por {$channel->label()}.";

        if ($skipped > 0) {
            $message .= " {$skipped} se omitieron: {$channel->unreachableReason()}.";
        }

        return back()->with($queued > 0 ? 'success' : 'error', $queued > 0
            ? $message
            : "No se envió ningún mensaje: {$skipped} invitados quedaron omitidos ({$channel->unreachableReason()}).");
    }
}
