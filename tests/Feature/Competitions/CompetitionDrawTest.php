<?php

namespace Tests\Feature\Competitions;

use App\Events\CompetitionDrawn;
use App\Models\Competition;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CompetitionDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_competition_draw_command_selects_winner(): void
    {
        Event::fake();

        $competition = Competition::factory()
            ->hasTickets(10)
            ->create([
                'status' => Competition::STATUS_AWAITING_DRAW,
                'draw_at' => Carbon::now()->subMinutes(5),
            ]);

        // Assign all tickets to users
        $users = User::factory()->count(10)->create();
        $competition->tickets->each(function ($ticket, $index) use ($users) {
            $ticket->update(['user_id' => $users[$index]->id]);
        });

        // Run the draw command
        Artisan::call('competition:draw');

        $competition = $competition->fresh();

        $this->assertNotNull($competition->winning_ticket_id);
        $this->assertEquals(Competition::STATUS_COMPLETED, $competition->status);

        Event::assertDispatched(CompetitionDrawn::class);
    }

    public function test_only_draws_competitions_past_draw_time(): void
    {
        // This one should be drawn (past draw time)
        $shouldDraw = Competition::factory()->create([
            'status' => Competition::STATUS_AWAITING_DRAW,
            'draw_at' => Carbon::now()->subMinutes(10),
        ]);

        // This one should NOT be drawn (future draw time)
        $shouldNotDraw = Competition::factory()->create([
            'status' => Competition::STATUS_AWAITING_DRAW,
            'draw_at' => Carbon::now()->addHours(1),
        ]);

        Artisan::call('competition:draw');

        $shouldDraw = $shouldDraw->fresh();
        $shouldNotDraw = $shouldNotDraw->fresh();

        $this->assertEquals(Competition::STATUS_COMPLETED, $shouldDraw->status);
        $this->assertEquals(Competition::STATUS_AWAITING_DRAW, $shouldNotDraw->status);
    }

    public function test_only_draws_competitions_with_awaiting_draw_status(): void
    {
        $active = Competition::factory()->create([
            'status' => Competition::STATUS_ACTIVE,
            'draw_at' => Carbon::now()->subMinutes(10),
        ]);

        Artisan::call('competition:draw');

        $active = $active->fresh();

        // Should not be drawn because status is not AWAITING_DRAW
        $this->assertEquals(Competition::STATUS_ACTIVE, $active->status);
        $this->assertNull($active->winning_ticket_id);
    }

    public function test_winning_ticket_must_belong_to_user(): void
    {
        $competition = Competition::factory()->hasTickets(5)->create([
            'status' => Competition::STATUS_AWAITING_DRAW,
            'draw_at' => Carbon::now()->subMinutes(5),
        ]);

        // Only assign 3 tickets to users, leave 2 unassigned
        $users = User::factory()->count(3)->create();
        $competition->tickets->take(3)->each(function ($ticket, $index) use ($users) {
            $ticket->update(['user_id' => $users[$index]->id]);
        });

        Artisan::call('competition:draw');

        $competition = $competition->fresh();
        $winningTicket = $competition->winningTicket;

        $this->assertNotNull($winningTicket);
        $this->assertNotNull($winningTicket->user_id);
    }

    public function test_drop_competition_draws_daily_at_midnight(): void
    {
        Event::fake();

        $drop = Competition::factory()->hasTickets(10)->create([
            'is_drop' => true,
            'status' => Competition::STATUS_ACTIVE,
            'ending_at' => Carbon::today()->endOfDay(),
        ]);

        // Assign tickets to users
        $users = User::factory()->count(10)->create();
        $drop->tickets->each(function ($ticket, $index) use ($users) {
            $ticket->update(['user_id' => $users[$index]->id]);
        });

        // Run the drop draw command
        Artisan::call('competition:drop-draw');

        $drop = $drop->fresh();

        $this->assertNotNull($drop->winning_ticket_id);
        $this->assertEquals(Competition::STATUS_COMPLETED, $drop->status);
    }
}
