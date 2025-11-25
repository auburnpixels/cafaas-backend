<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompetitionFactory extends Factory
{
    protected $model = Competition::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(),
            'type' => Competition::TYPE_TRADITIONAL,
            'status' => Competition::STATUS_UNPUBLISHED,
            'ticket_price' => $this->faker->numberBetween(5, 50),
            'ticket_amount' => $this->faker->numberBetween(50, 200),
            'tickets_bought' => 0,
            'draw_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'ending_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'delivery_option' => 1,
            'is_drop' => false,
            'is_featured_drop' => false,
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Competition::STATUS_ACTIVE,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Competition::STATUS_UNPUBLISHED,
        ]);
    }

    public function awaitingDraw(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Competition::STATUS_AWAITING_DRAW,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Competition::STATUS_COMPLETED,
        ]);
    }

    public function drop(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_drop' => true,
            'ticket_price' => 0,
            'type' => Competition::TYPE_DROP,
        ]);
    }

    public function accessRaffle(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Competition::TYPE_ACCESS,
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'ticket_price' => 0,
        ]);
    }
}
