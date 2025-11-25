<?php

namespace Tests\Unit\Models;

use App\Models\Competition;
use App\Models\ShippingAddress;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_competitions(): void
    {
        $user = User::factory()->hasCompetitions(3)->create();

        $this->assertCount(3, $user->competitions);
        $this->assertInstanceOf(Competition::class, $user->competitions->first());
    }

    public function test_user_has_many_tickets(): void
    {
        $user = User::factory()->hasTickets(5)->create();

        $this->assertCount(5, $user->tickets);
        $this->assertInstanceOf(Ticket::class, $user->tickets->first());
    }

    public function test_user_has_one_shipping_address(): void
    {
        $user = User::factory()->has(ShippingAddress::factory())->create();

        $this->assertInstanceOf(ShippingAddress::class, $user->shippingAddress);
    }

    public function test_user_email_is_verified(): void
    {
        $verified = User::factory()->create(['email_verified_at' => now()]);
        $unverified = User::factory()->create(['email_verified_at' => null]);

        $this->assertNotNull($verified->email_verified_at);
        $this->assertNull($unverified->email_verified_at);
    }

    public function test_user_username_is_unique(): void
    {
        $user1 = User::factory()->create(['username' => 'johndoe']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        User::factory()->create(['username' => 'johndoe']);
    }

    public function test_user_has_commission_rate(): void
    {
        $user = User::factory()->create(['commission' => 10]);

        $this->assertEquals(10, $user->commission);
    }

    public function test_user_can_have_phone_number_verified(): void
    {
        $user = User::factory()->create([
            'phone_number' => '+447700900000',
            'phone_number_verified' => true,
        ]);

        $this->assertTrue($user->phone_number_verified);
    }
}
