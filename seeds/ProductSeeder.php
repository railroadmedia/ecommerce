<?php

use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * ProductSeeder constructor.
     */
    public function __construct()
    {
        $this->faker = new Generator();
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_products')
            ->insert([
                // simple physical product
                [
                    'brand' => 'drumeo',
                    'name' => 'Drum Sticks',
                    'sku' => 'drum-sticks',
                    'price' => 19.99,
                    'type' => config('ecommerce.type_product'),
                    'active' => true,
                    'category' => 'accessories',
                    'description' => 'Our amazing drum stick design never breaks!',
                    'thumbnail_url' => $this->faker->imageUrl(300, 300),
                    'is_physical' => true,
                    'weight' => 1.5,
                    'subscription_interval_type' => null,
                    'subscription_interval_count' => null,
                    'stock' => 110,
                    'created_at' => Carbon::now()
                        ->toDateTimeString(),
                    'updated_at' => Carbon::now()
                        ->toDateTimeString(),
                ],

                // simple physical product another brand
                [
                    'brand' => 'pianote',
                    'name' => 'Piano Stand',
                    'sku' => 'piano stand',
                    'price' => 69.99,
                    'type' => config('ecommerce.type_product'),
                    'active' => true,
                    'category' => 'accessories',
                    'description' => 'Checkout our piano stand that can hold any piano.',
                    'thumbnail_url' => $this->faker->imageUrl(300, 300),
                    'is_physical' => true,
                    'weight' => 15.1,
                    'subscription_interval_type' => null,
                    'subscription_interval_count' => null,
                    'stock' => 10,
                    'created_at' => Carbon::now()
                        ->toDateTimeString(),
                    'updated_at' => Carbon::now()
                        ->toDateTimeString(),
                ],

                // simple physical product disabled
                [
                    'brand' => 'pianote',
                    'name' => 'Pianote Gloves',
                    'sku' => 'piano-gloves',
                    'price' => 146.00,
                    'type' => config('ecommerce.type_product'),
                    'active' => false,
                    'category' => 'accessories',
                    'description' => 'Checkout our piano stand that can hold any piano.',
                    'thumbnail_url' => $this->faker->imageUrl(300, 300),
                    'is_physical' => true,
                    'weight' => 15.1,
                    'subscription_interval_type' => null,
                    'subscription_interval_count' => null,
                    'stock' => 10,
                    'created_at' => Carbon::now()
                        ->toDateTimeString(),
                    'updated_at' => Carbon::now()
                        ->toDateTimeString(),
                ],
            ]);
    }
}