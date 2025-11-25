<?php

namespace Database\Factories;

use App\Models\Checkout;
use App\Models\Competition;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckoutFactory extends Factory
{
    protected $model = Checkout::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'email' => $this->faker->safeEmail(),
            'expiry_at' => Carbon::now()->addMinutes(15),
            'completed' => null,
            'competition_id' => Competition::factory(),
            'user_id' => null,
            'discount_id' => null,
        ];
    }

    public function withUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_at' => Carbon::now()->subMinutes(5),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed' => Carbon::now(),
        ]);
    }
}
