<?php

namespace Tests\Feature\AccessLinks;

use App\Models\AccessLink;
use App\Models\Competition;
use App\Models\Prize;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_valid_access_link(): void
    {
        $competition = Competition::factory()->create([
            'type' => Competition::TYPE_ACCESS,
        ]);

        $prize = Prize::factory()->for($competition)->create();

        $accessLink = AccessLink::factory()
            ->for($prize)
            ->for($competition)
            ->create([
                'status' => AccessLink::STATUS_AWAITING_PAYMENT,
                'expiry_at' => Carbon::now()->addHours(24),
            ]);

        $response = $this->get(route('access-link.show', ['uuid' => $accessLink->uuid]));

        $response->assertOk();
        $response->assertViewIs('access-link.show');
        $response->assertViewHas('accessLink');
    }

    public function test_expired_access_link_redirects_to_expired_page(): void
    {
        $accessLink = AccessLink::factory()->create([
            'status' => AccessLink::STATUS_EXPIRED,
            'expiry_at' => Carbon::now()->subHours(1),
        ]);

        $response = $this->get(route('access-link.show', ['uuid' => $accessLink->uuid]));

        $response->assertRedirect(route('access-link.expired', ['uuid' => $accessLink->uuid]));
    }

    public function test_paid_access_link_redirects_to_paid_page(): void
    {
        $accessLink = AccessLink::factory()->create([
            'status' => AccessLink::STATUS_PAID,
        ]);

        $response = $this->get(route('access-link.show', ['uuid' => $accessLink->uuid]));

        $response->assertRedirect(route('access-link.paid', ['uuid' => $accessLink->uuid]));
    }

    public function test_access_link_expired_page_displays_correctly(): void
    {
        $accessLink = AccessLink::factory()->create([
            'status' => AccessLink::STATUS_EXPIRED,
        ]);

        $response = $this->get(route('access-link.expired', ['uuid' => $accessLink->uuid]));

        $response->assertOk();
        $response->assertViewIs('access-link.expired');
    }

    public function test_access_link_success_page_displays_after_payment(): void
    {
        $accessLink = AccessLink::factory()->create([
            'status' => AccessLink::STATUS_PAID,
        ]);

        $response = $this->get(route('access-link.success', ['uuid' => $accessLink->uuid]));

        $response->assertOk();
        $response->assertViewIs('access-link.success');
    }

    public function test_non_existent_access_link_redirects_home(): void
    {
        $response = $this->get(route('access-link.show', ['uuid' => 'invalid-uuid']));

        $response->assertRedirect('/');
    }
}
