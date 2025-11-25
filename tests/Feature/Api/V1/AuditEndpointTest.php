<?php

namespace Tests\Feature\Api\V1;

use App\Models\Competition;
use App\Models\CompetitionDrawAudit;
use App\Models\Prize;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditEndpointTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_audit_record_for_valid_raffle()
    {
        // Arrange
        $competition = Competition::factory()->create(['uuid' => Str::uuid()]);
        $prize = Prize::factory()->create(['competition_id' => $competition->id]);
        $ticket = Ticket::factory()->create(['competition_id' => $competition->id]);

        $audit = CompetitionDrawAudit::create([
            'competition_id' => $competition->id,
            'prize_id' => $prize->id,
            'draw_id' => Str::uuid(),
            'drawn_at_utc' => now()->toDateTimeString(),
            'total_entries' => 100,
            'rng_seed_or_hash' => 'test_hash_123',
            'selected_entry_id' => $ticket->id,
            'signature_hash' => 'test_signature_456',
        ]);

        // Act
        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/audit");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'raffle_id',
                'draw_id',
                'drawn_at_utc',
                'total_entries',
                'rng_seed_hash',
                'winner_entry_id',
                'signature_hash',
                'prize_name',
            ])
            ->assertJson([
                'raffle_id' => $competition->uuid,
                'draw_id' => $audit->draw_id,
                'total_entries' => 100,
                'rng_seed_hash' => 'test_hash_123',
                'signature_hash' => 'test_signature_456',
            ]);

        $response->assertHeader('X-API-Version', 'v1');
        $response->assertHeader('X-Raffaly-Audit-Signature');
        $this->assertTrue($response->headers->has('ETag'));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_raffle()
    {
        $response = $this->getJson('/api/v1/raffles/'.Str::uuid().'/audit');

        $response->assertStatus(404)
            ->assertJson([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Resource not found',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_when_no_audit_exists()
    {
        $competition = Competition::factory()->create(['uuid' => Str::uuid()]);

        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/audit");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_includes_api_headers_in_response()
    {
        $competition = Competition::factory()->create(['uuid' => Str::uuid()]);
        $prize = Prize::factory()->create(['competition_id' => $competition->id]);
        $ticket = Ticket::factory()->create(['competition_id' => $competition->id]);

        CompetitionDrawAudit::create([
            'competition_id' => $competition->id,
            'prize_id' => $prize->id,
            'draw_id' => Str::uuid(),
            'drawn_at_utc' => now()->toDateTimeString(),
            'total_entries' => 50,
            'rng_seed_or_hash' => 'hash',
            'selected_entry_id' => $ticket->id,
            'signature_hash' => 'sig',
        ]);

        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/audit", [
            'X-Request-Id' => 'test-request-123',
        ]);

        $response->assertHeader('X-Request-Id', 'test-request-123');
        $response->assertHeader('X-API-Version', 'v1');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }
}
