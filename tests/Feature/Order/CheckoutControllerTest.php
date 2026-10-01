<?php

namespace Tests\Feature\Order;

use App\Models\Event;
use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_orders()
    {
        // $this->withoutExceptionHandling();
        Order::factory()->count(2)->create(['user_id' => $this->user->id]);
        Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.index', ['user_id' => $this->user->id]));

        $response->assertStatus(200);
        $response->assertViewIs('orders.index');
        $response->assertViewHas('orders');
    
        $this->assertCount(2, $response->viewData('orders'));
    }

    /** @test */
    public function can_user_view_detail_order()
    {
        // $this->withoutExceptionHandling();
        
        $this->actingAs($this->user);

        $order = Order::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->get(route('orders.show', ['order_id' => $order->id]));

        $response->assertStatus(200);
        $response->assertViewIs('orders.show');
        $response->assertViewHas('order');
        $this->assertEquals($order->id, $response->viewData('order')->id);
    }

    /** @test */
    public function not_can_view_detail_order_user()
    {
        $otherOrder = Order::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->actingAs($this->user)
            ->get(route('orders.show', $otherOrder->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function not_init_checkout_template_dont_exits_or_inactive()
    {
        $templateInactiva = Template::factory()->create([
            'is_active' => false
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('checkout.store'), [
                'template_id' => $templateInactiva->id
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['template_id']);
    }

    /** @test */
    public function checkout_redirect_url_with_data_correct()
    {
        $this->withoutExceptionHandling();

        $template = Template::factory()->create([
            'price' => 499.00, 
            'stripe_price_id' => 'price_12345abcdef'
        ]);

        $mockOrder = (object) [
            'id' => 1,
            'user_id' => $this->user->id,
            'template_id' => $template->id,
            'status' => 'pending',
            'stripe_session_id' => 'cs_test_url_mock_999'
        ];

        $this->mock(\App\Services\OrderService::class, function ($mock) use ($mockOrder) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'checkout_url' => 'https://checkout.stripe.com/pay/cs_test_url_mock_999',
                    'order' => $mockOrder
                ]);
        });

        $response = $this->actingAs($this->user)
            ->post(route('checkout.store'), [
                'template_id' => $template->id
            ]);

        $response->assertRedirect('https://checkout.stripe.com/pay/cs_test_url_mock_999');
    }

    /** @test */
    public function el_retorno_exitoso_de_stripe_procesa_la_orden_y_muestra_la_vista_success()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
            'stripe_session_id' => 'cs_success_session_777'
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('checkout.success', ['session_id' => 'cs_success_session_777']));

        $response->assertStatus(200);
        $response->assertViewIs('checkout.success');
        $response->assertViewHas('order');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'paid'
        ]);

        $this->assertDatabaseHas('events', [
            'order_id' => $order->id,
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function return_view_cancel()
    {
        $response = $this->actingAs($this->user)
            ->get(route('checkout.cancel'));

        $response->assertStatus(200);
        $response->assertViewIs('checkout.cancel');
    }
}