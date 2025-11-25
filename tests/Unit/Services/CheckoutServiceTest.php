<?php

namespace Tests\Unit\Services;

use App\Http\Services\CheckoutService;
use App\Models\Discount;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    protected CheckoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CheckoutService;
    }

    public function test_calculates_total_without_discount_or_credit(): void
    {
        $result = $this->service->calculateTotalAndUserCredit(
            subtotal: 100.00,
            user: null,
            discount: null,
            ticketCount: 10
        );

        $this->assertEquals(100.00, $result['total']);
        $this->assertEquals(0, $result['user_credit_used']);
    }

    public function test_per_ticket_amount_off_discount_applies_correctly(): void
    {
        $discount = new Discount([
            'type' => Discount::TYPE_PER_TICKET,
            'unit' => Discount::UNIT_AMOUNT_OFF,
            'amount' => 5.00,
        ]);

        $result = $this->service->calculateTotalAndUserCredit(
            subtotal: 100.00, // 10 tickets @ £10
            user: null,
            discount: $discount,
            ticketCount: 10
        );

        // £100 - (£5 x 10 tickets) = £50
        $this->assertEquals(50.00, $result['total']);
    }

    public function test_per_ticket_percent_off_discount_applies_correctly(): void
    {
        $discount = new Discount([
            'type' => Discount::TYPE_PER_TICKET,
            'unit' => Discount::UNIT_PERCENT_OFF,
            'amount' => 20, // 20% off
        ]);

        $result = $this->service->calculateTotalAndUserCredit(
            subtotal: 100.00,
            user: null,
            discount: $discount,
            ticketCount: 10
        );

        // £100 - 20% = £80
        $this->assertEquals(80.00, $result['total']);
    }

    public function test_batch_ticket_equal_discount_only_applies_when_exact_match(): void
    {
        $discount = new Discount([
            'type' => Discount::TYPE_BATCH_TICKET,
            'unit' => Discount::UNIT_AMOUNT_OFF,
            'amount' => 10.00,
            'tickets' => 10,
            'ticket_type' => Discount::TICKET_TYPE_EQUAL,
        ]);

        // Exact match - should apply
        $result = $this->service->calculateTotalAndUserCredit(100.00, null, $discount, 10);
        $this->assertEquals(90.00, $result['total']);

        // Not exact match - should NOT apply
        $result = $this->service->calculateTotalAndUserCredit(90.00, null, $discount, 9);
        $this->assertEquals(90.00, $result['total']);

        // More than required - should NOT apply (equal only)
        $result = $this->service->calculateTotalAndUserCredit(110.00, null, $discount, 11);
        $this->assertEquals(110.00, $result['total']);
    }

    public function test_batch_ticket_equal_or_more_rolling_applies_multiple_times(): void
    {
        $discount = new Discount([
            'type' => Discount::TYPE_BATCH_TICKET,
            'unit' => Discount::UNIT_AMOUNT_OFF,
            'amount' => 5.00,
            'tickets' => 5,
            'ticket_type' => Discount::TICKET_TYPE_EQUAL_OR_MORE_THAN_WITH_ROLLING,
        ]);

        // Buy 15 tickets = 3 batches of 5
        $result = $this->service->calculateTotalAndUserCredit(150.00, null, $discount, 15);

        // £5 off x 3 = £15 off
        $this->assertEquals(135.00, $result['total']);
    }

    public function test_batch_ticket_equal_or_more_without_rolling_applies_once(): void
    {
        $discount = new Discount([
            'type' => Discount::TYPE_BATCH_TICKET,
            'unit' => Discount::UNIT_PERCENT_OFF,
            'amount' => 10, // 10% off
            'tickets' => 5,
            'ticket_type' => Discount::TICKET_TYPE_EQUAL_OR_MORE_THAN_WITHOUT_ROLLING,
        ]);

        // Buy 15 tickets - discount applies once
        $result = $this->service->calculateTotalAndUserCredit(150.00, null, $discount, 15);

        // 10% off = £15 off (applied once, not three times)
        $this->assertEquals(135.00, $result['total']);
    }

    public function test_checkout_total_amount_off_discount(): void
    {
        $discount = new Discount([
            'type' => Discount::TYPE_CHECKOUT_TOTAL,
            'unit' => Discount::UNIT_AMOUNT_OFF,
            'amount' => 20.00,
        ]);

        $result = $this->service->calculateTotalAndUserCredit(100.00, null, $discount, 5);

        $this->assertEquals(80.00, $result['total']);
    }

    public function test_checkout_total_percent_off_discount(): void
    {
        $discount = new Discount([
            'type' => Discount::TYPE_CHECKOUT_TOTAL,
            'unit' => Discount::UNIT_PERCENT_OFF,
            'amount' => 15, // 15% off
        ]);

        $result = $this->service->calculateTotalAndUserCredit(100.00, null, $discount, 10);

        $this->assertEquals(85.00, $result['total']);
    }

    public function test_minimum_total_is_one_pence(): void
    {
        $discount = new Discount([
            'type' => Discount::TYPE_CHECKOUT_TOTAL,
            'unit' => Discount::UNIT_AMOUNT_OFF,
            'amount' => 200.00, // More than subtotal
        ]);

        $result = $this->service->calculateTotalAndUserCredit(50.00, null, $discount, 5);

        // Should be minimum 0.01, not negative
        $this->assertGreaterThanOrEqual(0.01, $result['total']);
    }
}
