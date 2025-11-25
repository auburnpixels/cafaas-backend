<?php

namespace Tests\Feature;

use App\Http\Services\DrawEventService;
use App\Models\Competition;
use App\Models\Prize;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawEventLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected DrawEventService $drawEventService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->drawEventService = app(DrawEventService::class);
        config(['raffaly.draw_events.enabled' => true]);
    }

    /** @test */
    public function it_logs_raffle_created_event()
    {
        $competition = Competition::factory()->create();

        $event = $this->drawEventService->logRaffleCreated($competition);

        $this->assertDatabaseHas('draw_events', [
            'event_type' => 'raffle.created',
            'competition_id' => $competition->id,
        ]);

        $this->assertEquals('raffle.created', $event->event_type);
    }

    /** @test */
    public function it_logs_raffle_updated_event()
    {
        $competition = Competition::factory()->create();

        $changes = [
            'title' => ['old' => 'Old Title', 'new' => 'New Title'],
            'status' => ['old' => 'unpublished', 'new' => 'active'],
        ];

        $event = $this->drawEventService->logRaffleUpdated($competition, $changes);

        $this->assertDatabaseHas('draw_events', [
            'event_type' => 'raffle.updated',
            'competition_id' => $competition->id,
        ]);

        $payload = $event->event_payload;
        $this->assertEquals(['title', 'status'], $payload['updated_fields']);
    }

    /** @test */
    public function it_logs_entry_created_event()
    {
        $competition = Competition::factory()->create();
        $ticket = Ticket::factory()->create(['competition_id' => $competition->id]);
        $user = User::factory()->create();

        $event = $this->drawEventService->logEntryCreated($ticket, false, $user);

        $this->assertDatabaseHas('draw_events', [
            'event_type' => 'entry.created',
            'competition_id' => $competition->id,
        ]);

        $payload = $event->event_payload;
        $this->assertEquals($ticket->id, $payload['ticket_id']);
        $this->assertEquals($user->id, $payload['user_id']);
        $this->assertFalse($payload['is_free']);
    }

    /** @test */
    public function it_logs_draw_started_event()
    {
        $competition = Competition::factory()->create();

        $event = $this->drawEventService->logDrawStarted($competition, 150);

        $this->assertDatabaseHas('draw_events', [
            'event_type' => 'draw.started',
            'competition_id' => $competition->id,
        ]);

        $payload = $event->event_payload;
        $this->assertEquals(150, $payload['entries_count']);
    }

    /** @test */
    public function it_logs_draw_completed_event()
    {
        $competition = Competition::factory()->create();
        $prize = Prize::factory()->create(['competition_id' => $competition->id]);
        $ticket = Ticket::factory()->create(['competition_id' => $competition->id]);

        $event = $this->drawEventService->logDrawCompleted($prize, $ticket);

        $this->assertDatabaseHas('draw_events', [
            'event_type' => 'draw.completed',
            'competition_id' => $competition->id,
        ]);

        $payload = $event->event_payload;
        $this->assertEquals($prize->id, $payload['prize_id']);
        $this->assertEquals($ticket->id, $payload['winning_ticket_id']);
    }

    /** @test */
    public function it_captures_actor_context()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $competition = Competition::factory()->create();
        $event = $this->drawEventService->logRaffleCreated($competition);

        $this->assertEquals('user', $event->actor_type);
        $this->assertEquals($user->id, $event->actor_id);
    }

    /** @test */
    public function it_stores_system_events_without_actor()
    {
        $event = $this->drawEventService->logSystemCronRan('test_job', 1.5, ['status' => 'success']);

        $this->assertDatabaseHas('draw_events', [
            'event_type' => 'system.cron_ran',
            'actor_type' => 'system',
        ]);

        $this->assertNull($event->competition_id);
    }
}
