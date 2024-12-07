<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                    'name' => Str::of(fake()->words(rand(1, 3), true))->title(),
                    'sku' => fake()->bothify('SKU-###-###'),
                    'upc' => fake()->numerify('###########'),
                    'price' => fake()->numberBetween(50_00, 500_00),
                    'meta' => json_encode([
                        'size' => fake()->randomElement(['S', 'M', 'L', 'XL', 'XXL']),
                        'color' => fake()->safeColorName(),
                        'material' => fake()->randomElement(['cotton', 'polyester', 'wool']),
                        'dimensions' => [
                            'length' => fake()->randomFloat(2, 1, 10),
                            'width' => fake()->randomFloat(2, 1, 10),
                            'height' => fake()->randomFloat(2, 1, 10),
                        ],
                        'weight' => fake()->randomFloat(2, 0.1, 5).' lb',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('products')->insert($products);
        DB::table('product_variants')->insert($variants);
    }
}
