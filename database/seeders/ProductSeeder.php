<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public const PRODUCTS_TO_GENERATE = 40;

    public function run(): void
    {
        $products = [];
        $variants = [];

        for ($i = 1; $i <= static::PRODUCTS_TO_GENERATE; $i++) {
            $productId = Str::ulid();
            $products[] = [
                'id' => $productId,
                'name' => Str::of(fake()->words(rand(1, 3), true))->title(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $variantCount = random_int(1, 3);
            for ($j = 0; $j < $variantCount; $j++) {
                $variants[] = [
                    'id' => Str::ulid(),
                    'product_id' => $productId,
                    'sku' => fake()->bothify('SKU-###-###'),
                    'upc' => fake()->numerify('############'),
                    'price' => fake()->numberBetween(50_00, 500_00),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('products')->insert($products);
        DB::table('product_variants')->insert($variants);
    }
}
