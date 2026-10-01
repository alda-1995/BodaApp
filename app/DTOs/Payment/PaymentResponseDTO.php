<?php
namespace App\DTOs\Payment;

class PaymentResponseDTO
{
    public function __construct(
        public readonly string $transactionReferenceId,
        public readonly string $redirectUrl,
        public readonly float $amount,
        public readonly string $currency
    ) {}
}