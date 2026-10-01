<?php

namespace App\Services;

use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Event\EventDTO;
use App\DTOs\Order\CreateCheckoutSessionDTO;
use App\DTOs\Payment\ProcessWebhookOrderDTO;
use App\Exceptions\Order\ActiveEventAlreadyExistsException;
use App\Models\Event;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use DateTime;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Throwable;

class OrderService
{
    protected StripeClient $stripe;

    public function __construct(
        StripeClient $stripe,
        protected TemplateService $templateService,
        protected AuthService $authService,
        protected EventService $eventService
    ) {
        $this->stripe = $stripe;
    }

    public function getPaginatedByUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return Order::with(['template', 'event'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findByUserAndId(int $userId, int $orderId): ?Order
    {
        return Order::with(['template', 'event'])
            ->where('user_id', $userId)
            ->where('id', $orderId)
            ->first();
    }

    public function createStripeCheckoutSession(CreateCheckoutSessionDTO $dto): string
    {
        try {
            $template = $this->templateService->find($dto->templateId);
            if (!$template) {
                throw new Exception('La plantilla no existe.');
            }

            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'customer_email' => $dto->customerEmail,
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'mxn',
                            'product_data' => [
                                'name' => "Plantilla Invitación: {$template->name}",
                            ],
                            'unit_amount' => (int) ($template->price * 100),
                        ],
                        'quantity' => 1,
                    ]
                ],
                'metadata' => [
                    'template_id' => $template->id,
                ],
                'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel'),
            ]);

            return $session->url;
        } catch (Exception $e) {
            Log::error('Error al crear la sesión de pago: ' . $e->getMessage(), [
                'dto' => (array) $dto,
                'trace' => $e->getTraceAsString(),
            ]);
            throw new Exception("Lo sentimos, no pudimos generar el proceso de pago.");
        }
    }

    public function createOrderCompleted(ProcessWebhookOrderDTO $dto, int $userId): Order
    {
        return Order::create([
            'user_id' => $userId,
            'template_id' => $dto->templateId,
            'stripe_session_id' => $dto->stripeSessionId,
            'amount' => $dto->amountTotal,
            'currency' => $dto->currency,
            'status' => 'completed',
        ]);
    }

    public function fulfillWebhookOrder(ProcessWebhookOrderDTO $dto): Order
    {
        try {
            return DB::transaction(function () use ($dto) {
                $existingOrder = Order::where('stripe_session_id', $dto->stripeSessionId)->first();
                if ($existingOrder) {
                    Log::info("Webhook Stripe: Sesión {$dto->stripeSessionId} ya había sido procesada previamente.");
                    return $existingOrder;
                }

                $user = $this->authService->getUserWithEmail($dto->customerEmail);

                if (!$user) {
                    $dtoUser = new RegisterDTO(
                        name: $dto->customerName ?? 'Default',
                        email: $dto->customerEmail,
                        password: Str::random(16)
                    );

                    $user = $this->authService->register($dtoUser, 'organizer');
                } else {
                    // Una cuenta que ya existía (p. ej. un coadministrador) se vuelve
                    // organizadora al comprar su propia invitación.
                    $user->roles()->syncWithoutDetaching([Role::named('organizer')->id]);
                }

                $order = $this->createOrderCompleted($dto, $user->id);

                $dtoEvent = new EventDTO(
                    userId: $user->id,
                    templateId: $dto->templateId,
                    orderId: $order->id,
                    // Sin fecha: la captura la pareja en el wizard y de ahí sale la vigencia.
                    features: [],
                    isActive: true,
                );

                $this->eventService->createEvent($dtoEvent);

                return $order;
            });
        } catch (Throwable $e) {
            Log::error("Error crítico al aprovisionar la orden para la sesión {$dto->stripeSessionId}: " . $e->getMessage(), [
                'exception' => $e,
                'dto' => $dto,
            ]);

            throw $e;
        }
    }

    public function getOrderStatusBySession(string $sessionId): array
    {
        $order = Order::where('stripe_session_id', $sessionId)
            ->with(['event', 'user'])
            ->first();

        if ($order) {
            return [
                'status' => $order->status,
                'order_id' => $order->id,
                'email' => $order->user?->email,
                'message' => 'Orden completada con éxito.',
            ];
        }

        try {
            $session = $this->stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                return [
                    'status' => 'pending',
                    'message' => 'El pago fue autorizado y estamos activando tu cuenta...',
                ];
            }

            if ($session->status === 'expired') {
                return [
                    'status' => 'expired',
                    'message' => 'La sesión de pago ha expirado. Por favor intenta realizar la compra nuevamente.',
                ];
            }

            // Si el Intento de Pago falló explícitamente en Stripe
            if ($session->payment_intent) {
                $paymentIntent = $this->stripe->paymentIntents->retrieve($session->payment_intent);

                if ($paymentIntent->status === 'requires_payment_method' && !empty($paymentIntent->last_payment_error)) {
                    return [
                        'status' => 'failed',
                        'message' => $paymentIntent->last_payment_error->message ?? 'El pago fue rechazado por la entidad bancaria.',
                    ];
                }
            }
        } catch (ApiErrorException $e) {
            Log::warning("No se pudo consultar la sesión {$sessionId} en Stripe: " . $e->getMessage());
        }

        return [
            'status' => 'pending',
            'message' => 'Procesando tu pago...',
        ];
    }

    public function processRefund(string $paymentIntentId): void
    {
        try {
            $sessions = $this->stripe->checkout->sessions->all([
                'payment_intent' => $paymentIntentId,
                'limit' => 1,
            ]);

            if (empty($sessions->data)) {
                Log::warning("Webhook Stripe: No se encontró Checkout Session para el reembolso: {$paymentIntentId}");
                return;
            }

            $sessionId = $sessions->data[0]->id;

            DB::transaction(function () use ($sessionId, $paymentIntentId) {
                $order = Order::where('stripe_session_id', $sessionId)->first();

                if (!$order) {
                    Log::warning("Webhook Stripe: No existe orden local para la sesión {$sessionId}");
                    return;
                }

                $order->update(['status' => 'refunded']);
                $this->eventService->deactivateByOrderId($order->id);

                Log::info("Orden {$order->id} y su evento asociado procesados por reembolso exitosamente.");
            });

        } catch (Exception $e) {
            Log::error("Error en el proceso de guardado para refund: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}