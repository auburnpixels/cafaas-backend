<?php

namespace Tests\Feature\Drops;

use App\Models\Competition;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_todays_drop_page(): void
    {
        $drop = Competition::factory()->create([
            'is_drop' => true,
            'status' => Competition::STATUS_ACTIVE,
            'ending_at' => Carbon::today()->endOfDay(),
        ]);

        $response = $this->get(route('todays-drop.index'));

        $response->assertOk();
        $response->assertViewIs('drops.index');
    }

    public function test_guest_can_enter_drop_with_email(): void
    {
        Mail::fake();

        $drop = Competition::factory()->hasTickets(100)->create([
            'is_drop' => true,
            'status' => Competition::STATUS_ACTIVE,
            'ending_at' => Carbon::today()->endOfDay(),
        ]);

        $response = $this->post(route('todays-drop.enter', ['drop' => $drop->id]), [
            'email' => 'drop@example.com',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'competition_id' => $drop->id,
        ]);
    }

    public function test_can_view_drops_marketing_page(): void
    {
        $response = $this->get(route('drops.marketing'));

        $response->assertOk();
        $response->assertViewIs('drops.marketing');
    }

    public function test_can_view_drops_winners_page(): void
    {
        $response = $this->get(route('drops.winners'));

        $response->assertOk();
        $response->assertViewIs('drops.winners');
    }

    public function test_drops_are_free(): void
    {
        $drop = Competition::factory()->create([
            'is_drop' => true,
            'ticket_price' => 0,
        ]);

        $this->assertTrue($drop->isFree);
        $this->assertTrue($drop->is_drop);
    }
}
