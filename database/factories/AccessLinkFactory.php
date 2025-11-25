<?php

namespace Database\Factories;

use App\Models\AccessLink;
use App\Models\Competition;
use App\Models\Prize;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccessLinkFactory extends Factory
{
    protected $model = AccessLink::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'email' => $this->faker->safeEmail(),
            'status' => AccessLink::STATUS_AWAITING_PAYMENT,
            'expiry_at' => Carbon::now()->addHours(48),
            'shipping_price' => $this->faker->numberBetween(500, 2000), // in pence
            'competition_id' => Competition::factory(),
            'prize_id' => Prize::factory(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccessLink::STATUS_PAID,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccessLink::STATUS_EXPIRED,
            'expiry_at' => Carbon::now()->subHours(1),
        ]);
    }
}
