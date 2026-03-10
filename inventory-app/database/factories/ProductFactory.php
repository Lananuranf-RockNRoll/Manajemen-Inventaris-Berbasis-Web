<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $cost  = $this->faker->randomFloat(2, 10, 500);
        $price = round($cost * $this->faker->randomFloat(2, 1.1, 2.5), 2);

        return [
            'category_id'   => Category::factory(),
            'sku'           => strtoupper($this->faker->unique()->bothify('??-####')),
            'name'          => $this->faker->words(3, true),
            'description'   => $this->faker->sentence(),
            'standard_cost' => $cost,
            'list_price'    => $price,
            'is_active'     => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
