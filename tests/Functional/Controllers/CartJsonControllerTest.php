<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
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

    public function test_index()
    {
        $this->session->flush();

        $cartService = $this->app->make(CartService::class);

        $product = $this->fakeProduct([
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'weight' => 1,
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
        ]);

        $initialQuantity = 2;

        $cartService->addToCart(
            $product['sku'],
            $initialQuantity,
            false,
            ''
        );

        $response = $this->call('GET', '/json/cart');

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
                'price_before_discounts'      => $product['price'] * $initialQuantity,
                'price_after_discounts'       => $product['price'] * $initialQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$product['is_physical'],
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

    public function test_add_to_cart()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $initialQuantity = 2;

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$product['sku'] => $initialQuantity],
        ]);

        // response asserts

        // assert response status code
        $this->assertEquals(201, $response->getStatusCode());

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
                'price_before_discounts'      => $product['price'] * $initialQuantity,
                'price_after_discounts'       => $product['price'] * $initialQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$product['is_physical'],
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
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $quantity = $this->faker->numberBetween(2, 10);

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
        ]);

        // assert response status code
        $this->assertEquals(201, $response->getStatusCode());

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
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        $this->assertFalse(empty($decodedResponse['meta']['cart']['items']));

        $this->assertNotEquals(
            0,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertFalse(empty($cart->getItems()));
    }

    public function test_add_inexistent_product_to_cart()
    {
        $randomSku = $this->faker->word;

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$randomSku => 10],
        ]);

        // assert response status code
        $this->assertEquals(403, $response->getStatusCode());

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
        $this->session->flush();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
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
                'price_after_discounts'       => $productOne['price'],
                'requires_shipping'           => true,
                'is_digital'                  => !$productOne['is_physical'],
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
                'price_before_discounts'      => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts'       => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$productTwo['is_physical'],
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
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $quantity = $this->faker->numberBetween(5, 100);

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
        ]);

        // assert response status code
        $this->assertEquals(201, $response->getStatusCode());

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
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        $this->assertFalse(empty($decodedResponse['meta']['cart']['items']));

        $this->assertNotEquals(
            0,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertFalse(empty($cart->getItems()));
    }

    public function test_add_products_available_and_not_available_to_cart()
    {
        $this->session->flush();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
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
        $this->assertEquals(403, $response->getStatusCode());

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
                'price_before_discounts'      => $productOne['price'] * $productOneQuantity,
                'price_after_discounts'       => $productOne['price'] * $productOneQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$productOne['is_physical'],
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
                'price_before_discounts'      => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts'       => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$productTwo['is_physical'],
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

    public function test_remove_cart_item()
    {
        $this->session->flush();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $cartService = $this->app->make(CartService::class);

        $productOneQuantity = $this->faker->numberBetween(1, 5);
        $productTwoQuantity = $this->faker->numberBetween(1, 5);

        $cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $response = $this->call(
            'DELETE',
            '/json/remove-from-cart/' . $productOne['sku']
        );

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
                'sku'                         => $productTwo['sku'],
                'name'                        => $productTwo['name'],
                'quantity'                    => $productTwoQuantity,
                'thumbnail_url'               => $productTwo['thumbnail_url'],
                'description'                 => $productTwo['description'],
                'stock'                       => $productTwo['stock'],
                'subscription_interval_type'  => $productTwo['subscription_interval_type'],
                'subscription_interval_count' => $productTwo['subscription_interval_count'],
                'price_before_discounts'      => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts'       => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$productTwo['is_physical'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $totalDue = $productTwo['price'] * $productTwoQuantity;

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
        $cartItemOne = $cart->getItemBySku($productTwo['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($productTwoQuantity, $cartItemOne->getQuantity());
    }

    public function test_remove_unexiting_cart_item()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $cartService = $this->app->make(CartService::class);

        $productQuantity = $this->faker->numberBetween(1, 5);

        $cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $randomSku = $this->faker->word . 'sku1';

        $response = $this->call(
            'DELETE',
            '/json/remove-from-cart/' . $randomSku
        );

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

        // assert the error message
        $this->assertEquals(
            ['No product with SKU ' . $randomSku . ' was found.'],
            $decodedResponse['meta']['cart']['errors']
        );

        // assert the cart data is the initial data

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection count
        $this->assertEquals(1, count($decodedResponse['meta']['cart']['items']));

        // assert cart item data
        $this->assertEquals(
            [
                'sku'                         => $product['sku'],
                'name'                        => $product['name'],
                'quantity'                    => $productQuantity,
                'thumbnail_url'               => $product['thumbnail_url'],
                'description'                 => $product['description'],
                'stock'                       => $product['stock'],
                'subscription_interval_type'  => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'price_before_discounts'      => $product['price'] * $productQuantity,
                'price_after_discounts'       => $product['price'] * $productQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$product['is_physical'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $totalDue = $product['price'] * $productQuantity;

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

        $this->assertEquals($productQuantity, $cartItemOne->getQuantity());
    }

    public function test_remove_sole_cart_item()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $cartService = $this->app->make(CartService::class);

        $productQuantity = $this->faker->numberBetween(1, 5);

        $cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $response = $this->call(
            'DELETE',
            '/json/remove-from-cart/' . $product['sku']
        );

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
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection count
        $this->assertTrue(empty($decodedResponse['meta']['cart']['items']));

        $this->assertEquals(
            [
                'shipping' => 0,
                'tax' => 0,
                'due' => 0,
                'product_taxes' => 0,
                'shipping_taxes' => 0
            ],
            $decodedResponse['meta']['cart']['totals']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertTrue(empty($cart->getItems()));
    }

    public function test_update_cart_item_quantity()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $cartService = $this->app->make(CartService::class);

        $initialProductQuantity = $this->faker->numberBetween(1, 5);

        $cartService->addToCart(
            $product['sku'],
            $initialProductQuantity,
            false,
            ''
        );

        $newProductQuantity = $this->faker->numberBetween(6, 10);

        $response = $this->call(
            'PATCH',
            '/json/update-product-quantity/' . $product['sku'] . '/' . $newProductQuantity
        );

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
                'quantity'                    => $newProductQuantity,
                'thumbnail_url'               => $product['thumbnail_url'],
                'description'                 => $product['description'],
                'stock'                       => $product['stock'],
                'subscription_interval_type'  => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'price_before_discounts'      => $product['price'] * $newProductQuantity,
                'price_after_discounts'       => $product['price'] * $newProductQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$product['is_physical'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $totalDue = $product['price'] * $newProductQuantity;

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

        $this->assertEquals($newProductQuantity, $cartItemOne->getQuantity());
    }

    public function test_update_cart_item_quantity_higher_amount_than_product_stock()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(3, 5),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $cartService = $this->app->make(CartService::class);

        $initialProductQuantity = $this->faker->numberBetween(1, 2);

        $cartService->addToCart(
            $product['sku'],
            $initialProductQuantity,
            false,
            ''
        );

        $newProductQuantity = $this->faker->numberBetween(6, 10);

        $response = $this->call(
            'PATCH',
            '/json/update-product-quantity/' . $product['sku'] . '/' . $newProductQuantity
        );

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
                        'errors'
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert the error message
        $this->assertEquals(
            ['Product ' . $product['name'] . ' is currently out of stock, please check back later.'],
            $decodedResponse['meta']['cart']['errors']
        );

        // assert the cart data is the initial data

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection count
        $this->assertEquals(1, count($decodedResponse['meta']['cart']['items']));

        // assert cart item data
        $this->assertEquals(
            [
                'sku'                         => $product['sku'],
                'name'                        => $product['name'],
                'quantity'                    => $initialProductQuantity,
                'thumbnail_url'               => $product['thumbnail_url'],
                'description'                 => $product['description'],
                'stock'                       => $product['stock'],
                'subscription_interval_type'  => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'price_before_discounts'      => $product['price'] * $initialProductQuantity,
                'price_after_discounts'       => $product['price'] * $initialProductQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$product['is_physical'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $totalDue = $product['price'] * $initialProductQuantity;

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

        $this->assertEquals($initialProductQuantity, $cartItemOne->getQuantity());
    }

    public function test_update_cart_item_quantity_inexistent_product()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'weight' => 2,
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'stock' => $this->faker->numberBetween(3, 5),
        ]);

        $cartService = $this->app->make(CartService::class);

        $productQuantity = $this->faker->numberBetween(1, 2);

        $cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $randomSku = $this->faker->word . 'sku1';
        $inexistentProductQuantity = $this->faker->numberBetween(6, 10);

        $response = $this->call(
            'PATCH',
            '/json/update-product-quantity/' . $randomSku . '/' . $inexistentProductQuantity
        );

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
                        'errors'
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert the error message
        $this->assertEquals(
            ['No product with SKU ' . $randomSku . ' was found.'],
            $decodedResponse['meta']['cart']['errors']
        );

        // assert the cart data is the initial data

        // assert items collection
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['items']));

        // assert items collection count
        $this->assertEquals(1, count($decodedResponse['meta']['cart']['items']));

        // assert cart item data
        $this->assertEquals(
            [
                'sku'                         => $product['sku'],
                'name'                        => $product['name'],
                'quantity'                    => $productQuantity,
                'thumbnail_url'               => $product['thumbnail_url'],
                'description'                 => $product['description'],
                'stock'                       => $product['stock'],
                'subscription_interval_type'  => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'price_before_discounts'      => $product['price'] * $productQuantity,
                'price_after_discounts'       => $product['price'] * $productQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$product['is_physical'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $totalDue = $product['price'] * $productQuantity;

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

        $this->assertEquals($productQuantity, $cartItemOne->getQuantity());
    }

    public function test_update_number_of_payments()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $cartService = $this->app->make(CartService::class);

        $productQuantity = $this->faker->numberBetween(1, 5);

        $cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $numberOfPayments = $this->getPaymentPlanOption();

        $response = $this->call(
            'PUT',
            '/json/update-number-of-payments/' . $numberOfPayments
        );

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
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $financeCharge = 1;

        $totalDue = (($product['price'] * $productQuantity + $financeCharge) / $numberOfPayments);

        // assert total due
        $this->assertEquals(
            $totalDue,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // assert response cart number of payments
        $this->assertEquals(
            $numberOfPayments,
            $decodedResponse['meta']['cart']['number_of_payments']
        );

        // backend assert
        $cart = Cart::fromSession();

        // assert session cart number of payments
        $this->assertEquals($numberOfPayments, $cart->getPaymentPlanNumberOfPayments());
    }

    public function test_store_address_new()
    {
        $this->session->flush();

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(15, 100),
                'is_physical' => true,
                'type' => Product::TYPE_PHYSICAL_ONE_TIME,
                'subscription_interval_type' => null,
                'subscription_interval_count' => null,
                'weight' => 10,
            ]
        );

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart($product['sku'], 1);

        $cartAddressService = $this->app->make(CartAddressService::class);

        $address = $cartAddressService->getShippingAddress();

        $shippingAddress = [
            'shipping_address_line_1' => $this->faker->streetName,
            'shipping_city' => $this->faker->city,
            'shipping_first_name' => $this->faker->word
        ];

        $response = $this->call('PUT', '/json/session-address', $shippingAddress);

        $address->setStreetLine1($shippingAddress['shipping_address_line_1']);
        $address->setCity($shippingAddress['shipping_city']);
        $address->setFirstName($shippingAddress['shipping_first_name']);

        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address, $addressFromSession);

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'cart' => [
                        'shipping_address' => $address->toArray()
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_store_address_existing_id()
    {
        $this->session->flush();

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(15, 100),
                'is_physical' => true,
                'type' => Product::TYPE_PHYSICAL_ONE_TIME,
                'subscription_interval_type' => null,
                'subscription_interval_count' => null,
                'weight' => 10,
            ]
        );

        $address = $this->fakeAddress();

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart($product['sku'], 1);

        $response = $this->call('PUT', '/json/session-address', ['shipping_address_id' => $address['id']]);

        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $expectedAddress = [
            'first_name' => $address['first_name'],
            'last_name' => $address['last_name'],
            'city' => $address['city'],
            'region' => $address['region'],
            'country' => $address['country'],
            'zip_or_postal_code' => $address['zip'],
            'street_line_two' => $address['street_line_2'],
            'street_line_one' => $address['street_line_1'],
        ];

        $this->assertEquals($expectedAddress, $addressFromSession->toArray());

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'cart' => [
                        'shipping_address' => $expectedAddress
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_store_address_supplement()
    {
        $this->session->flush();

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(15, 100),
                'is_physical' => true,
                'type' => Product::TYPE_PHYSICAL_ONE_TIME,
                'subscription_interval_type' => null,
                'subscription_interval_count' => null,
                'weight' => 10,
            ]
        );

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart($product['sku'], 1);

        // setup initial session address
        $address = new Address();

        $address->setStreetLine1($this->faker->address);
        $address->setCity($this->faker->city);
        $address->setLastName($this->faker->word);
        $address->setZip($this->faker->postcode);

        $cartAddressService = $this->app->make(CartAddressService::class);

        $cartAddressService->updateShippingAddress($address);

        $supplementAddress = [
            'shipping_country' => 'Serbia',
            'shipping_first_name' => $this->faker->word,
        ];

        $response = $this->call('PUT', '/json/session-address', $supplementAddress);

        $address->setCountry($supplementAddress['shipping_country']);
        $address->setFirstName($supplementAddress['shipping_first_name']);

        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address, $addressFromSession);

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'cart' => [
                        'shipping_address' => $address->toArray()
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_store_address_update()
    {
        $this->session->flush();

        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(15, 100),
                'is_physical' => true,
                'type' => Product::TYPE_PHYSICAL_ONE_TIME,
                'subscription_interval_type' => null,
                'subscription_interval_count' => null,
                'weight' => 10,
            ]
        );

        $cartService = $this->app->make(CartService::class);

        $cartService->addToCart($product['sku'], 1);

        // setup initial session address
        $address = new Address();

        $address->setStreetLine1($this->faker->address);
        $address->setCity($this->faker->city);
        $address->setLastName($this->faker->word);
        $address->setZip($this->faker->postcode);

        $cartAddressService = $this->app->make(CartAddressService::class);

        $cartAddressService->updateShippingAddress($address);

        // setup additional address data with field overwritten
        $supplementAddress = [
            'shipping_last_name' => $this->faker->word,
            'shipping_first_name' => $this->faker->word,
        ];

        $response = $this->call('PUT', '/json/session-address', $supplementAddress);

        $address->setLastName($supplementAddress['shipping_last_name']);
        $address->setFirstName($supplementAddress['shipping_first_name']);

        // assert session has the address data
        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address, $addressFromSession);

        // assert response has the address data
        $this->assertArraySubset(
            [
                'meta' => [
                    'cart' => [
                        'shipping_address' => $address->toArray()
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_update_cart_total_overrides()
    {
        $adminUser = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('can')
            ->willReturn(true);
        
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $cartService = $this->app->make(CartService::class);

        $productQuantity = $this->faker->numberBetween(1, 5);

        $cartService->addToCart(
            $product['sku'],
            $productQuantity,
            false,
            ''
        );

        $params = [
            'product_taxes_due_override' => rand(1, 100),
            'shipping_taxes_due_override' => rand(1, 100),
            'shipping_due_override' => rand(1, 100),
            'order_items_due_overrides' => [
                [
                    'sku' => $product['sku'],
                    'amount' => rand(1, 100),
                ]
            ],
        ];

        $productTotalDueExpected = $params['order_items_due_overrides'][0]['amount'] * $productQuantity;
        $taxesExpected = $params['product_taxes_due_override'] + $params['shipping_taxes_due_override'];
        $shippingExpected = $params['shipping_due_override'];

        $totalDueExpected = $productTotalDueExpected + $taxesExpected + $shippingExpected;

        $response = $this->call(
            'PATCH',
            '/json/update-total-overrides',
            $params
        );

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
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert total due
        $this->assertEquals(
            $totalDueExpected,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        $this->assertEquals(
            $taxesExpected,
            $decodedResponse['meta']['cart']['totals']['tax']
        );

        $this->assertEquals(
            $shippingExpected,
            $decodedResponse['meta']['cart']['totals']['shipping']
        );

        // backend assert
        $cart = Cart::fromSession();

        // assert session cart number of payments
        $this->assertEquals(1, $cart->getPaymentPlanNumberOfPayments());
    }

    public function test_index_subscription_free_trial_days_zero_due_today_order_total()
    {
        $this->session->flush();

        $cartService = $this->app->make(CartService::class);

        // subscription that starts billing after SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE discount
        $product = $this->fakeProduct([
            'sku' => 'a' . $this->faker->word,
            'price' => 12.95,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $discountDaysAmount = 10;

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'product_id' => $product['id'],
                'type' => DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE,
                'amount' => $discountDaysAmount,
                'expiration_date' => Carbon::now()->addDays(2)->toDateTimeString(), // discount not expired
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
                'min' => Carbon::now()
                    ->subDay(1),
                'max' => Carbon::now()
                    ->addDays(3),
            ]
        );

        $cartService->addToCart(
            $product['sku'],
            1,
            false,
            ''
        );

        $response = $this->call('GET', '/json/cart');

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
                'quantity'                    => 1,
                'thumbnail_url'               => $product['thumbnail_url'],
                'description'                 => $product['description'],
                'stock'                       => $product['stock'],
                'subscription_interval_type'  => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'price_before_discounts'      => $product['price'],
                'price_after_discounts'       => $product['price'],
                'requires_shipping'           => false,
                'is_digital'                  => !$product['is_physical'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        // assert total due today is zero
        $this->assertEquals(
            0,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(1, count($cart->getItems()));

        // assert cart item one
        $cartItemOne = $cart->getItemBySku($product['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals(1, $cartItemOne->getQuantity());
    }

    public function test_index_subscription_free_trial_days_and_physical()
    {
        $this->session->flush();

        $cartService = $this->app->make(CartService::class);

        // subscription that starts billing after SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE discount
        $productOne = $this->fakeProduct([
            'sku' => 'a' . $this->faker->word,
            'price' => 12.95,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $discountDaysAmount = 10;

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'product_id' => $productOne['id'],
                'type' => DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE,
                'amount' => $discountDaysAmount,
                'expiration_date' => Carbon::now()->addDays(2)->toDateTimeString(), // discount not expired
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
                'min' => Carbon::now()
                    ->subDay(1),
                'max' => Carbon::now()
                    ->addDays(3),
            ]
        );

        // a normal physical product
        $productTwo = $this->fakeProduct([
            'sku' => 'b' . $this->faker->word,
            'price' => 50.15,
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'weight' => 1,
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
        ]);

        $cartService->addToCart(
            $productOne['sku'],
            1,
            false,
            ''
        );

        $productTwoQuantity = 2;

        $cartService->addToCart(
            $productTwo['sku'],
            $productTwoQuantity,
            false,
            ''
        );

        $response = $this->call('GET', '/json/cart');

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
        $this->assertEquals(2, count($decodedResponse['meta']['cart']['items']));

        // assert cart item data
        $this->assertEquals(
            [
                'sku'                         => $productOne['sku'],
                'name'                        => $productOne['name'],
                'quantity'                    => 1,
                'thumbnail_url'               => $productOne['thumbnail_url'],
                'description'                 => $productOne['description'],
                'stock'                       => $productOne['stock'],
                'subscription_interval_type'  => $productOne['subscription_interval_type'],
                'subscription_interval_count' => $productOne['subscription_interval_count'],
                'price_before_discounts'      => $productOne['price'],
                'price_after_discounts'       => $productOne['price'],
                'requires_shipping'           => false,
                'is_digital'                  => !$productOne['is_physical'],
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
                'price_before_discounts'      => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts'       => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$productTwo['is_physical'],
            ],
            $decodedResponse['meta']['cart']['items'][1]
        );

        $expectedTotalDueToday = $productTwo['price'] * $productTwoQuantity; // order total does not contain cart item product one price because of SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE discount

        // assert total due
        $this->assertEquals(
            $expectedTotalDueToday,
            $decodedResponse['meta']['cart']['totals']['due']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items count
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertEquals(2, count($cart->getItems()));

        // assert cart item one
        $cartItemOne = $cart->getItemBySku($productOne['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals(1, $cartItemOne->getQuantity());

        // assert cart item two
        $cartItemTwo = $cart->getItemBySku($productTwo['sku']);

        $this->assertEquals(CartItem::class, get_class($cartItemTwo));

        $this->assertEquals($productTwoQuantity, $cartItemTwo->getQuantity());
    }

    public function test_add_to_cart_negative_product_price()
    {
        $this->session->flush();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'price' => 5,
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $discount = $this->fakeDiscount(
            [
                'active' => true,
                'product_id' => $productOne['id'],
                'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
                'expiration_date' => null,
                'amount' => 10
            ]
        );

        $discountCriteria = $this->fakeDiscountCriteria(
            [
                'discount_id' => $discount['id'],
                'type' => DiscountCriteriaService::DATE_REQUIREMENT_TYPE,
                'min' => Carbon::now()
                    ->subDay(1),
                'max' => Carbon::now()
                    ->addDays(3),
            ]
        );

        $productOneQuantity = $this->faker->numberBetween(1, 5);
        $productTwoQuantity = $this->faker->numberBetween(1, 5);

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [
                $productOne['sku'] => $productOneQuantity,
                $productTwo['sku'] => $productTwoQuantity,
            ],
        ]);

        // assert response status code
        $this->assertEquals(201, $response->getStatusCode());

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
                'price_before_discounts'      => $productOne['price'] * $productOneQuantity,
                'price_after_discounts'       => 0,
                'requires_shipping'           => true,
                'is_digital'                  => !$productOne['is_physical'],
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
                'price_before_discounts'      => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts'       => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping'           => true,
                'is_digital'                  => !$productTwo['is_physical'],
            ],
            $decodedResponse['meta']['cart']['items'][1]
        );

        $totalDue = $productTwo['price'] * $productTwoQuantity;

        // assert total due
        $this->assertEquals(
            $totalDue,
            $decodedResponse['meta']['cart']['totals']['due']
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
