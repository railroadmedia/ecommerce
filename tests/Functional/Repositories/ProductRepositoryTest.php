<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\ProductFactory;
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
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * ProductRepositoryTest constructor.
     * @param $classBeingTested
     */
    public function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(ProductRepository::class);
        $this->productFactory = $this->app->make(ProductFactory::class);
    }

    public function test_get_active_product_with_sku()
    {
        $product = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            $this->faker->numberBetween(1, 2000),
            $this->faker->randomElement(
                [
                    ProductService::TYPE_PRODUCT,
                    ProductService::TYPE_SUBSCRIPTION
                ]
            ),
            true);

        $results = $this->classBeingTested->getProductsByConditions(['sku' => $product['sku']]);

        $this->assertEquals(['0' => array_merge(['id' => $product['id']], $product)], $results);
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }


}
