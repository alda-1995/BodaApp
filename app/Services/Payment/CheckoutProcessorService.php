<?php

namespace App\Services\Payment;

use App\Contracts\PaymentInputInterface;
use App\DTOs\Payment\PaymentResponseDTO;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CheckoutProcessorService
{
    public function __construct(
        protected PaymentGatewayFactory $gatewayFactory
    ) {}

    public function execute(PaymentInputInterface $dto, string $provider): PaymentResponseDTO
    {
        $gateway = $this->gatewayFactory->make($provider);

        $paymentResponse = $gateway->createCheckoutSession($dto);

        DB::transaction(function () use ($dto, $paymentResponse) {
            Order::create([
                'user_id' => $dto->userId,
                'template_id' => $dto->templateId,
                'stripe_session_id' => $paymentResponse->transactionReferenceId, 
                'amount' => $paymentResponse->amount,
                'currency' => $paymentResponse->currency,
                'status' => 'pending',
            ]);
        });

        return $paymentResponse;
    }
}