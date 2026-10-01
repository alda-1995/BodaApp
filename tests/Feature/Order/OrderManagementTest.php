<?php

namespace Tests\Feature\Order;

use App\Models\Event;
use App\Models\Order;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function create_order_with_default_status_pending()
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'template_id' => $template->id,
            'amount' => 450.00,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
            'amount' => 450.00,
            'currency' => 'mxn'
        ]);
    }

    /** @test */
    public function amount_is_decimal_cast()
    {
        $order = Order::factory()->create(['amount' => 550.50]);
        $this->assertSame('550.50', $order->amount);
    }

    /** @test */
    public function order_with_relation_user_and_template()
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'template_id' => $template->id
        ]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertInstanceOf(Template::class, $order->template);
        $this->assertEquals($user->id, $order->user->id);
        $this->assertEquals($template->id, $order->template->id);
    }

    /** @test */
    public function order_with_relation_event()
    {
        $order = Order::factory()->completed()->create();
        
        $event = Event::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Event::class, $order->event);
        $this->assertEquals($event->id, $order->event->id);
    }

    /** @test */
    public function can_update_order_and_completed()
    {
        $order = Order::factory()->completed()->create([
            'stripe_payment_intent_id' => 'pi_test_intent_123'
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
            'stripe_payment_intent_id' => 'pi_test_intent_123'
        ]);
    }
}