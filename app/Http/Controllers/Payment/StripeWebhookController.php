<?php

namespace App\Http\Controllers\Payment;

use App\DTOs\Payment\ProcessWebhookOrderDTO;
use App\Http\Controllers\Controller;
use App\Jobs\SendOnboardingEmailIfAbandoned;
use App\Services\AuthService;
use App\Services\EventService;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected AuthService $authService,
        protected EventService $eventService
    ) {
    }

    public function handleWebhook(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            return response('Firma de Webhook inválida.', 400);
        } catch (Exception $e) {
            return response('Payload inválido.', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                return $this->handleCheckoutSessionCompleted($event->data->object);

            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($event->data->object);

            case 'charge.refunded':
                return $this->handleChargeRefunded($event->data->object);

            case 'checkout.session.expired':
                return $this->handleCheckoutSessionExpired($event->data->object);

            default:
                Log::info("Webhook de Stripe ignorado: {$event->type}");
                return response("Evento no manejado: {$event->type}", 200);
        }
    }

    protected function handleCheckoutSessionCompleted(object $session): Response
    {
        if ($session->payment_status !== 'paid') {
            return response('Sesión completada pero el pago está pendiente.', 200);
        }

        $dto = ProcessWebhookOrderDTO::fromStripeSession($session);

        try {
            $order = $this->orderService->fulfillWebhookOrder($dto);
            $user = $order->user;

            if ($user && (is_null($user->password_changed_at))) {
                SendOnboardingEmailIfAbandoned::dispatch($user)
                    ->delay(now()->addHours(3));
            }
        } catch (Exception $e) {
            Log::error('Error procesando checkout.session.completed en Stripe Webhook', [
                'email' => $dto->customerEmail,
                'error' => $e->getMessage(),
            ]);
            return response('Error interno al procesar la orden.', 500);
        }

        return response('Checkout procesado exitosamente.', 200);
    }

    /**
     * Pago fallido / Tarjeta rechazada.
     */
    protected function handlePaymentFailed(object $paymentIntent): Response
    {
        $customerEmail = $paymentIntent->receipt_email ?? $paymentIntent->last_payment_error->charge->billing_details->email ?? null;
        $failureReason = $paymentIntent->last_payment_error->message ?? 'Razón desconocida';

        Log::warning('Stripe Webhook: Pago fallido', [
            'payment_intent_id' => $paymentIntent->id,
            'email' => $customerEmail,
            'reason' => $failureReason,
        ]);

        return response('Pago fallido registrado.', 200);
    }

    protected function handleChargeRefunded(object $charge): Response
    {
        $paymentIntentId = $charge->payment_intent ?? null;

        Log::info('Stripe Webhook: Reembolso detectado', [
            'charge_id' => $charge->id,
            'payment_intent' => $paymentIntentId,
        ]);

        if (!$paymentIntentId) {
            return response('No hay Payment Intent asociado al reembolso.', 200);
        }

        try {
            $this->orderService->processRefund($paymentIntentId);
            return response('Reembolso procesado.', 200);
        } catch (Exception $e) {
            Log::error('Fallo crítico al procesar reembolso en Webhook Stripe', [
                'charge_id' => $charge->id,
                'payment_intent' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            return response('Error interno al procesar el reembolso.', 500);
        }
    }

    /**
     * Sesión de checkout expirada sin completar pago.
     */
    protected function handleCheckoutSessionExpired(object $session): Response
    {
        Log::info('Stripe Webhook: Sesión de checkout abandonada', [
            'session_id' => $session->id,
            'customer_email' => $session->customer_details->email ?? null,
        ]);

        return response('Sesión expirada registrada.', 200);
    }
}