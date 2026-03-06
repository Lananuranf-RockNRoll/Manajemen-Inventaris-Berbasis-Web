<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Categories sourced directly from ML-Dataset.csv CategoryName column.
     * Unique values: CPU, Mother Board, RAM, Storage, Video Card
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'CPU',         'slug' => 'cpu'],
            ['name' => 'Mother Board','slug' => 'mother-board'],
            ['name' => 'RAM',         'slug' => 'ram'],
            ['name' => 'Storage',     'slug' => 'storage'],
            ['name' => 'Video Card',  'slug' => 'video-card'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
