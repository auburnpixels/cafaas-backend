<?php

namespace Tests\Feature\Api\V1;

use App\Models\Checkout;
use App\Models\Competition;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EntriesEndpointTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_entry_statistics_for_valid_raffle()
    {
        // Arrange
        $competition = Competition::factory()->create([
            'uuid' => Str::uuid(),
            'ticket_quantity' => 1000,
        ]);

        $user = User::factory()->create();
        $checkout = Checkout::factory()->create(['completed' => now()]);

        // Create paid tickets (with user_id)
        Ticket::factory()->count(30)->create([
            'competition_id' => $competition->id,
            'user_id' => $user->id,
            'checkout_id' => $checkout->id,
        ]);

        // Create free tickets (without user_id for simplicity)
        Ticket::factory()->count(10)->create([
            'competition_id' => $competition->id,
            'user_id' => null,
        ]);

        // Act
        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/entries/stats");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'raffle_id',
                'total_entries',
                'paid_entries',
                'free_entries',
                'max_entries',
                'updated_at',
            ])
            ->assertJson([
                'raffle_id' => $competition->uuid,
                'total_entries' => 40,
                'paid_entries' => 30,
                'free_entries' => 10,
                'max_entries' => 1000,
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_raffle()
    {
        $response = $this->getJson('/api/v1/raffles/'.Str::uuid().'/entries/stats');

        $response->assertStatus(404)
            ->assertJson([
                'error' => [
                    'code' => 'NOT_FOUND',
                ],
            ]);
    }

    /** @test */
    public function it_caches_entry_statistics()
    {
        $competition = Competition::factory()->create(['uuid' => Str::uuid()]);

        $response = $this->getJson("/api/v1/raffles/{$competition->uuid}/entries/stats");

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('Cache-Control'));
    }
}
