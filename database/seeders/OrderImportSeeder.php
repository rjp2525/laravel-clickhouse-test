<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderImportSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data');

        DB::unprepared("LOAD DATA LOCAL INFILE '$path/addresses.csv'
            INTO TABLE addresses
            FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
            LINES TERMINATED BY '\n'
            IGNORE 1 ROWS
            (id, name, address_1, address_2, city, state, country, postal_code, created_at, updated_at)");

        DB::unprepared("LOAD DATA LOCAL INFILE '$path/sales_orders.csv'
            INTO TABLE sales_orders
            FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
            LINES TERMINATED BY '\n'
            IGNORE 1 ROWS
            (id, billing_address_id, shipping_address_id, total, discount, created_at, updated_at)");

        DB::unprepared("LOAD DATA LOCAL INFILE '$path/sales_order_rows.csv'
            INTO TABLE sales_order_rows
            FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
            LINES TERMINATED BY '\n'
            IGNORE 1 ROWS
            (id, sales_order_id, product_id, product_variant_id, quantity, price, created_at, updated_at)");
    }
}
