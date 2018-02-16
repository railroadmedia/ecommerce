<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\ProductFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ProductServiceTest extends EcommerceTestCase
{
    /**
     * @var ProductService
     */
    protected $classBeingTested;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(ProductService::class);
        $this->productFactory = $this->app->make(ProductFactory::class);
    }

    public function test_store_product()
    {
        $brand = ConfigService::$brand;
        $name = $this->faker->word;
        $sku = $this->faker->word;
        $price = $this->faker->numberBetween(0, 19999);
        $type = ProductService::TYPE_PRODUCT;
        $active = true;
        $description = $this->faker->text;
        $thumbnail_url = null;
        $is_physical = true;
        $weight = 0;
        $subscription_interval_type = null;
        $subscription_interval_count = null;
        $stock = rand();

        $product = $this->classBeingTested->store($brand,
            $name,
            $sku,
            $price,
            $type,
            $active,
            $description,
            $thumbnail_url,
            $is_physical,
            $weight,
            $subscription_interval_type,
            $subscription_interval_count,
            $stock);

        $this->assertEquals([
            "id" => 1,
            "brand" => $brand,
            "name" => $name,
            "sku" => $sku,
            "price" => $price,
            "type" => $type,
            "active" => $active,
            "description" => $description,
            "thumbnail_url" => $thumbnail_url,
            "is_physical" => $is_physical,
            "weight" => $weight,
            "subscription_interval_type" => $subscription_interval_type,
            "subscription_interval_count" => $subscription_interval_count,
            "stock" => $stock,
            "created_on" => Carbon::now()->toDateTimeString(),
            "updated_on" => null
        ], $product);
    }

    public function test_get_product_when_not_exist()
    {
        $results = $this->classBeingTested->getById(rand());

        $this->assertNull($results);
    }

    public function test_get_product()
    {
        $product = $this->productFactory->store();

        $result = $this->classBeingTested->getById($product['id']);

        $this->assertEquals($product, $result);
    }

    public function test_update_inexistent_product()
    {
        $results = $this->classBeingTested->update(rand(), []);

        $this->assertNull($results);
    }

    public function test_update_product_price()
    {
        $product = $this->productFactory->store();

        $newPrice = $this->faker->numberBetween(1, 3000);

        $product = $this->classBeingTested->update($product['id'], ['price' => $newPrice]);

        $this->assertEquals($newPrice, $product['price']);
    }
}
