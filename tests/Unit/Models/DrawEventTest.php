<?php

namespace Tests\Unit\Models;

use App\Models\DrawEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DrawEventTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_generates_hash_correctly()
    {
        $data = [
            'event_type' => 'test.event',
            'competition_id' => 123,
            'event_payload' => ['key' => 'value'],
            'created_at' => '2025-11-07T12:00:00+00:00',
        ];

        $hash = DrawEvent::generateHash($data);

        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash)); // SHA256 is 64 chars
    }

    /** @test */
    public function it_generates_different_hashes_for_different_data()
    {
        $data1 = [
            'event_type' => 'test.event',
            'competition_id' => 123,
            'event_payload' => ['key' => 'value1'],
            'created_at' => '2025-11-07T12:00:00+00:00',
        ];

        $data2 = [
            'event_type' => 'test.event',
            'competition_id' => 123,
            'event_payload' => ['key' => 'value2'],
            'created_at' => '2025-11-07T12:00:00+00:00',
        ];

        $hash1 = DrawEvent::generateHash($data1);
        $hash2 = DrawEvent::generateHash($data2);

        $this->assertNotEquals($hash1, $hash2);
    }

    /** @test */
    public function it_includes_previous_hash_in_chain()
    {
        $data = [
            'event_type' => 'test.event',
            'competition_id' => 123,
            'event_payload' => ['key' => 'value'],
            'created_at' => '2025-11-07T12:00:00+00:00',
        ];

        $previousHash = 'abc123';
        $hashWithChain = DrawEvent::generateHash($data, $previousHash);
        $hashWithoutChain = DrawEvent::generateHash($data);

        $this->assertNotEquals($hashWithChain, $hashWithoutChain);
    }

    /** @test */
    public function it_can_log_an_event()
    {
        config(['raffaly.draw_events.enabled' => true]);

        $event = DrawEvent::logEvent(
            'test.event',
            ['test' => 'data'],
            null,
            ['actor_type' => 'system']
        );

        $this->assertInstanceOf(DrawEvent::class, $event);
        $this->assertDatabaseHas('draw_events', [
            'event_type' => 'test.event',
            'actor_type' => 'system',
        ]);
    }

    /** @test */
    public function it_creates_hash_chain_when_logging_multiple_events()
    {
        config(['raffaly.draw_events.enabled' => true, 'raffaly.draw_events.chain_hashing' => true]);

        $event1 = DrawEvent::logEvent('event.one', ['data' => '1']);
        $event2 = DrawEvent::logEvent('event.two', ['data' => '2']);

        $this->assertNull($event1->previous_event_hash);
        $this->assertEquals($event1->event_hash, $event2->previous_event_hash);
    }

    /** @test */
    public function it_verifies_chain_integrity_successfully()
    {
        config(['raffaly.draw_events.enabled' => true, 'raffaly.draw_events.chain_hashing' => true]);

        DrawEvent::logEvent('event.one', ['data' => '1']);
        DrawEvent::logEvent('event.two', ['data' => '2']);
        DrawEvent::logEvent('event.three', ['data' => '3']);

        $results = DrawEvent::verifyChainIntegrity();

        $this->assertTrue($results['is_valid']);
        $this->assertEquals(3, $results['total_events']);
        $this->assertEquals(3, $results['verified_events']);
        $this->assertEquals(0, $results['failed_events']);
    }

    /** @test */
    public function it_scopes_events_by_competition()
    {
        config(['raffaly.draw_events.enabled' => true]);

        DrawEvent::logEvent('event.one', ['data' => '1'], 100);
        DrawEvent::logEvent('event.two', ['data' => '2'], 100);
        DrawEvent::logEvent('event.three', ['data' => '3'], 200);

        $competition100Events = DrawEvent::forCompetition(100)->get();
        $competition200Events = DrawEvent::forCompetition(200)->get();

        $this->assertCount(2, $competition100Events);
        $this->assertCount(1, $competition200Events);
    }

    /** @test */
    public function it_scopes_events_by_type()
    {
        config(['raffaly.draw_events.enabled' => true]);

        DrawEvent::logEvent('raffle.created', ['data' => '1']);
        DrawEvent::logEvent('raffle.updated', ['data' => '2']);
        DrawEvent::logEvent('draw.started', ['data' => '3']);

        $raffleEvents = DrawEvent::byType('raffle.created')->get();
        $drawEvents = DrawEvent::byType('draw.started')->get();

        $this->assertCount(1, $raffleEvents);
        $this->assertCount(1, $drawEvents);
    }
}
