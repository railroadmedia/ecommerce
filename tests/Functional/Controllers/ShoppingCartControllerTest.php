<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShoppingCartControllerTest extends EcommerceTestCase
{
    /**
     * @var CartService
     */
    protected $classBeingTested;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(CartService::class);
    }

    public function test_add_to_cart()
    {
        $product = [
            'brand' => $this->faker->word,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => $this->faker->word,
            'active' => 1,
            'description' => $this->faker->word,
            'thumbnail_url' => null,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(1, 100),
            'weight' => null,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ];

        $product['id'] = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => 2]
        ]);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => 10]
        ]);

        $cart = $response->decodeResponseJson();
        $this->assertEquals([0=>$product], $cart['addedProducts']);
    }

    public function test_add_product_with_stock_empty_to_cart()
    {
        $product = [
            'brand' => $this->faker->word,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'stock' => 0,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $productId = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);
        $quantity = $this->faker->numberBetween(1,1000);
        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => $quantity]
        ]);

        $cart = $response->decodeResponseJson();

        $this->assertEquals([], $cart['addedProducts']);
        $this->assertEquals(0, $cart['cartNumberOfItems']);
        $this->assertEquals('Product with SKU:'.$product['sku'].' could not be added to cart. The product stock('.$product['stock'].') is smaller than the quantity you\'ve selected('.$quantity.')', $cart['notAvailableProducts'][0]);
    }

    public function test_add_inexistent_product_to_cart()
    {
        $randomSku = $this->faker->word;
        $response = $this->call('PUT', '/add-to-cart', [
            'products' => [$randomSku => 10]
        ]);

        $cart = $response->decodeResponseJson();
        $this->assertEquals([], $cart['addedProducts']);
        $this->assertEquals(0, $cart['cartNumberOfItems']);
        $this->assertEquals('Product with SKU:'.$randomSku.' could not be added to cart.', $cart['notAvailableProducts'][0]);
    }

    public function test_add_many_products_to_cart()
    {
        $product1 = [
            'brand' => $this->faker->word,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(2, 100),
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableProduct)->insertGetId($product1);

        $product2 = [
            'brand' => $this->faker->word,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(3, 100),
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableProduct)->insertGetId($product2);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product1['sku'] => 2,
                $product2['sku'] => 3]
        ]);

        $cart = $response->decodeResponseJson();
        $this->assertEquals(2, $cart['cartNumberOfItems']);
    }

    public function test_add_to_cart_higher_amount_than_product_stock()
    {
        $product = [
            'brand' => $this->faker->word,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(1, 3),
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $productId = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => $this->faker->numberBetween(5, 100)]
        ]);

        $cart = $response->decodeResponseJson();

        $this->assertEquals([], $cart['addedProducts']);
        $this->assertEquals(0, $cart['cartNumberOfItems']);
   }

   public function test_add_products_available_and_not_available_to_cart()
   {
       $product1 = [
           'brand' => $this->faker->word,
           'name' => $this->faker->word,
           'sku' => $this->faker->word,
           'price' => $this->faker->numberBetween(1, 10),
           'type' => ProductService::TYPE_PRODUCT,
           'active' => 1,
           'description' => $this->faker->word,
           'is_physical' => 0,
           'stock' => $this->faker->numberBetween(6, 3000),
           'created_on' => Carbon::now()->toDateTimeString()
       ];

       $productId1 = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product1);

       $product2 = [
           'brand' => $this->faker->word,
           'name' => $this->faker->word,
           'sku' => $this->faker->word,
           'price' => $this->faker->numberBetween(1, 10),
           'type' => ProductService::TYPE_PRODUCT,
           'active' => 1,
           'description' => $this->faker->word,
           'is_physical' => 0,
           'stock' => $this->faker->numberBetween(10, 300),
           'created_on' => Carbon::now()->toDateTimeString()
       ];

       $productId2 = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product2);

       $response = $this->call('PUT', '/add-to-cart/', [
           'products' => [$product1['sku'] => $this->faker->numberBetween(5, 100),
               $this->faker->word => 2,
               $product2['sku'] => $this->faker->numberBetween(1,10),
               $this->faker->word => 2]
       ]);
       $cart = $response->decodeResponseJson();

       $this->assertEquals(2, $cart['cartNumberOfItems']);
       $this->assertEquals(2, count($cart['notAvailableProducts']));
   }


    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
