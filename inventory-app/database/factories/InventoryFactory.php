<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'product_id'   => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'qty_on_hand'  => $this->faker->numberBetween(10, 200),
            'qty_reserved' => 0,
            'min_stock'    => 5,
            'max_stock'    => 500,
        ];
    }

    public function lowStock(): static
    {
        return $this->state([
            'qty_on_hand'  => 3,
            'qty_reserved' => 0,
            'min_stock'    => 5,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state([
            'qty_on_hand'  => 0,
            'qty_reserved' => 0,
        ]);
    }
}
