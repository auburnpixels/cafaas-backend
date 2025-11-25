<?php

namespace Database\Factories;

use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShippingAddressFactory extends Factory
{
    protected $model = ShippingAddress::class;

    public function definition(): array
    {
        return [
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional()->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip_code' => $this->faker->postcode(),
            'country' => 'GB',
            'user_id' => User::factory(),
        ];
    }
}
