<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->bothify('????####')),
            'type' => $this->faker->randomElement([Voucher::TYPE_FIXED_AMOUNT, Voucher::TYPE_PERCENTAGE]),
            'value' => $this->faker->randomFloat(2, 1, 50),
            'scope' => Voucher::SCOPE_HOST_SPECIFIC,
            'user_id' => User::factory(),
            'competition_id' => null,
            'uses_md5_email_validation' => false,
            'active' => true,
            'starts_at' => null,
            'expires_at' => null,
        ];
    }

    public function fixedAmount(float $amount = 5.00): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Voucher::TYPE_FIXED_AMOUNT,
            'value' => $amount,
        ]);
    }

    public function percentage(float $percent = 10): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Voucher::TYPE_PERCENTAGE,
            'value' => $percent,
        ]);
    }

    public function hostSpecific(User $host): self
    {
        return $this->state(fn (array $attributes) => [
            'scope' => Voucher::SCOPE_HOST_SPECIFIC,
            'user_id' => $host->id,
        ]);
    }

    public function competitionSpecific(Competition $competition): self
    {
        return $this->state(fn (array $attributes) => [
            'scope' => Voucher::SCOPE_COMPETITION_SPECIFIC,
            'competition_id' => $competition->id,
            'user_id' => $competition->user_id,
        ]);
    }

    public function md5EmailValidation(): self
    {
        return $this->state(fn (array $attributes) => [
            'uses_md5_email_validation' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
