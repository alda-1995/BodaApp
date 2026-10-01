<?php
namespace App\DTOs\Payment;

use App\Contracts\PaymentInputInterface;

class StripeCheckoutDTO implements PaymentInputInterface
{
    public function __construct(
        public readonly int $userId,
        public readonly string $customerEmail,
        public readonly int $templateId,
        public readonly string $stripePriceId,
        public readonly float $amount
    ) {}
}