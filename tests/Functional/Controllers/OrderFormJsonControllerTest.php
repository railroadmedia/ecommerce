<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Controllers\OrderFormJsonController;
use Railroad\Ecommerce\Factories\ProductFactory;
use Railroad\Ecommerce\Factories\ShippingCostsFactory;
use Railroad\Ecommerce\Factories\ShippingOptionFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderFormJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var ShippingOptionFactory
     */
    protected $shippingOptionFactory;

    /**
     * @var ShippingCostsFactory
     */
    protected $shippingCostsFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->productFactory = $this->app->make(ProductFactory::class);
        $this->shippingOptionFactory = $this->app->make(ShippingOptionFactory::class);
        $this->shippingCostsFactory = $this->app->make(ShippingCostsFactory::class);
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

        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingOption2 = $this->shippingOptionFactory->store('*', 0,1);

        $this->shippingCostsFactory->store($shippingOption['id'], 0, 1000, 520);

        $this->shippingCostsFactory->store($shippingOption2['id'], 0,1,13.50);
        $this->shippingCostsFactory->store($shippingOption2['id'], 1,2,19);
        $this->shippingCostsFactory->store($shippingOption2['id'], 2,50,24);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product1['sku'] => 2,
                $product2['sku'] => 3]
        ]);

        $results = $this->call('GET', '/order');
        $decodedResults = $results->decodeResponseJson();

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertNull($decodedResults['results']['shippingAddress']);
        $this->assertEquals(0, $decodedResults['results']['shippingCosts']);
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
