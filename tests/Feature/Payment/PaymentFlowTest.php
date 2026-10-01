<?php

namespace Tests\Feature\Payment;

use App\Contracts\PaymentGatewayStrategy;
use App\DTOs\Payment\PaymentResponseDTO;
use App\Models\Template;
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class PaymentFlowTest extends TestCase
{
    // use RefreshDatabase;

    /** @test */
    public function create_order_payment_conect()
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['price' => 1000.00, 'stripe_price_id' => 'price_123']);

        // Mocking de la estrategia de Stripe
        $stripeMock = Mockery::mock(PaymentGatewayStrategy::class);
        $stripeMock->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn(new PaymentResponseDTO('sess_stripe_999', 'https://stripe.com/pay', 1000.00, 'MXN'));

        // Mocking de la Factory
        $factoryMock = Mockery::mock(PaymentGatewayFactory::class);
        $factoryMock->shouldReceive('make')
            ->with('stripe')
            ->once()
            ->andReturn($stripeMock);

        $this->app->instance(PaymentGatewayFactory::class, $factoryMock);

        $response = $this->actingAs($user)->json('POST', "/api/checkout/{$template->id}", [
            'provider' => 'stripe'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['redirect_url' => 'https://stripe.com/pay']);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'template_id' => $template->id,
            'stripe_session_id' => 'sess_stripe_999',
            'status' => 'pending'
        ]);
    }
}
