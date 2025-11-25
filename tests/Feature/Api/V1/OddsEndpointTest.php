<?php

namespace Tests\Feature\Api\V1;

use App\Models\Competition;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OddsEndpointTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_calculates_odds_correctly()
    {
        // Arrange
        $competition = Competition::factory()->create(['uuid' => Str::uuid()]);
        Ticket::factory()->count(100)->create(['competition_id' => $competition->id]);

        // Act
        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/odds?entries=10");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'raffle_id',
                'entrant_entries',
                'total_entries',
                'ratio',
                'probability',
                'as_of',
            ])
            ->assertJson([
                'raffle_id' => $competition->uuid,
                'entrant_entries' => 10,
                'total_entries' => 100,
                'ratio' => '10 in 100',
                'probability' => 0.1,
            ]);
    }

    /** @test */
    public function it_requires_entries_parameter()
    {
        $competition = Competition::factory()->create(['uuid' => Str::uuid()]);

        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/odds");

        // Laravel will return 422 for missing required parameter
        $this->assertTrue(in_array($response->status(), [400, 422]));
    }

    /** @test */
    public function it_rejects_negative_entries()
    {
        $competition = Competition::factory()->create(['uuid' => Str::uuid()]);
        Ticket::factory()->count(100)->create(['competition_id' => $competition->id]);

        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/odds?entries=-5");

        $response->assertStatus(400)
            ->assertJson([
                'error' => [
                    'code' => 'INVALID_PARAMETER',
                ],
            ]);
    }

    /** @test */
    public function it_handles_raffle_with_no_entries()
    {
        $competition = Competition::factory()->create(['uuid' => Str::uuid()]);

        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/odds?entries=5");

        $response->assertStatus(400);
    }

    /** @test */
    public function it_caps_entries_at_total_entries()
    {
        $competition = Competition::factory()->create(['uuid' => Str::uuid()]);
        Ticket::factory()->count(50)->create(['competition_id' => $competition->id]);

        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/odds?entries=100");

        $response->assertStatus(200)
            ->assertJson([
                'entrant_entries' => 50,
                'total_entries' => 50,
                'probability' => 1.0,
            ]);
    }
}
