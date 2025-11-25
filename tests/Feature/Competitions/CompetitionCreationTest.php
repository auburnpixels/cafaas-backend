<?php

namespace Tests\Feature\Competitions;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_access_competition_creation_page(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_number_verified' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('account.competitions.create'));

        $response->assertOk();
        $response->assertViewIs('accounts.competitions.create');
    }

    public function test_unverified_email_user_cannot_create_competition(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'phone_number_verified' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('account.competitions.create'));

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_phone_user_cannot_create_competition(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_number_verified' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('account.competitions.create'));

        $response->assertRedirect();
    }

    public function test_guest_cannot_access_competition_creation(): void
    {
        $response = $this->get(route('account.competitions.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_create_traditional_competition(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_number_verified' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('account.competitions.store'), [
                'type' => Competition::TYPE_TRADITIONAL,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('competitions', [
            'user_id' => $user->id,
            'type' => Competition::TYPE_TRADITIONAL,
            'status' => Competition::STATUS_UNPUBLISHED,
        ]);
    }

    public function test_user_can_create_access_competition(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'phone_number_verified' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('account.competitions.store'), [
                'type' => Competition::TYPE_ACCESS,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('competitions', [
            'user_id' => $user->id,
            'type' => Competition::TYPE_ACCESS,
        ]);
    }

    public function test_user_can_view_their_own_competitions(): void
    {
        $user = User::factory()->hasCompetitions(3)->create();

        $response = $this->actingAs($user)
            ->get(route('account.competitions.active'));

        $response->assertOk();
        $response->assertViewHas('competitions');
    }

    public function test_user_cannot_edit_another_users_competition(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $competition = Competition::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)
            ->get(route('account.competitions.edit', $competition));

        $response->assertForbidden();
    }

    public function test_user_can_delete_their_own_unpublished_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()
            ->for($user)
            ->create(['status' => Competition::STATUS_UNPUBLISHED]);

        $response = $this->actingAs($user)
            ->delete(route('account.competitions.destroy', $competition));

        $response->assertRedirect();
        $this->assertSoftDeleted('competitions', ['id' => $competition->id]);
    }
}
