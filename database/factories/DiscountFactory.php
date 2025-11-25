<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'type' => Discount::TYPE_PER_TICKET,
            'unit' => Discount::UNIT_AMOUNT_OFF,
            'amount' => $this->faker->numberBetween(1, 20),
            'tickets' => null,
            'ticket_type' => null,
            'competition_id' => Competition::factory(),
        ];
    }

    public function perTicket(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Discount::TYPE_PER_TICKET,
        ]);
    }

    public function batchTicket(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Discount::TYPE_BATCH_TICKET,
            'tickets' => 10,
            'ticket_type' => Discount::TICKET_TYPE_EQUAL,
        ]);
    }

    public function checkoutTotal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Discount::TYPE_CHECKOUT_TOTAL,
        ]);
    }

    public function amountOff(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => Discount::UNIT_AMOUNT_OFF,
        ]);
    }

    public function percentOff(): static
    {
        return $this->state(fn (array $attributes) => [
            'unit' => Discount::UNIT_PERCENT_OFF,
            'amount' => $this->faker->numberBetween(5, 50), // Percentage
        ]);
    }
}
