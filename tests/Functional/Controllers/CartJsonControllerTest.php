<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class CartJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var Store
     */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->app->make(Store::class);
    }

    public function test_add_to_cart()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
        ]);

        $initialQuantity = 2;

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$product['sku'] => $initialQuantity],
        ]);

        // response asserts

        // assert response status code
        $this->assertEquals(200, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'discounts',
                        'shipping_address',
                        'billing_address',
                        'number_of_payments',
                        'totals' => [
                            'shipping',
                            'tax',
                            'due'
                        ]
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection count
        $this->assertEquals(1, count($decodedResponse['meta']['cart']['items']));

        // assert cart item data
        $this->assertEquals(
            [
                'sku'                         => $product['sku'],
                'name'                        => $product['name'],
                'quantity'                    => $initialQuantity,
                'thumbnail_url'               => $product['thumbnail_url'],
                'description'                 => $product['description'],
                'stock'                       => $product['stock'],
                'subscription_interval_type'  => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'price_before_discounts'      => $product['price'],
                'price_after_discounts'       => 0, // todo - refactor
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $totalDue = $product['price'] * $initialQuantity;

        // assert total due
        $this->assertEquals(
            $totalDue,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(1, count($cart->getItems()));

        // assert cart item
        $cartItemOne = $cart->getItemBySku($product['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($initialQuantity, $cartItemOne->getQuantity());
    }

    public function test_add_product_with_stock_empty_to_cart()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => 0,
        ]);

        $quantity = $this->faker->numberBetween(2, 10);

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
        ]);

        // assert response status code
        $this->assertEquals(200, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'discounts',
                        'shipping_address',
                        'billing_address',
                        'number_of_payments',
                        'totals' => [
                            'shipping',
                            'tax',
                            'due'
                        ],
                        'errors',
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection is empty
        $this->assertTrue(empty($decodedResponse['meta']['cart']['items']));

        $this->assertEquals(
            ['Product ' . $product['name'] . ' is currently out of stock, please check back later.'],
            $decodedResponse['meta']['cart']['errors']
        );

        $this->assertEquals(
            0,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertTrue(empty($cart->getItems()));
    }

    public function test_add_inexistent_product_to_cart()
    {
        $randomSku = $this->faker->word;

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$randomSku => 10],
        ]);

        // assert response status code
        $this->assertEquals(200, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'discounts',
                        'shipping_address',
                        'billing_address',
                        'number_of_payments',
                        'totals' => [
                            'shipping',
                            'tax',
                            'due'
                        ],
                        'errors',
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection is empty
        $this->assertTrue(empty($decodedResponse['meta']['cart']['items']));

        $this->assertEquals(
            ['No product with SKU ' . $randomSku . ' was found.'],
            $decodedResponse['meta']['cart']['errors']
        );

        $this->assertEquals(
            0,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertTrue(empty($cart->getItems()));
    }

    public function test_add_many_products_to_cart()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productOneQuantity = 1;
        $productTwoQuantity = 2;

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [
                $productOne['sku'] => $productOneQuantity,
                $productTwo['sku'] => $productTwoQuantity,
            ],
        ]);

        // response asserts

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'discounts',
                        'shipping_address',
                        'billing_address',
                        'number_of_payments',
                        'totals' => [
                            'shipping',
                            'tax',
                            'due'
                        ]
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection count
        $this->assertEquals(2, count($decodedResponse['meta']['cart']['items']));

        // assert cart item data
        $this->assertEquals(
            [
                'sku'                         => $productOne['sku'],
                'name'                        => $productOne['name'],
                'quantity'                    => $productOneQuantity,
                'thumbnail_url'               => $productOne['thumbnail_url'],
                'description'                 => $productOne['description'],
                'stock'                       => $productOne['stock'],
                'subscription_interval_type'  => $productOne['subscription_interval_type'],
                'subscription_interval_count' => $productOne['subscription_interval_count'],
                'price_before_discounts'      => $productOne['price'],
                'price_after_discounts'       => 0, // todo - refactor
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $this->assertEquals(
            [
                'sku'                         => $productTwo['sku'],
                'name'                        => $productTwo['name'],
                'quantity'                    => $productTwoQuantity,
                'thumbnail_url'               => $productTwo['thumbnail_url'],
                'description'                 => $productTwo['description'],
                'stock'                       => $productTwo['stock'],
                'subscription_interval_type'  => $productTwo['subscription_interval_type'],
                'subscription_interval_count' => $productTwo['subscription_interval_count'],
                'price_before_discounts'      => $productTwo['price'],
                'price_after_discounts'       => 0, // todo - refactor
            ],
            $decodedResponse['meta']['cart']['items'][1]
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(2, count($cart->getItems()));

        // assert cart item one
        $cartItemOne = $cart->getItemBySku($productOne['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($productOneQuantity, $cartItemOne->getQuantity());

        // assert cart item two
        $cartItemTwo = $cart->getItemBySku($productTwo['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemTwo));

        $this->assertEquals($productTwoQuantity, $cartItemTwo->getQuantity());
    }

    public function test_add_to_cart_higher_amount_than_product_stock()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(1, 3),
        ]);

        $quantity = $this->faker->numberBetween(5, 100);

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
        ]);

        // assert response status code
        $this->assertEquals(200, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'discounts',
                        'shipping_address',
                        'billing_address',
                        'number_of_payments',
                        'totals' => [
                            'shipping',
                            'tax',
                            'due'
                        ],
                        'errors',
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection is empty
        $this->assertTrue(empty($decodedResponse['meta']['cart']['items']));

        $this->assertEquals(
            ['Product ' . $product['name'] . ' is currently out of stock, please check back later.'],
            $decodedResponse['meta']['cart']['errors']
        );

        $this->assertEquals(
            0,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertTrue(empty($cart->getItems()));
    }

    public function test_add_products_available_and_not_available_to_cart()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
        ]);

        $randomSku1 = $this->faker->word . 'sku1';
        $randomSku2 = $this->faker->word . 'sku2';

        $productOneQuantity = $this->faker->numberBetween(1, 5);
        $productTwoQuantity = $this->faker->numberBetween(1, 5);

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [
                $productOne['sku'] => $productOneQuantity,
                $randomSku1 => 2,
                $productTwo['sku'] => $productTwoQuantity,
                $randomSku2 => 2,
            ],
        ]);

        // assert response status code
        $this->assertEquals(200, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'discounts',
                        'shipping_address',
                        'billing_address',
                        'number_of_payments',
                        'totals' => [
                            'shipping',
                            'tax',
                            'due'
                        ],
                        'errors',
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection count
        $this->assertEquals(2, count($decodedResponse['meta']['cart']['items']));

        // assert cart item data
        $this->assertEquals(
            [
                'sku'                         => $productOne['sku'],
                'name'                        => $productOne['name'],
                'quantity'                    => $productOneQuantity,
                'thumbnail_url'               => $productOne['thumbnail_url'],
                'description'                 => $productOne['description'],
                'stock'                       => $productOne['stock'],
                'subscription_interval_type'  => $productOne['subscription_interval_type'],
                'subscription_interval_count' => $productOne['subscription_interval_count'],
                'price_before_discounts'      => $productOne['price'],
                'price_after_discounts'       => 0, // todo - refactor
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $this->assertEquals(
            [
                'sku'                         => $productTwo['sku'],
                'name'                        => $productTwo['name'],
                'quantity'                    => $productTwoQuantity,
                'thumbnail_url'               => $productTwo['thumbnail_url'],
                'description'                 => $productTwo['description'],
                'stock'                       => $productTwo['stock'],
                'subscription_interval_type'  => $productTwo['subscription_interval_type'],
                'subscription_interval_count' => $productTwo['subscription_interval_count'],
                'price_before_discounts'      => $productTwo['price'],
                'price_after_discounts'       => 0, // todo - refactor
            ],
            $decodedResponse['meta']['cart']['items'][1]
        );

        $totalDue = $productOne['price'] * $productOneQuantity + $productTwo['price'] * $productTwoQuantity;

        // assert total due
        $this->assertEquals(
            $totalDue,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        $this->assertEquals(
            [
                'No product with SKU ' . $randomSku1 . ' was found.',
                'No product with SKU ' . $randomSku2 .  ' was found.',
            ],
            $decodedResponse['meta']['cart']['errors']
        );

        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(2, count($cart->getItems()));

        // assert cart item one
        $cartItemOne = $cart->getItemBySku($productOne['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($productOneQuantity, $cartItemOne->getQuantity());

        // assert cart item two
        $cartItemTwo = $cart->getItemBySku($productTwo['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemTwo));

        $this->assertEquals($productTwoQuantity, $cartItemTwo->getQuantity());
    }
}
