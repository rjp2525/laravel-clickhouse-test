<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateOrderData extends Command
{
    protected $signature = 'app:generate-order-data';

    protected $description = 'Generate fake order data for seeding';

    public const CHUNK_SIZE = 20_000;

    public const TOTAL_ORDERS = 5_000_000;

    public const DIFFERENT_ADDRESS_PERCENTAGE = 15;

    public const REPEAT_CUSTOMER_PERCENTAGE = 10;

    public function handle()
    {
        $this->info('Starting data generation...');

        $progressBar = $this->output->createProgressBar(static::TOTAL_ORDERS / static::CHUNK_SIZE);
        $progressBar->start();

        $repeatCustomers = [];
        $maxRepeatCustomers = (int) (static::TOTAL_ORDERS * (static::REPEAT_CUSTOMER_PERCENTAGE / 100));

        $addressFile = fopen(database_path('data/addresses.csv'), 'w');
        $salesOrderFile = fopen(database_path('data/sales_orders.csv'), 'w');
        $salesOrderRowsFile = fopen(database_path('data/sales_order_rows.csv'), 'w');

        fputcsv($addressFile, ['id', 'name', 'address_1', 'address_2', 'city', 'state', 'country', 'postal_code', 'created_at', 'updated_at']);
        fputcsv($salesOrderFile, ['id', 'billing_address_id', 'shipping_address_id', 'total', 'discount', 'created_at', 'updated_at']);
        fputcsv($salesOrderRowsFile, ['id', 'sales_order_id', 'product_id', 'product_variant_id', 'quantity', 'price', 'created_at', 'updated_at']);

        for ($i = 0; $i < static::TOTAL_ORDERS; $i += static::CHUNK_SIZE) {
            $addresses = [];
            $salesOrders = [];
            $rows = [];

            for ($j = 0; $j < static::CHUNK_SIZE; $j++) {
                $billingId = Str::ulid();
                $shippingId = $billingId;
                $isRepeatCustomer = random_int(1, 100) <= static::REPEAT_CUSTOMER_PERCENTAGE;

                if ($isRepeatCustomer && ! empty($repeatCustomers)) {
                    $repeatCustomer = $repeatCustomers[array_rand($repeatCustomers)];
                    $billingId = $repeatCustomer['billing_id'];
                    $shippingId = $repeatCustomer['shipping_id'];
                } else {
                    $addresses[] = [
                        'id' => $billingId,
                        'name' => fake()->name(),
                        'address_1' => fake()->streetAddress(),
                        'address_2' => fake()->buildingNumber(),
                        'city' => fake()->city(),
                        'state' => fake()->state(),
                        'country' => fake()->country(),
                        'postal_code' => fake()->postcode(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (random_int(1, 100) <= static::DIFFERENT_ADDRESS_PERCENTAGE) {
                        $shippingId = Str::ulid();
                        $addresses[] = [
                            'id' => $shippingId,
                            'name' => fake()->name(),
                            'address_1' => fake()->streetAddress(),
                            'address_2' => fake()->buildingNumber(),
                            'city' => fake()->city(),
                            'state' => fake()->state(),
                            'country' => fake()->country(),
                            'postal_code' => fake()->postcode(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (count($repeatCustomers) < $maxRepeatCustomers) {
                        $repeatCustomers[] = [
                            'billing_id' => $billingId,
                            'shipping_id' => $shippingId,
                        ];
                    }
                }

                $orderId = Str::ulid();
                $discount = random_int(0, 1) ? fake()->randomFloat(2, 1, 50) : null;
                $total = 0;
                $date = fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d');

                $salesOrders[] = [
                    'id' => $orderId,
                    'billing_address_id' => $billingId,
                    'shipping_address_id' => $shippingId,
                    'total' => 0,
                    'discount' => $discount,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];

                for ($k = 0; $k < random_int(1, 5); $k++) {
                    $variant = DB::table('product_variants')->inRandomOrder()->first();
                    $quantity = random_int(1, 10);
                    $price = $variant->price * $quantity;

                    $rows[] = [
                        'id' => Str::ulid(),
                        'sales_order_id' => $orderId,
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $quantity,
                        'price' => $price,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $total += $price;
                }

                $salesOrders[count($salesOrders) - 1]['total'] = $total;
            }

            $this->writeChunkToCsv($addressFile, $addresses);
            $this->writeChunkToCsv($salesOrderFile, $salesOrders);
            $this->writeChunkToCsv($salesOrderRowsFile, $rows);

            $progressBar->advance();
        }

        fclose($addressFile);
        fclose($salesOrderFile);
        fclose($salesOrderRowsFile);

        $progressBar->finish();
        $this->info("\nData generation complete!");
    }

    private function writeChunkToCsv($file, array $data)
    {
        foreach ($data as $row) {
            fputcsv($file, $row);
        }
    }
}
