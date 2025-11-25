<?php

namespace Database\Factories;

use App\Models\Checkout;
use App\Models\Competition;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'number' => (string) $this->faker->unique()->numberBetween(1, 999999),
            'competition_id' => Competition::factory(),
            'user_id' => null,
            'checkout_id' => null,
        ];
    }

    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }

    public function inCheckout(): static
    {
        return $this->state(fn (array $attributes) => [
            'checkout_id' => Checkout::factory(),
        ]);
    }
}
