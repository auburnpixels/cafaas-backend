<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Prize;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrizeFactory extends Factory
{
    protected $model = Prize::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'value' => $this->faker->numberBetween(100, 5000),
            'place' => 1,
            'competition_id' => Competition::factory(),
        ];
    }

    public function firstPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'place' => 1,
        ]);
    }

    public function secondPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'place' => 2,
        ]);
    }

    public function thirdPlace(): static
    {
        return $this->state(fn (array $attributes) => [
            'place' => 3,
        ]);
    }
}
