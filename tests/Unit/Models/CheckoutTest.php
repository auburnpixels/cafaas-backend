<?php

namespace Tests\Unit\Models;

use App\Models\Checkout;
use App\Models\Competition;
use App\Models\Discount;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_belongs_to_competition(): void
    {
        $competition = Competition::factory()->create();
        $checkout = Checkout::factory()->for($competition)->create();

        $this->assertInstanceOf(Competition::class, $checkout->competition);
        $this->assertEquals($competition->id, $checkout->competition->id);
    }

    public function test_checkout_can_belong_to_user(): void
    {
        $user = User::factory()->create();
        $checkout = Checkout::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $checkout->user);
        $this->assertEquals($user->id, $checkout->user->id);
    }

    public function test_checkout_can_have_discount(): void
    {
        $discount = Discount::factory()->create();
        $checkout = Checkout::factory()->for($discount)->create();

        $this->assertInstanceOf(Discount::class, $checkout->discount);
        $this->assertEquals($discount->id, $checkout->discount->id);
    }

    public function test_checkout_has_many_tickets(): void
    {
        $checkout = Checkout::factory()->hasTickets(5)->create();

        $this->assertCount(5, $checkout->tickets);
        $this->assertInstanceOf(Ticket::class, $checkout->tickets->first());
    }

    public function test_checkout_knows_if_it_is_expired(): void
    {
        $expired = Checkout::factory()->create([
            'expiry_at' => Carbon::now()->subMinutes(5),
        ]);

        $valid = Checkout::factory()->create([
            'expiry_at' => Carbon::now()->addMinutes(10),
        ]);

        $this->assertTrue($expired->expiry_at->isPast());
        $this->assertFalse($valid->expiry_at->isPast());
    }

    public function test_checkout_has_uuid(): void
    {
        $checkout = Checkout::factory()->create();

        $this->assertNotNull($checkout->uuid);
        $this->assertIsString($checkout->uuid);
    }

    public function test_checkout_can_store_axcess_payment_data(): void
    {
        $paymentData = ['transaction_id' => 'ABC123', 'status' => 'success'];

        $checkout = Checkout::factory()->create(['axcess' => $paymentData]);

        $this->assertEquals($paymentData, $checkout->axcess);
    }

    public function test_checkout_expiry_is_datetime(): void
    {
        $checkout = Checkout::factory()->create();

        $this->assertInstanceOf(Carbon::class, $checkout->expiry_at);
    }
}
