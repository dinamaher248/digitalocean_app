<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'restaurants', 'name' => 'مطعم', 'icon' => 'restaurant', 'sort_order' => 1],
            ['slug' => 'pharmacies', 'name' => 'صيدلية', 'icon' => 'local_pharmacy', 'sort_order' => 2],
            ['slug' => 'supermarkets', 'name' => 'سوبر ماركت', 'icon' => 'shopping_cart', 'sort_order' => 3],
            ['slug' => 'clothing', 'name' => 'ملابس', 'icon' => 'checkroom', 'sort_order' => 4],
            ['slug' => 'gifts_flowers', 'name' => 'هدايا وورد', 'icon' => 'local_florist', 'sort_order' => 5],
            ['slug' => 'hotels', 'name' => 'فنادق', 'icon' => 'hotel', 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            DB::table('service_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
