<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ProductRepositoryTest extends EcommerceTestCase
{
    /**
     * @var ProductRepository
     */
    protected $classBeingTested;

    /**
     * ProductRepositoryTest constructor.
     * @param $classBeingTested
     */
    public function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(ProductRepository::class);
    }

    public function test_get_active_product_with_sku()
    {
        $product = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'thumbnail_url' => null,
            'is_physical' => 0,
            'weight' => null,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'stock' => $this->faker->numberBetween(1, 100),
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ];

        $productId = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $results = $this->classBeingTested->getActiveProductsByConditions(['sku' => $product['sku']]);

        $this->assertEquals(['0' => array_merge(['id' => $productId], $product)], $results);
    }


    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }


}
