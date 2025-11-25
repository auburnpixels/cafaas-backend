<?php

namespace Tests\Unit\Models;

use App\Models\Competition;
use App\Models\Prize;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_competition_knows_if_it_is_active(): void
    {
        $active = Competition::factory()->create(['status' => Competition::STATUS_ACTIVE]);
        $unpublished = Competition::factory()->create(['status' => Competition::STATUS_UNPUBLISHED]);

        $this->assertTrue($active->active);
        $this->assertFalse($unpublished->active);
    }

    public function test_competition_calculates_tickets_remaining_correctly(): void
    {
        $competition = Competition::factory()->create([
            'ticket_amount' => 100,
            'tickets_bought' => 35,
        ]);

        $this->assertEquals(65, $competition->ticketsRemaining);
    }

    public function test_competition_knows_if_it_is_free(): void
    {
        $free = Competition::factory()->create(['ticket_price' => 0]);
        $paid = Competition::factory()->create(['ticket_price' => 10.00]);

        $this->assertTrue($free->isFree);
        $this->assertFalse($paid->isFree);
    }

    public function test_competition_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $competition->user);
        $this->assertEquals($user->id, $competition->user->id);
    }

    public function test_competition_has_many_tickets(): void
    {
        $competition = Competition::factory()->hasTickets(50)->create();

        $this->assertCount(50, $competition->tickets);
        $this->assertInstanceOf(Ticket::class, $competition->tickets->first());
    }

    public function test_competition_has_many_prizes(): void
    {
        $competition = Competition::factory()->hasPrizes(3)->create();

        $this->assertCount(3, $competition->prizes);
        $this->assertInstanceOf(Prize::class, $competition->prizes->first());
    }

    public function test_drops_scope_filters_drop_competitions(): void
    {
        Competition::factory()->create(['is_drop' => true]);
        Competition::factory()->create(['is_drop' => true]);
        Competition::factory()->create(['is_drop' => false]);

        $drops = Competition::drops()->get();

        $this->assertCount(2, $drops);
        $this->assertTrue($drops->every->is_drop);
    }

    public function test_active_scope_filters_active_competitions(): void
    {
        Competition::factory()->create(['status' => Competition::STATUS_ACTIVE]);
        Competition::factory()->create(['status' => Competition::STATUS_AWAITING_DRAW]);

        $active = Competition::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals(Competition::STATUS_ACTIVE, $active->first()->status);
    }

    public function test_competition_knows_if_it_is_access_raffle(): void
    {
        $access = Competition::factory()->create(['type' => Competition::TYPE_ACCESS]);
        $traditional = Competition::factory()->create(['type' => Competition::TYPE_TRADITIONAL]);

        $this->assertTrue($access->isAccessRaffle);
        $this->assertFalse($traditional->isAccessRaffle);
    }

    public function test_competition_slug_generated_from_title(): void
    {
        $competition = Competition::factory()->create([
            'title' => 'Win a Tesla Model 3',
        ]);

        $this->assertEquals('win-a-tesla-model-3', $competition->slug);
    }

    public function test_competition_has_winning_ticket_relationship(): void
    {
        $competition = Competition::factory()->hasTickets(10)->create();
        $winningTicket = $competition->tickets->first();

        $competition->update(['winning_ticket_id' => $winningTicket->id]);
        $competition = $competition->fresh();

        $this->assertInstanceOf(Ticket::class, $competition->winningTicket);
        $this->assertEquals($winningTicket->id, $competition->winningTicket->id);
    }
}
