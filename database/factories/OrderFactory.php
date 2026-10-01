<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id'                  => User::factory(),
            'template_id'              => Template::factory(),
            'stripe_session_id'        => 'cs_test_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'stripe_payment_intent_id' => 'pi_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'amount'                   => $this->faker->randomFloat(2, 299, 999),
            'currency'                 => 'mxn',
            'status'                   => 'pending', 
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'stripe_payment_intent_id' => null,
        ]);
    }
}