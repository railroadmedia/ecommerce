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

    public function test_add_product_to_cart()
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
            'stock' => $this->faker->numberBetween(1, 100),
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $productId = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => 2]
        ]);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => 10]
        ]);

        $cart = $response->decodeResponseJson();
        $this->assertEquals($cart[0]['quantity'], 12);
        $this->assertEquals($cart[0]['totalPrice'], $product['price'] * 12);
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
            'stock' => '0',
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $productId = $this->query()->table(ConfigService::$tableProduct)->insertGetId($product);

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [$product['sku'] => 2]
        ]);

        $cart = $response->decodeResponseJson();

        $this->assertArrayHasKey('error', $cart);
        $this->assertEquals('The product stock is empty and can not be added to cart.', $cart['error']['detail']);
    }

    public function test_add_inexistent_product_to_cart()
    {
        $response = $this->call('PUT', '/add-to-cart', [
            'products' => [$this->faker->word => 10]
        ]);

        $cart = $response->decodeResponseJson();
        $this->assertArrayHasKey('error', $cart);
        $this->assertEquals('The product could not be added to cart.', $cart['error']['detail']);
    }



    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
