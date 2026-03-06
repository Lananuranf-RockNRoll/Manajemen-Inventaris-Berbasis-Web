<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Seed initial inventory: assign each product to every warehouse
     * with a default qty_on_hand of 500 and min_stock of 10.
     * Dataset does not provide per-warehouse stock levels, so defaults are used.
     */
    public function run(): void
    {
        $products   = Product::all();
        $warehouses = Warehouse::all();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                Inventory::updateOrCreate(
                    [
                        'product_id'   => $product->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    [
                        'qty_on_hand'  => 500,
                        'qty_reserved' => 0,
                        'min_stock'    => 10,
                        'max_stock'    => 1000,
                    ]
                );
            }
        }
    }
}
