<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\CartFactory;
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

    /**
     * @var CartFactory
     */
    protected $cartFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(CartService::class);
        $this->cartFactory = $this->app->make(CartFactory::class);
    }

    public function test_add_to_cart()
    {
        $product = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => $this->faker->word,
            'active' => 1,
            'description' => $this->faker->word,
            'thumbnail_url' => null,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(15, 100),
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
        $this->assertEquals([0 => $product], $cart['results']['addedProducts']);
    }

    public function test_add_product_with_stock_empty_to_cart()
    {
        $product = [
            'brand' => ConfigService::$brand,
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
        $quantity = $this->faker->numberBetween(1, 1000);
        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => $quantity]
        ]);

        $cart = $response->decodeResponseJson();

        $this->assertEquals([], $cart['results']['addedProducts']);
        $this->assertEquals(0, $cart['results']['cartNumberOfItems']);
        $this->assertEquals('Product with SKU:' . $product['sku'] . ' could not be added to cart. The product stock(' . $product['stock'] . ') is smaller than the quantity you\'ve selected(' . $quantity . ')', $cart['results']['notAvailableProducts'][0]);
    }

    public function test_add_inexistent_product_to_cart()
    {
        $randomSku = $this->faker->word;
        $response = $this->call('PUT', '/add-to-cart', [
            'products' => [$randomSku => 10]
        ]);

        $cart = $response->decodeResponseJson();
        $this->assertEquals([], $cart['results']['addedProducts']);
        $this->assertEquals(0, $cart['results']['cartNumberOfItems']);
        $this->assertEquals('Product with SKU:' . $randomSku . ' could not be added to cart.', $cart['results']['notAvailableProducts'][0]);
    }

    public function test_add_many_products_to_cart()
    {
        $product1 = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(5, 100),
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableProduct)->insertGetId($product1);

        $product2 = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(5, 100),
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $this->query()->table(ConfigService::$tableProduct)->insertGetId($product2);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product1['sku'] => 2,
                $product2['sku'] => 3]
        ]);

        $cart = $response->decodeResponseJson();
        $this->assertEquals(2, $cart['results']['cartNumberOfItems']);
    }

    public function test_add_to_cart_higher_amount_than_product_stock()
    {
        $product = [
            'brand' => ConfigService::$brand,
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

        $this->assertEquals([], $cart['results']['addedProducts']);
        $this->assertEquals(0, $cart['results']['cartNumberOfItems']);
    }

    public function test_add_products_available_and_not_available_to_cart()
    {
        $product1 = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(10, 3000),
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $productId1 = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product1);

        $product2 = [
            'brand' => ConfigService::$brand,
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
            'products' => [$product1['sku'] => $this->faker->numberBetween(1, 5),
                $this->faker->word . 'sku1' => 2,
                $product2['sku'] => $this->faker->numberBetween(1, 5),
                $this->faker->word . 'sku2' => 2]
        ]);
        $cart = $response->decodeResponseJson();

        $this->assertEquals(2, $cart['results']['cartNumberOfItems']);
        $this->assertEquals(2, count($cart['results']['notAvailableProducts']));
    }

    public function test_remove_product_from_cart()
    {
        $product = [
            'brand' => ConfigService::$brand,
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
        $productId = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $cart = $this->cartFactory->addCartItem($product['name'],
            $product['description'],
            $this->faker->numberBetween(1, 1000),
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productId
            ]);

        $response = $this->call('PUT', '/remove-from-cart/' . $productId);

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_update_cart_item_quantity()
    {
        $product = [
            'brand' => ConfigService::$brand,
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
        $productId = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $firstQuantity = $this->faker->numberBetween(1, 5);
        $cart = $this->cartFactory->addCartItem($product['name'],
            $product['description'],
            $firstQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productId
            ]);
        $newQuantity = $this->faker->numberBetween(6, 10);
        $response = $this->call('PUT', '/update-product-quantity/' . $productId . '/' . $newQuantity);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(201, $decodedResponse['code']);
        $this->assertTrue($decodedResponse['results']['success']);
        $this->assertEquals($newQuantity, $decodedResponse['results']['addedProducts'][0]['quantity']);
    }

    public function test_update_cart_item_quantity_insufficient_stock()
    {
        $product = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(2, 5),
            'created_on' => Carbon::now()->toDateTimeString()
        ];
        $productId = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $firstQuantity = $this->faker->numberBetween(1, 2);

        $this->cartFactory->addCartItem($product['name'],
            $product['description'],
            $firstQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productId
            ]);

        $newQuantity = $this->faker->numberBetween(6, 10);
        $response = $this->call('PUT', '/update-product-quantity/' . $productId . '/' . $newQuantity);
        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(201, $decodedResponse['code']);
        $this->assertFalse($decodedResponse['results']['success']);
        $this->assertEquals($firstQuantity, $decodedResponse['results']['addedProducts'][0]['quantity']);
    }

    public function test_redirect_to_shop()
    {
        $product = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => $this->faker->word,
            'active' => 1,
            'description' => $this->faker->word,
            'thumbnail_url' => null,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(15, 100),
            'weight' => null,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ];

        $product['id'] = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => 2],
            'redirect' => '/shop'
        ]);

        $this->assertEquals('/shop', $response->decodeResponseJson()['results']['redirect']);
    }

    public function test_redirect_checkout()
    {
        $product = [
            'brand' => ConfigService::$brand,
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 10),
            'type' => $this->faker->word,
            'active' => 1,
            'description' => $this->faker->word,
            'thumbnail_url' => null,
            'is_physical' => 0,
            'stock' => $this->faker->numberBetween(15, 100),
            'weight' => null,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ];

        $product['id'] = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => 2]
        ]);

        $this->assertArrayNotHasKey('redirect', $response->decodeResponseJson()['results']);
    }


    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
