<?php

namespace App\Services\Payment\Providers;

use App\Contracts\PaymentGatewayStrategy;
use App\Contracts\PaymentInputInterface;
use App\DTOs\Payment\PaymentResponseDTO;
use App\DTOs\StripeCheckoutDTO;
use InvalidArgumentException;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class StripePaymentStrategy implements PaymentGatewayStrategy
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(PaymentInputInterface $dto): PaymentResponseDTO
    {
        if (!$dto instanceof StripeCheckoutDTO) {
            throw new InvalidArgumentException("StripePaymentStrategy requiere una instancia de StripeCheckoutDTO.");
        }

        $session = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $dto->stripePriceId,
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel'),
            'customer_email' => $dto->customerEmail,
            'metadata' => [
                'user_id' => $dto->userId,
                'template_id' => $dto->templateId,
            ]
        ]);

        return new PaymentResponseDTO(
            transactionReferenceId: $session->id,
            redirectUrl: $session->url,
            amount: $dto->amount,
            currency: 'MXN'
        );
    }
}