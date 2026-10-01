<?php
namespace App\DTOs\Order;

class CreateCheckoutSessionDTO
{
    public function __construct(
        public int $templateId,
        public string $customerEmail
    ) {}
}