<?php

namespace Tests\Feature\Checkout;

use App\Events\CompetitionTicketsBought;
use App\Models\Checkout;
use App\Models\Competition;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TicketPurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_initiate_ticket_purchase(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_number_verified' => true,
        ]);

        $competition = Competition::factory()
            ->active()
            ->hasTickets(100)
            ->create(['ticket_price' => 10.00]);

        $response = $this->actingAs($user)
            ->post(route('entries.store', $competition), [
                'tickets' => 5,
            ]);

        $response->assertRedirect();

        // Check checkout created
        $this->assertDatabaseHas('checkouts', [
            'competition_id' => $competition->id,
        ]);

        // Check tickets temporarily assigned
        $this->assertEquals(5, Ticket::where('competition_id', $competition->id)
            ->whereNotNull('checkout_id')
            ->count());
    }

    public function test_cannot_purchase_tickets_for_inactive_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()
            ->hasTickets(100)
            ->create(['status' => Competition::STATUS_UNPUBLISHED]);

        $response = $this->actingAs($user)
            ->post(route('entries.store', $competition), [
                'tickets' => 5,
            ]);

        $response->assertRedirect('/');
    }

    public function test_checkout_has_expiry_time(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_number_verified' => true,
        ]);

        $competition = Competition::factory()->active()->hasTickets(100)->create();

        $this->actingAs($user)
            ->post(route('entries.store', $competition), [
                'tickets' => 5,
            ]);

        $checkout = Checkout::where('competition_id', $competition->id)->first();

        $this->assertNotNull($checkout->expiry_at);
        $this->assertTrue($checkout->expiry_at->isFuture());
    }

    public function test_cannot_purchase_more_tickets_than_available(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_number_verified' => true,
        ]);

        $competition = Competition::factory()
            ->active()
            ->hasTickets(5)
            ->create();

        $response = $this->actingAs($user)
            ->post(route('entries.store', $competition), [
                'tickets' => 10, // Requesting more than available
            ]);

        // Should redirect back or show error
        $this->assertDatabaseMissing('checkouts', [
            'competition_id' => $competition->id,
        ]);
    }

    public function test_guest_can_purchase_tickets_with_email(): void
    {
        $competition = Competition::factory()->active()->hasTickets(100)->create();

        $response = $this->post(route('entries.store', $competition), [
            'tickets' => 3,
            'email' => 'guest@example.com',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('checkouts', [
            'competition_id' => $competition->id,
            'email' => 'guest@example.com',
            'user_id' => null,
        ]);
    }

    public function test_free_competition_assigns_tickets_immediately(): void
    {
        Event::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_number_verified' => true,
        ]);

        $competition = Competition::factory()
            ->active()
            ->hasTickets(100)
            ->create(['ticket_price' => 0]); // Free

        $this->actingAs($user)
            ->post(route('entries.store', $competition), [
                'tickets' => 5,
            ]);

        // Tickets should be assigned immediately (no checkout needed)
        $this->assertEquals(5, Ticket::where('competition_id', $competition->id)
            ->where('user_id', $user->id)
            ->count());

        Event::assertDispatched(CompetitionTicketsBought::class);
    }
}
