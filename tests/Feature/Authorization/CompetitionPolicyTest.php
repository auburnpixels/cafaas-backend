<?php

namespace Tests\Feature\Authorization;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_their_competition(): void
    {
        $owner = User::factory()->create();
        $competition = Competition::factory()->for($owner)->create([
            'status' => Competition::STATUS_UNPUBLISHED,
        ]);

        $response = $this->actingAs($owner)
            ->patch(route('account.competitions.update', $competition), [
                'title' => 'Updated Title',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('competitions', [
            'id' => $competition->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_non_owner_cannot_update_competition(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $competition = Competition::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)
            ->patch(route('account.competitions.update', $competition), [
                'title' => 'Hacked Title',
            ]);

        $response->assertForbidden();
    }

    public function test_owner_can_delete_pending_competition(): void
    {
        $owner = User::factory()->create();
        $competition = Competition::factory()->for($owner)->create([
            'status' => Competition::STATUS_UNPUBLISHED,
        ]);

        $response = $this->actingAs($owner)
            ->delete(route('account.competitions.destroy', $competition));

        $response->assertRedirect();
        $this->assertSoftDeleted('competitions', ['id' => $competition->id]);
    }

    public function test_owner_cannot_delete_active_competition(): void
    {
        $owner = User::factory()->create();
        $competition = Competition::factory()->for($owner)->create([
            'status' => Competition::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($owner)
            ->delete(route('account.competitions.destroy', $competition));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_competition_edit(): void
    {
        $competition = Competition::factory()->create();

        $response = $this->get(route('account.competitions.edit', $competition));

        $response->assertRedirect(route('login'));
    }
}
