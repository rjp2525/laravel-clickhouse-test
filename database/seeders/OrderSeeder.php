<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public const CHUNK_SIZE = 2_000;

    public const TOTAL_ORDERS = 85_000_000;

    public const DIFFERENT_ADDRESS_PERCENTAGE = 15;

    public const REPEAT_CUSTOMER_PERCENTAGE = 10;

    public function run(): void
    {
        $repeatCustomers = [];
        $maxRepeatCustomers = (int) (static::TOTAL_ORDERS * (static::REPEAT_CUSTOMER_PERCENTAGE / 100));

        for ($i = 0; $i < static::TOTAL_ORDERS; $i += static::CHUNK_SIZE) {
            $salesOrders = [];
            $addresses = [];
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

            DB::table('addresses')->insert($addresses);
            DB::table('sales_orders')->insert($salesOrders);
            DB::table('sales_order_rows')->insert($rows);
        }
    }
}
