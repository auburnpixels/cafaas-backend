<?php

namespace Tests\Unit;

use App\Http\Services\TicketCleanupService;
use App\Models\Checkout;
use App\Models\Competition;
use App\Models\Prize;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var TicketCleanupService
     */
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TicketCleanupService;
    }

    /** @test */
    public function it_can_get_ticket_statistics_for_a_competition()
    {
        // Create a competition
        $competition = Competition::factory()->create([
            'status' => Competition::STATUS_ENDED,
        ]);

        // Create tickets with different states
        $user = User::factory()->create();

        // 3 purchased tickets (completed checkout)
        $completedCheckout = Checkout::factory()->create([
            'completed' => now(),
            'user_id' => $user->id,
        ]);

        Ticket::factory()->count(3)->create([
            'competition_id' => $competition->id,
            'checkout_id' => $completedCheckout->id,
            'user_id' => $user->id,
        ]);

        // 2 abandoned tickets (incomplete checkout)
        $abandonedCheckout = Checkout::factory()->create([
            'completed' => null,
            'user_id' => $user->id,
        ]);

        Ticket::factory()->count(2)->create([
            'competition_id' => $competition->id,
            'checkout_id' => $abandonedCheckout->id,
            'user_id' => $user->id,
        ]);

        // 5 never assigned tickets
        Ticket::factory()->count(5)->create([
            'competition_id' => $competition->id,
            'checkout_id' => null,
            'user_id' => null,
        ]);

        $stats = $this->service->getTicketStatistics($competition);

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(3, $stats['purchased']);
        $this->assertEquals(0, $stats['winning']); // No winning tickets yet
        $this->assertEquals(7, $stats['unused']); // 2 abandoned + 5 never assigned
    }

    /** @test */
    public function it_can_cleanup_unused_tickets_from_completed_competition()
    {
        // Create a completed competition
        $competition = Competition::factory()->create([
            'status' => Competition::STATUS_ENDED,
        ]);

        $user = User::factory()->create();

        // Create purchased tickets (should be kept)
        $completedCheckout = Checkout::factory()->create([
            'completed' => now(),
            'user_id' => $user->id,
        ]);

        Ticket::factory()->count(3)->create([
            'competition_id' => $competition->id,
            'checkout_id' => $completedCheckout->id,
            'user_id' => $user->id,
        ]);

        // Create unused tickets (should be deleted)
        Ticket::factory()->count(5)->create([
            'competition_id' => $competition->id,
            'checkout_id' => null,
            'user_id' => null,
        ]);

        $result = $this->service->cleanupCompetitionTickets($competition);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $result['deleted']);
        $this->assertEquals(3, $result['kept']);

        // Verify only purchased tickets remain
        $this->assertEquals(3, Ticket::where('competition_id', $competition->id)->count());
    }

    /** @test */
    public function it_does_not_cleanup_tickets_from_active_competitions()
    {
        // Create an active competition
        $competition = Competition::factory()->create([
            'status' => Competition::STATUS_ACTIVE,
        ]);

        // Create unused tickets
        Ticket::factory()->count(5)->create([
            'competition_id' => $competition->id,
            'checkout_id' => null,
            'user_id' => null,
        ]);

        $result = $this->service->cleanupCompetitionTickets($competition);

        $this->assertFalse($result['success']);
        $this->assertEquals('Competition is not completed yet', $result['message']);
        $this->assertEquals(0, $result['deleted']);

        // Verify all tickets still exist
        $this->assertEquals(5, Ticket::where('competition_id', $competition->id)->count());
    }

    /** @test */
    public function it_preserves_winning_tickets_during_cleanup()
    {
        // Create a completed competition
        $competition = Competition::factory()->create([
            'status' => Competition::STATUS_ENDED,
        ]);

        $user = User::factory()->create();

        // Create purchased tickets (should be kept)
        $completedCheckout = Checkout::factory()->create([
            'completed' => now(),
            'user_id' => $user->id,
        ]);

        Ticket::factory()->count(2)->create([
            'competition_id' => $competition->id,
            'checkout_id' => $completedCheckout->id,
            'user_id' => $user->id,
        ]);

        // Create a winning ticket (should be preserved even if unused)
        $winningTicket = Ticket::factory()->create([
            'competition_id' => $competition->id,
            'checkout_id' => null, // Never purchased
            'user_id' => null,
        ]);

        // Create a prize that references the winning ticket
        $prize = Prize::factory()->create([
            'competition_id' => $competition->id,
            'winning_ticket_id' => $winningTicket->id,
        ]);

        // Create unused tickets (should be deleted)
        Ticket::factory()->count(3)->create([
            'competition_id' => $competition->id,
            'checkout_id' => null,
            'user_id' => null,
        ]);

        $result = $this->service->cleanupCompetitionTickets($competition);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['deleted']); // Only unused tickets deleted
        $this->assertEquals(2, $result['kept']); // Purchased tickets kept

        // Verify winning ticket is preserved
        $this->assertDatabaseHas('tickets', ['id' => $winningTicket->id]);

        // Verify prize still references the winning ticket
        $prize->refresh();
        $this->assertEquals($winningTicket->id, $prize->winning_ticket_id);

        // Verify only purchased and winning tickets remain
        $remainingTickets = Ticket::where('competition_id', $competition->id)->get();
        $this->assertEquals(3, $remainingTickets->count()); // 2 purchased + 1 winning
    }

    /** @test */
    public function it_handles_guest_checkout_tickets_correctly()
    {
        // Create a completed competition
        $competition = Competition::factory()->create([
            'status' => Competition::STATUS_ENDED,
        ]);

        // Create guest checkout (no user_id)
        $guestCheckout = Checkout::factory()->create([
            'completed' => now(),
            'user_id' => null,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Create tickets for guest checkout (should be kept)
        Ticket::factory()->count(2)->create([
            'competition_id' => $competition->id,
            'checkout_id' => $guestCheckout->id,
            'user_id' => null, // Guest checkout
            'name' => 'John Doe',
        ]);

        // Create unused tickets (should be deleted)
        Ticket::factory()->count(3)->create([
            'competition_id' => $competition->id,
            'checkout_id' => null,
            'user_id' => null,
        ]);

        $result = $this->service->cleanupCompetitionTickets($competition);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['deleted']);
        $this->assertEquals(2, $result['kept']);

        // Verify guest checkout tickets are preserved
        $remainingTickets = Ticket::where('competition_id', $competition->id)->get();
        $this->assertEquals(2, $remainingTickets->count());
        $this->assertTrue($remainingTickets->every(fn ($ticket) => $ticket->checkout_id === $guestCheckout->id));
    }
}
