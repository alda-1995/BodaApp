<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayStrategy;
use App\Services\Payment\Providers\StripePaymentStrategy;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    protected array $strategies = [
        'stripe' => StripePaymentStrategy::class,
    ];

    public function make(string $provider): PaymentGatewayStrategy
    {
        if (!array_key_exists($provider, $this->strategies)) {
            throw new InvalidArgumentException("El proveedor de pagos [{$provider}] no está soportado.");
        }

        return app($this->strategies[$provider]);
    }
}