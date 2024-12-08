<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateToClickhouse extends Command
{
    protected $signature = 'app:migrate-to-clickhouse';

    protected $description = 'Migrate data from MariaDB to ClickHouse';

    public function handle()
    {
        $this->info('Starting data migration to ClickHouse...');

        $products = DB::table('products')->get();
        foreach ($products as $product) {
            DB::connection('clickhouse')->table('products')->insert([
                'id' => $product->id,
                'name' => $product->name,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ]);
        }
        $this->info('Products migrated.');

        $productVariants = DB::table('product_variants')->get();
        foreach ($productVariants as $variant) {
            DB::connection('clickhouse')->table('product_variants')->insert([
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'upc' => $variant->upc,
                'price' => $variant->price,
                'product_id' => $variant->product_id,
                'meta' => $variant->meta,
                'created_at' => $variant->created_at,
                'updated_at' => $variant->updated_at,
            ]);
        }
        $this->info('Product variants migrated.');

        $this->info('Data migration completed successfully!');
    }
}
