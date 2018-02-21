<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Controllers\OrderFormJsonController;
use Railroad\Ecommerce\Factories\ProductFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderFormJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->productFactory = $this->app->make(ProductFactory::class);
    }

    public function testIndex()
    {
        $product1 = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => 10,
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' =>2,
            'stock' => $this->faker->numberBetween(5, 100),
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableProduct)->insertGetId($product1);

        $product2 = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => 5,
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableProduct)->insertGetId($product2);

        $shippingOption = [
            'country' =>'Canada',
            'active' => 1,
            'priority' => 1,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $shippingOption2 = [
            'country' =>'*',
            'active' => 1,
            'priority' => 0,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $shippingOptionId = $this->query()->table(ConfigService::$tableShippingOption)->insertGetId($shippingOption);
        $shippingOptionId2 = $this->query()->table(ConfigService::$tableShippingOption)->insertGetId($shippingOption2);

        $shippingOptionWeightRanges = [
            'shipping_option_id' => $shippingOptionId,
            'min' => 0,
            'max' => 1000,
        'price' => 520,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableShippingCostsWeightRange)->insertGetId($shippingOptionWeightRanges);

        $shippingOptionWeightRangesUni1 = [
            'shipping_option_id' => $shippingOptionId2,
            'min' => 0,
            'max' => 1,
            'price' => 13.50,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableShippingCostsWeightRange)->insertGetId($shippingOptionWeightRangesUni1);

        $shippingOptionWeightRangesUni2 = [
            'shipping_option_id' => $shippingOptionId2,
            'min' => 1,
            'max' => 2,
            'price' => 19,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableShippingCostsWeightRange)->insertGetId($shippingOptionWeightRangesUni2);
        $shippingOptionWeightRangesUni3 = [
            'shipping_option_id' => $shippingOptionId2,
            'min' => 2,
            'max' => 50,
            'price' => 24,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableShippingCostsWeightRange)->insertGetId($shippingOptionWeightRangesUni3);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product1['sku'] => 2,
                $product2['sku'] => 3]
        ]);

        $results = $this->call('GET', '/order');

        dd($results);
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
