<?php

namespace Tests\Unit\Models;

use App\Models\Checkout;
use App\Models\Competition;
use App\Models\Discount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_belongs_to_competition(): void
    {
        $competition = Competition::factory()->create();
        $discount = Discount::factory()->for($competition)->create();

        $this->assertInstanceOf(Competition::class, $discount->competition);
        $this->assertEquals($competition->id, $discount->competition->id);
    }

    public function test_discount_has_correct_constants(): void
    {
        $this->assertEquals('per_ticket', Discount::TYPE_PER_TICKET);
        $this->assertEquals('batch_ticket', Discount::TYPE_BATCH_TICKET);
        $this->assertEquals('checkout_total', Discount::TYPE_CHECKOUT_TOTAL);
        $this->assertEquals('amount_off', Discount::UNIT_AMOUNT_OFF);
        $this->assertEquals('percent_off', Discount::UNIT_PERCENT_OFF);
    }

    public function test_discount_can_be_soft_deleted(): void
    {
        $discount = Discount::factory()->create();
        $id = $discount->id;

        $discount->delete();

        $this->assertSoftDeleted('discounts', ['id' => $id]);
    }

    public function test_discount_has_many_checkouts(): void
    {
        $discount = Discount::factory()->has(Checkout::factory()->count(3))->create();

        $this->assertCount(3, $discount->checkouts);
        $this->assertInstanceOf(Checkout::class, $discount->checkouts->first());
    }

    public function test_discount_amount_is_cast_to_float(): void
    {
        $discount = Discount::factory()->create(['amount' => '15.50']);

        $this->assertIsFloat($discount->amount);
        $this->assertEquals(15.50, $discount->amount);
    }

    public function test_discount_tickets_is_cast_to_integer(): void
    {
        $discount = Discount::factory()->create(['tickets' => '10']);

        $this->assertIsInt($discount->tickets);
        $this->assertEquals(10, $discount->tickets);
    }
}
