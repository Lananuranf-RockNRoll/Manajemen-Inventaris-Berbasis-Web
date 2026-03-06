<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Warehouses sourced directly from ML-Dataset.csv
     * Unique WarehouseName values: 9 warehouses
     */
    public function run(): void
    {
        $warehouses = [
            [
                'name'        => 'Southlake Texas',
                'region'      => 'South America',
                'country'     => 'United States of America',
                'state'       => 'Texas',
                'city'        => 'Southlake',
                'postal_code' => '26192',
                'address'     => '2014 Jabberwocky Rd',
            ],
            [
                'name'        => 'Seattle Washington',
                'region'      => 'North America',
                'country'     => 'United States of America',
                'state'       => 'Washington',
                'city'        => 'Seattle',
                'postal_code' => null,
                'address'     => null,
            ],
            [
                'name'        => 'San Francisco',
                'region'      => 'North America',
                'country'     => 'United States of America',
                'state'       => 'California',
                'city'        => 'San Francisco',
                'postal_code' => null,
                'address'     => null,
            ],
            [
                'name'        => 'New Jersy',
                'region'      => 'North America',
                'country'     => 'United States of America',
                'state'       => 'New Jersey',
                'city'        => 'New Jersey',
                'postal_code' => null,
                'address'     => null,
            ],
            [
                'name'        => 'Toronto',
                'region'      => 'North America',
                'country'     => 'Canada',
                'state'       => 'Ontario',
                'city'        => 'Toronto',
                'postal_code' => null,
                'address'     => null,
            ],
            [
                'name'        => 'Mexico City',
                'region'      => 'North America',
                'country'     => 'Mexico',
                'state'       => null,
                'city'        => 'Mexico City',
                'postal_code' => null,
                'address'     => null,
            ],
            [
                'name'        => 'Beijing',
                'region'      => 'Asia',
                'country'     => 'China',
                'state'       => null,
                'city'        => 'Beijing',
                'postal_code' => null,
                'address'     => null,
            ],
            [
                'name'        => 'Bombay',
                'region'      => 'Asia',
                'country'     => 'India',
                'state'       => 'Maharashtra',
                'city'        => 'Mumbai',
                'postal_code' => null,
                'address'     => null,
            ],
            [
                'name'        => 'Sydney',
                'region'      => 'Australia',
                'country'     => 'Australia',
                'state'       => 'New South Wales',
                'city'        => 'Sydney',
                'postal_code' => null,
                'address'     => null,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(
                ['name' => $warehouse['name']],
                $warehouse
            );
        }
    }
}
