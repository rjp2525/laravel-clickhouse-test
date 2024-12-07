<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    /**
     * The number of addresses to create.
     */
    public const ADDRESS_COUNT = 50;

    /**
     * Run the database seeds.
     */
    public function run()
    {
        for ($i = 0; $i < self::ADDRESS_COUNT; $i++) {
            Address::create([
                'name' => fake()->name,
                'address_1' => fake()->streetAddress(),
                'address_2' => fake()->optional()->secondaryAddress,
                'city' => fake()->city,
                'state' => fake()->state,
                'country' => fake()->country,
                'postal_code' => fake()->postcode,
            ]);
        }
    }
}
