<?php
namespace App\DTOs\Payment;

use Stripe\Checkout\Session;

class ProcessWebhookOrderDTO
{
    public function __construct(
        public string $stripeSessionId,
        public string $customerEmail,
        public string $customerName,
        public int $templateId,
        public float $amountTotal,
        public string $currency
    ) {}

    public static function fromStripeSession(Session $session): self
    {
        return new self(
            stripeSessionId: $session->id,
            customerEmail: $session->customer_details->email ?? $session->customer_email,
            customerName: $session->metadata->customer_name ?? $session->customer_details->name ?? 'Cliente',
            templateId: (int) $session->metadata->template_id,
            amountTotal: $session->amount_total / 100,
            currency: strtolower($session->currency),
        );
    }
}