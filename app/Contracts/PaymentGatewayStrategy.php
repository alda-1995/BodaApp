<?php
namespace App\Contracts;

use App\DTOs\Payment\PaymentResponseDTO;

interface PaymentGatewayStrategy
{
    public function createCheckoutSession(PaymentInputInterface $dto): PaymentResponseDTO;
}