<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->company() . ' Warehouse',
            'region'      => $this->faker->state(),
            'country'     => $this->faker->country(),
            'state'       => $this->faker->state(),
            'city'        => $this->faker->city(),
            'postal_code' => $this->faker->postcode(),
            'address'     => $this->faker->streetAddress(),
            'phone'       => $this->faker->phoneNumber(),
            'email'       => $this->faker->companyEmail(),
            'is_active'   => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
