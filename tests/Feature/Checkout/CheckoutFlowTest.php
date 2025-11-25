<?php

namespace Tests\Feature\Checkout;

use App\Models\Checkout;
use App\Models\Competition;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_checkout_page(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->active()->hasTickets(100)->create();

        $checkout = Checkout::factory()
            ->for($user)
            ->for($competition)
            ->create(['expiry_at' => Carbon::now()->addMinutes(15)]);

        $response = $this->actingAs($user)
            ->get(route('checkout.index', ['uuid' => $checkout->uuid]));

        $response->assertOk();
        $response->assertViewIs('checkout.index');
    }

    public function test_expired_checkout_redirects_with_error(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->active()->create();

        $checkout = Checkout::factory()
            ->for($user)
            ->for($competition)
            ->create(['expiry_at' => Carbon::now()->subMinutes(5)]); // Expired

        $response = $this->actingAs($user)
            ->get(route('checkout.index', ['uuid' => $checkout->uuid]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_checkout_process_page_loads_payment_iframe(): void
    {
        $checkout = Checkout::factory()
            ->for(Competition::factory()->active())
            ->hasTickets(5)
            ->create();

        $response = $this->get(route('checkout.process', ['uuid' => $checkout->uuid]));

        $response->assertOk();
        $response->assertViewIs('checkout.process');
        $response->assertViewHas('checkout');
        $response->assertViewHas('copyAndPayId');
    }

    public function test_cannot_access_non_existent_checkout(): void
    {
        $response = $this->get(route('checkout.index', ['uuid' => 'invalid-uuid']));

        $response->assertRedirect('/');
    }

    public function test_checkout_shows_correct_total_with_discount(): void
    {
        $competition = Competition::factory()->active()->create([
            'ticket_price' => 10.00,
        ]);

        $discount = \App\Models\Discount::factory()->create([
            'competition_id' => $competition->id,
            'type' => \App\Models\Discount::TYPE_CHECKOUT_TOTAL,
            'unit' => \App\Models\Discount::UNIT_AMOUNT_OFF,
            'amount' => 20.00,
        ]);

        $checkout = Checkout::factory()
            ->for($competition)
            ->for($discount)
            ->hasTickets(10)
            ->create();

        $response = $this->get(route('checkout.process', ['uuid' => $checkout->uuid]));

        $response->assertOk();
        $response->assertViewHas('totalsAndCredit', function ($data) {
            // £100 - £20 discount = £80
            return $data['total'] == 80.00;
        });
    }
}
