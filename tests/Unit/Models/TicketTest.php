<?php

namespace Tests\Unit\Models;

use App\Models\Checkout;
use App\Models\Competition;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_belongs_to_competition(): void
    {
        $competition = Competition::factory()->create();
        $ticket = Ticket::factory()->for($competition)->create();

        $this->assertInstanceOf(Competition::class, $ticket->competition);
        $this->assertEquals($competition->id, $ticket->competition->id);
    }

    public function test_ticket_can_belong_to_user(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $ticket->user);
        $this->assertEquals($user->id, $ticket->user->id);
    }

    public function test_ticket_can_belong_to_checkout(): void
    {
        $checkout = Checkout::factory()->create();
        $ticket = Ticket::factory()->for($checkout)->create();

        $this->assertInstanceOf(Checkout::class, $ticket->checkout);
        $this->assertEquals($checkout->id, $ticket->checkout->id);
    }

    public function test_ticket_knows_if_it_is_available(): void
    {
        $available = Ticket::factory()->create([
            'user_id' => null,
            'checkout_id' => null,
        ]);

        $assigned = Ticket::factory()->create([
            'user_id' => User::factory(),
        ]);

        $this->assertNull($available->user_id);
        $this->assertNotNull($assigned->user_id);
    }

    public function test_ticket_number_is_set(): void
    {
        $ticket = Ticket::factory()->create(['number' => '42']);

        $this->assertEquals('42', $ticket->number);
    }
}
