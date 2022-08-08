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
        $recommendedProducts = $this->addRecommendedProducts();

        $recommendedProductOne = [
            'sku' => $recommendedProducts[0]['sku'],
            'name' => $recommendedProducts[0]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[0]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[0]['sales_page_url'],
            'description' => $recommendedProducts[0]['description'],
            'stock' => $recommendedProducts[0]['stock'],
            'subscription_interval_type' => $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[0]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[0]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[0]['price'] : null,
            'price_before_discounts' => $recommendedProducts[0]['price'],
            'price_after_discounts' => $recommendedProducts[0]['price'],
            'requires_shipping' => $recommendedProducts[0]['is_physical'],
            'is_digital' => ($recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[0]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[0]['id'],
            'type' => $recommendedProducts[0]['type'],

        ];

        if (isset($recommendedProducts[0]['name_override'])) {
            $recommendedProductOne['name'] = $recommendedProducts[0]['name_override'];
        }

        if (isset($recommendedProducts[0]['product_page_url'])) {
            $recommendedProductOne['product_page_url'] = $recommendedProducts[0]['product_page_url'];
        }

        if (isset($recommendedProducts[0]['cta'])) {
            $recommendedProductOne['cta'] = $recommendedProducts[0]['cta'];
        }

        $recommendedProductTwo = [
            'sku' => $recommendedProducts[1]['sku'],
            'name' => $recommendedProducts[1]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[1]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[1]['sales_page_url'],
            'description' => $recommendedProducts[1]['description'],
            'stock' => $recommendedProducts[1]['stock'],
            'subscription_interval_type' => $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[1]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[1]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[1]['price'] : null,
            'price_before_discounts' => $recommendedProducts[1]['price'],
            'price_after_discounts' => $recommendedProducts[1]['price'],
            'requires_shipping' => $recommendedProducts[1]['is_physical'],
            'is_digital' => ($recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[1]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[1]['id'],
            'type' => $recommendedProducts[1]['type'],
        ];

        if (isset($recommendedProducts[1]['name_override'])) {
            $recommendedProductTwo['name'] = $recommendedProducts[1]['name_override'];
        }

        if (isset($recommendedProducts[1]['product_page_url'])) {
            $recommendedProductTwo['product_page_url'] = $recommendedProducts[1]['product_page_url'];
        }

        if (isset($recommendedProducts[1]['cta'])) {
            $recommendedProductTwo['cta'] = $recommendedProducts[1]['cta'];
        }

        $recommendedProductThree = [
            'sku' => $recommendedProducts[2]['sku'],
            'name' => $recommendedProducts[2]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[2]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[2]['sales_page_url'],
            'description' => $recommendedProducts[2]['description'],
            'stock' => $recommendedProducts[2]['stock'],
            'subscription_interval_type' => $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[2]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[2]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[2]['price'] : null,
            'price_before_discounts' => $recommendedProducts[2]['price'],
            'price_after_discounts' => $recommendedProducts[2]['price'],
            'requires_shipping' => $recommendedProducts[2]['is_physical'],
            'is_digital' => ($recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[2]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[2]['id'],
            'type' => $recommendedProducts[2]['type'],
        ];

        if (isset($recommendedProducts[2]['name_override'])) {
            $recommendedProductThree['name'] = $recommendedProducts[2]['name_override'];
        }

        if (isset($recommendedProducts[2]['product_page_url'])) {
            $recommendedProductThree['product_page_url'] = $recommendedProducts[2]['product_page_url'];
        }

        if (isset($recommendedProducts[2]['cta'])) {
            $recommendedProductThree['cta'] = $recommendedProducts[2]['cta'];
        }

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
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $initialQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $initialQuantity,
                'price_after_discounts' => $product['price'] * $initialQuantity,
                'requires_shipping' => true,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        // assert recommended products collection type
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['recommendedProducts']));

        // assert recommended products collection count
        $this->assertEquals(3, count($decodedResponse['meta']['cart']['recommendedProducts']));

        // assert recommended products collection data
        $this->assertEquals(
            [
                $recommendedProductOne,
                $recommendedProductTwo,
                $recommendedProductThree,
            ],
            $decodedResponse['meta']['cart']['recommendedProducts']
        );

        // assert total due
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

    public function test_index_user_owns_recommended_product()
    {
        $recommendedProducts = $this->addRecommendedProducts();

        $this->session->flush();

        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $userProductOne = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $recommendedProducts[1]['id'], // user already purchased 2nd product from the list
            ]
        );

        $recommendedProductOne = [
            'sku' => $recommendedProducts[0]['sku'],
            'name' => $recommendedProducts[0]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[0]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[0]['sales_page_url'],
            'description' => $recommendedProducts[0]['description'],
            'stock' => $recommendedProducts[0]['stock'],
            'subscription_interval_type' => $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[0]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[0]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[0]['price'] : null,
            'price_before_discounts' => $recommendedProducts[0]['price'],
            'price_after_discounts' => $recommendedProducts[0]['price'],
            'requires_shipping' => $recommendedProducts[0]['is_physical'],
            'is_digital' => ($recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[0]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[0]['id'],
            'type' => $recommendedProducts[0]['type'],

        ];

        if (isset($recommendedProducts[0]['name_override'])) {
            $recommendedProductOne['name'] = $recommendedProducts[0]['name_override'];
        }

        if (isset($recommendedProducts[0]['product_page_url'])) {
            $recommendedProductOne['product_page_url'] = $recommendedProducts[0]['product_page_url'];
        }

        if (isset($recommendedProducts[0]['cta'])) {
            $recommendedProductOne['cta'] = $recommendedProducts[0]['cta'];
        }

        // second recommented product is 3rd from the list
        $recommendedProductTwo = [
            'sku' => $recommendedProducts[2]['sku'],
            'name' => $recommendedProducts[2]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[2]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[2]['sales_page_url'],
            'description' => $recommendedProducts[2]['description'],
            'stock' => $recommendedProducts[2]['stock'],
            'subscription_interval_type' => $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[2]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[2]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[2]['price'] : null,
            'price_before_discounts' => $recommendedProducts[2]['price'],
            'price_after_discounts' => $recommendedProducts[2]['price'],
            'requires_shipping' => $recommendedProducts[2]['is_physical'],
            'is_digital' => ($recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[2]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[2]['id'],
            'type' => $recommendedProducts[2]['type'],
        ];

        if (isset($recommendedProducts[2]['name_override'])) {
            $recommendedProductTwo['name'] = $recommendedProducts[2]['name_override'];
        }

        if (isset($recommendedProducts[2]['product_page_url'])) {
            $recommendedProductTwo['product_page_url'] = $recommendedProducts[2]['product_page_url'];
        }

        if (isset($recommendedProducts[2]['cta'])) {
            $recommendedProductTwo['cta'] = $recommendedProducts[2]['cta'];
        }

        // third recommented product is 4th from the list
        $recommendedProductThree = [
            'sku' => $recommendedProducts[3]['sku'],
            'name' => $recommendedProducts[3]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[3]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[3]['sales_page_url'],
            'description' => $recommendedProducts[3]['description'],
            'stock' => $recommendedProducts[3]['stock'],
            'subscription_interval_type' => $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[3]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[3]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[3]['price'] : null,
            'price_before_discounts' => $recommendedProducts[3]['price'],
            'price_after_discounts' => $recommendedProducts[3]['price'],
            'requires_shipping' => $recommendedProducts[3]['is_physical'],
            'is_digital' => ($recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[3]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[3]['id'],
            'type' => $recommendedProducts[3]['type'],
        ];

        if (isset($recommendedProducts[3]['name_override'])) {
            $recommendedProductThree['name'] = $recommendedProducts[3]['name_override'];
        }

        if (isset($recommendedProducts[3]['product_page_url'])) {
            $recommendedProductThree['product_page_url'] = $recommendedProducts[3]['product_page_url'];
        }

        if (isset($recommendedProducts[3]['cta'])) {
            $recommendedProductThree['cta'] = $recommendedProducts[3]['cta'];
        }

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
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $initialQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $initialQuantity,
                'price_after_discounts' => $product['price'] * $initialQuantity,
                'requires_shipping' => true,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        // assert recommended products collection type
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['recommendedProducts']));

        // assert recommended products collection count
        $this->assertEquals(3, count($decodedResponse['meta']['cart']['recommendedProducts']));

        // assert recommended products collection data
        $this->assertEquals(
            [
                $recommendedProductOne,
                $recommendedProductTwo,
                $recommendedProductThree,
            ],
            $decodedResponse['meta']['cart']['recommendedProducts']
        );

        // assert total due
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
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $initialQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $initialQuantity,
                'price_after_discounts' => $product['price'] * $initialQuantity,
                'requires_shipping' => true,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
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

    public function test_add_recommended_product_to_cart()
    {
        $this->session->flush();

        $recommendedProductIndex = 2; // 3rd product from the recommended list
        $recommendedProducts = $this->addRecommendedProducts([
            $recommendedProductIndex => [
                'active' => 1,
                'stock' => $this->faker->numberBetween(15, 100),
                'is_physical' => true,
                'type' => Product::TYPE_PHYSICAL_ONE_TIME,
                'subscription_interval_type' => null,
                'subscription_interval_count' => null,
            ]
        ]);

        $product = $recommendedProducts[$recommendedProductIndex];

        $initialQuantity = 2;

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$product['sku'] => $initialQuantity],
        ]);

        $recommendedProductOne = [
            'sku' => $recommendedProducts[0]['sku'],
            'name' => $recommendedProducts[0]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[0]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[0]['sales_page_url'],
            'description' => $recommendedProducts[0]['description'],
            'stock' => $recommendedProducts[0]['stock'],
            'subscription_interval_type' => $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[0]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[0]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[0]['price'] : null,
            'price_before_discounts' => $recommendedProducts[0]['price'],
            'price_after_discounts' => $recommendedProducts[0]['price'],
            'requires_shipping' => $recommendedProducts[0]['is_physical'],
            'is_digital' => ($recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[0]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[0]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[0]['id'],
            'type' => $recommendedProducts[0]['type'],

        ];

        if (isset($recommendedProducts[0]['name_override'])) {
            $recommendedProductOne['name'] = $recommendedProducts[0]['name_override'];
        }

        if (isset($recommendedProducts[0]['product_page_url'])) {
            $recommendedProductOne['product_page_url'] = $recommendedProducts[0]['product_page_url'];
        }

        if (isset($recommendedProducts[0]['cta'])) {
            $recommendedProductOne['cta'] = $recommendedProducts[0]['cta'];
        }

        $recommendedProductTwo = [
            'sku' => $recommendedProducts[1]['sku'],
            'name' => $recommendedProducts[1]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[1]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[1]['sales_page_url'],
            'description' => $recommendedProducts[1]['description'],
            'stock' => $recommendedProducts[1]['stock'],
            'subscription_interval_type' => $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[1]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[1]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[1]['price'] : null,
            'price_before_discounts' => $recommendedProducts[1]['price'],
            'price_after_discounts' => $recommendedProducts[1]['price'],
            'requires_shipping' => $recommendedProducts[1]['is_physical'],
            'is_digital' => ($recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[1]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[1]['id'],
            'type' => $recommendedProducts[1]['type'],
        ];

        if (isset($recommendedProducts[1]['name_override'])) {
            $recommendedProductTwo['name'] = $recommendedProducts[1]['name_override'];
        }

        if (isset($recommendedProducts[1]['product_page_url'])) {
            $recommendedProductTwo['product_page_url'] = $recommendedProducts[1]['product_page_url'];
        }

        if (isset($recommendedProducts[1]['cta'])) {
            $recommendedProductTwo['cta'] = $recommendedProducts[1]['cta'];
        }

        // third recommented product is 4th from the list, because 3rd was added in cart
        $recommendedProductThree = [
            'sku' => $recommendedProducts[3]['sku'],
            'name' => $recommendedProducts[3]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[3]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[3]['sales_page_url'],
            'description' => $recommendedProducts[3]['description'],
            'stock' => $recommendedProducts[3]['stock'],
            'subscription_interval_type' => $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[3]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[3]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[3]['price'] : null,
            'price_before_discounts' => $recommendedProducts[3]['price'],
            'price_after_discounts' => $recommendedProducts[3]['price'],
            'requires_shipping' => $recommendedProducts[3]['is_physical'],
            'is_digital' => ($recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[3]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[3]['id'],
            'type' => $recommendedProducts[3]['type'],
        ];

        if (isset($recommendedProducts[3]['name_override'])) {
            $recommendedProductThree['name'] = $recommendedProducts[3]['name_override'];
        }

        if (isset($recommendedProducts[3]['product_page_url'])) {
            $recommendedProductThree['product_page_url'] = $recommendedProducts[3]['product_page_url'];
        }

        if (isset($recommendedProducts[3]['cta'])) {
            $recommendedProductThree['cta'] = $recommendedProducts[3]['cta'];
        }

        // response asserts

        // assert response status code
        $this->assertEquals(201, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $initialQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $initialQuantity,
                'price_after_discounts' => $product['price'] * $initialQuantity,
                'requires_shipping' => true,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        // assert recommended products collection type
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['recommendedProducts']));

        // assert recommended products collection count
        $this->assertEquals(3, count($decodedResponse['meta']['cart']['recommendedProducts']));

        // assert recommended products collection data
        $this->assertEquals(
            [
                $recommendedProductOne,
                $recommendedProductTwo,
                $recommendedProductThree,
            ],
            $decodedResponse['meta']['cart']['recommendedProducts']
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

    public function test_add_to_cart_exclude_recommended_product()
    {
        /*
            this test asserts that adding a product in cart that is configured in recommended products excluded_skus list, the respective recommended product will no be displayed
            examples:
            - if cart recommended products displays a product with variants, such as clothing, if adding any product variant to cart, it will not be displayed in cart recommended products
            - if cart displays a membership promo, if adding any membership to cart, it will not be displayed in cart recommended products
        */

        $recommendedProducts = $this->addRecommendedProducts();

        // this test assumes 1st configured recommended product has excluded list, if config is updated, this test should also be updated
        $this->assertTrue(is_array($recommendedProducts[0]['excluded_skus']));

        $excludeProductSkuAddedToCart = $this->faker->randomElement($recommendedProducts[0]['excluded_skus']);

        $response = $this->call('PUT', '/json/add-to-cart/', [
            'products' => [$excludeProductSkuAddedToCart => 1],
        ]);

        // first recommented product is 2nd from the list, because 1st was excluded
        $recommendedProductOne = [
            'sku' => $recommendedProducts[1]['sku'],
            'name' => $recommendedProducts[1]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[1]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[1]['sales_page_url'],
            'description' => $recommendedProducts[1]['description'],
            'stock' => $recommendedProducts[1]['stock'],
            'subscription_interval_type' => $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[1]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[1]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[1]['price'] : null,
            'price_before_discounts' => $recommendedProducts[1]['price'],
            'price_after_discounts' => $recommendedProducts[1]['price'],
            'requires_shipping' => $recommendedProducts[1]['is_physical'],
            'is_digital' => ($recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[1]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[1]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[1]['id'],
            'type' => $recommendedProducts[1]['type'],
        ];

        if (isset($recommendedProducts[1]['name_override'])) {
            $recommendedProductOne['name'] = $recommendedProducts[1]['name_override'];
        }

        if (isset($recommendedProducts[1]['product_page_url'])) {
            $recommendedProductOne['product_page_url'] = $recommendedProducts[1]['product_page_url'];
        }

        if (isset($recommendedProducts[1]['cta'])) {
            $recommendedProductOne['cta'] = $recommendedProducts[1]['cta'];
        }

        // second recommented product is 3rd from the list, because 1st was excluded
        $recommendedProductTwo = [
            'sku' => $recommendedProducts[2]['sku'],
            'name' => $recommendedProducts[2]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[2]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[2]['sales_page_url'],
            'description' => $recommendedProducts[2]['description'],
            'stock' => $recommendedProducts[2]['stock'],
            'subscription_interval_type' => $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[2]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[2]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[2]['price'] : null,
            'price_before_discounts' => $recommendedProducts[2]['price'],
            'price_after_discounts' => $recommendedProducts[2]['price'],
            'requires_shipping' => $recommendedProducts[2]['is_physical'],
            'is_digital' => ($recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[2]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[2]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[2]['id'],
            'type' => $recommendedProducts[2]['type'],
        ];

        if (isset($recommendedProducts[2]['name_override'])) {
            $recommendedProductTwo['name'] = $recommendedProducts[2]['name_override'];
        }

        if (isset($recommendedProducts[2]['product_page_url'])) {
            $recommendedProductTwo['product_page_url'] = $recommendedProducts[2]['product_page_url'];
        }

        if (isset($recommendedProducts[2]['cta'])) {
            $recommendedProductTwo['cta'] = $recommendedProducts[2]['cta'];
        }

        // third recommented product is 4th from the list, because 1st was excluded
        $recommendedProductThree = [
            'sku' => $recommendedProducts[3]['sku'],
            'name' => $recommendedProducts[3]['name'],
            'quantity' => 1,
            'thumbnail_url' => $recommendedProducts[3]['thumbnail_url'],
            'sales_page_url' => $recommendedProducts[3]['sales_page_url'],
            'description' => $recommendedProducts[3]['description'],
            'stock' => $recommendedProducts[3]['stock'],
            'subscription_interval_type' => $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[3]['subscription_interval_type'] : null,
            'subscription_interval_count' => $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[3]['subscription_interval_count'] : null,
            'subscription_renewal_price' => $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                $recommendedProducts[3]['price'] : null,
            'price_before_discounts' => $recommendedProducts[3]['price'],
            'price_after_discounts' => $recommendedProducts[3]['price'],
            'requires_shipping' => $recommendedProducts[3]['is_physical'],
            'is_digital' => ($recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_SUBSCRIPTION ||
                $recommendedProducts[3]['type'] == Product::TYPE_DIGITAL_ONE_TIME),
            'add_directly_to_cart' => $recommendedProducts[3]['add_directly_to_cart'] ?? true,
            'id' => $recommendedProducts[3]['id'],
            'type' => $recommendedProducts[3]['type'],
        ];

        if (isset($recommendedProducts[3]['name_override'])) {
            $recommendedProductThree['name'] = $recommendedProducts[3]['name_override'];
        }

        if (isset($recommendedProducts[3]['product_page_url'])) {
            $recommendedProductThree['product_page_url'] = $recommendedProducts[3]['product_page_url'];
        }

        if (isset($recommendedProducts[3]['cta'])) {
            $recommendedProductThree['cta'] = $recommendedProducts[3]['cta'];
        }

        // response asserts

        // assert response status code
        $this->assertEquals(201, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'recommendedProducts',
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

        // assert recommended products collection type
        $this->assertTrue(is_array($decodedResponse['meta']['cart']['recommendedProducts']));

        // assert recommended products collection count
        $this->assertEquals(3, count($decodedResponse['meta']['cart']['recommendedProducts']));

        // assert recommended products collection data
        $this->assertEquals(
            [
                $recommendedProductOne,
                $recommendedProductTwo,
                $recommendedProductThree,
            ],
            $decodedResponse['meta']['cart']['recommendedProducts']
        );
    }

    public function test_add_product_with_stock_empty_to_cart()
    {
        $this->addRecommendedProducts();

        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => 0,
            'min_stock_level' => 0,
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
        $this->assertEquals(403, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'recommendedProducts',
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

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertTrue(empty($cart->getItems()));
    }

    public function test_add_product_with_min_stock_level_too_low()
    {
        $this->addRecommendedProducts();

        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 50),
            'min_stock_level' => $this->faker->numberBetween(50, 1000),
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
        $this->assertEquals(403, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'recommendedProducts',
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

        $this->assertEquals(
            ['Product ' . $product['name'] . ' is currently out of stock, please check back later.'],
            $decodedResponse['meta']['cart']['errors']
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));



        $this->assertTrue(empty($cart->getItems()));
    }

    public function test_add_inexistent_product_to_cart()
    {
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
                'sku' => $productOne['sku'],
                'name' => $productOne['name'],
                'quantity' => $productOneQuantity,
                'thumbnail_url' => $productOne['thumbnail_url'],
                'sales_page_url' => $productOne['sales_page_url'],
                'description' => $productOne['description'],
                'stock' => $productOne['stock'],
                'subscription_interval_type' => $productOne['subscription_interval_type'],
                'subscription_interval_count' => $productOne['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'subscription_renewal_price' => null,
                'price_before_discounts' => $productOne['price'],
                'price_after_discounts' => $productOne['price'],
                'requires_shipping' => true,
                'is_digital' => !$productOne['is_physical'],
                'id' => $productOne['id'],
                'type' => $productOne['type'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $this->assertEquals(
            [
                'sku' => $productTwo['sku'],
                'name' => $productTwo['name'],
                'quantity' => $productTwoQuantity,
                'thumbnail_url' => $productTwo['thumbnail_url'],
                'sales_page_url' => $productTwo['sales_page_url'],
                'description' => $productTwo['description'],
                'stock' => $productTwo['stock'],
                'subscription_interval_type' => $productTwo['subscription_interval_type'],
                'subscription_interval_count' => $productTwo['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'subscription_renewal_price' => null,
                'price_before_discounts' => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts' => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping' => true,
                'is_digital' => !$productTwo['is_physical'],
                'id' => $productTwo['id'],
                'type' => $productTwo['type'],
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
        $this->addRecommendedProducts();

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
        $this->assertEquals(403, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'recommendedProducts',
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

        $this->assertTrue(empty($decodedResponse['meta']['cart']['items']));

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
        $this->addRecommendedProducts();

        $this->session->flush();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'min_stock_level' => 0,
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(10, 100),
            'min_stock_level' => 0,
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
                        'recommendedProducts',
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
                'sku' => $productOne['sku'],
                'name' => $productOne['name'],
                'quantity' => $productOneQuantity,
                'thumbnail_url' => $productOne['thumbnail_url'],
                'sales_page_url' => $productOne['sales_page_url'],
                'description' => $productOne['description'],
                'stock' => $productOne['stock'],
                'subscription_interval_type' => $productOne['subscription_interval_type'],
                'subscription_interval_count' => $productOne['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'subscription_renewal_price' => null,
                'price_before_discounts' => $productOne['price'] * $productOneQuantity,
                'price_after_discounts' => $productOne['price'] * $productOneQuantity,
                'requires_shipping' => true,
                'is_digital' => !$productOne['is_physical'],
                'id' => $productOne['id'],
                'type' => $productOne['type'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $this->assertEquals(
            [
                'sku' => $productTwo['sku'],
                'name' => $productTwo['name'],
                'quantity' => $productTwoQuantity,
                'thumbnail_url' => $productTwo['thumbnail_url'],
                'sales_page_url' => $productTwo['sales_page_url'],
                'description' => $productTwo['description'],
                'stock' => $productTwo['stock'],
                'subscription_interval_type' => $productTwo['subscription_interval_type'],
                'subscription_interval_count' => $productTwo['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'subscription_renewal_price' => null,
                'price_before_discounts' => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts' => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping' => true,
                'is_digital' => !$productTwo['is_physical'],
                'id' => $productTwo['id'],
                'type' => $productTwo['type'],
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
                'No product with SKU ' . $randomSku2 . ' was found.',
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
        $this->addRecommendedProducts();

        // todo - make product removed from cart a top recommended product, add asserts for recommended products & product removed is included in the list

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
                        'recommendedProducts',
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
                'sku' => $productTwo['sku'],
                'name' => $productTwo['name'],
                'quantity' => $productTwoQuantity,
                'thumbnail_url' => $productTwo['thumbnail_url'],
                'sales_page_url' => $productTwo['sales_page_url'],
                'description' => $productTwo['description'],
                'stock' => $productTwo['stock'],
                'subscription_interval_type' => $productTwo['subscription_interval_type'],
                'subscription_interval_count' => $productTwo['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts' => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping' => true,
                'is_digital' => !$productTwo['is_physical'],
                'id' => $productTwo['id'],
                'type' => $productTwo['type'],
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
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $productQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $productQuantity,
                'price_after_discounts' => $product['price'] * $productQuantity,
                'requires_shipping' => true,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
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
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
                'shipping_taxes' => 0,
                'shipping_before_override' => 0,
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
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $newProductQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $newProductQuantity,
                'price_after_discounts' => $product['price'] * $newProductQuantity,
                'requires_shipping' => true,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
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
        $this->addRecommendedProducts();

        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(3, 5),
            'min_stock_level' => 0,
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
        $this->assertEquals(403, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $initialProductQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $initialProductQuantity,
                'price_after_discounts' => $product['price'] * $initialProductQuantity,
                'requires_shipping' => true,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
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

    public function test_update_cart_item_quantity_higher_amount_than_min_stock_level()
    {
        $this->addRecommendedProducts();

        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(25, 30),
            'min_stock_level' => 20,
            'is_physical' => true,
            'type' => Product::TYPE_PHYSICAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $cartService = $this->app->make(CartService::class);

        $initialProductQuantity = $this->faker->numberBetween(2, 4);

        $cartService->addToCart(
            $product['sku'],
            $initialProductQuantity,
            false,
            ''
        );

        $newProductQuantity = $this->faker->numberBetween(10, 20);

        $response = $this->call(
            'PATCH',
            '/json/update-product-quantity/' . $product['sku'] . '/' . $newProductQuantity
        );

        // assert response status code
        $this->assertEquals(403, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $initialProductQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $initialProductQuantity,
                'price_after_discounts' => $product['price'] * $initialProductQuantity,
                'requires_shipping' => true,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
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
        $this->addRecommendedProducts();

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
        $this->assertEquals(403, $response->getStatusCode());

        // assert cart structure
        $response->assertJsonStructure(
            [
                'meta' => [
                    'cart' => [
                        'items',
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => $productQuantity,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $product['price'] * $productQuantity,
                'price_after_discounts' => $product['price'] * $productQuantity,
                'requires_shipping' => true,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
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
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
        $this->addRecommendedProducts();

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
        $this->addRecommendedProducts();

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
        $this->addRecommendedProducts();

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
            'shipping_address_line_1' => $address->getStreetLine1(),
            'shipping_city' => $address->getCity(),
            'shipping_last_name' => $address->getLastName(),
            'shipping_zip_or_postal_code' => $address->getZip(),
        ];

        $response = $this->call('PUT', '/json/session-address', $supplementAddress);

        $address->setCountry($supplementAddress['shipping_country']);
        $address->setFirstName($supplementAddress['shipping_first_name']);

        $cart = Cart::fromSession();

        $addressFromSession = $cart->getShippingAddress();

        $this->assertEquals($address->toArray(), $addressFromSession->toArray());

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
        $this->addRecommendedProducts();

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
            'shipping_country' => 'Serbia',
            'shipping_first_name' => $this->faker->word,
            'shipping_last_name' => $this->faker->word,
            'shipping_address_line_1' => $address->getStreetLine1(),
            'shipping_city' => $address->getCity(),
            'shipping_zip_or_postal_code' => $address->getZip(),
        ];

        $response = $this->call('PUT', '/json/session-address', $supplementAddress);

        $address->setCountry($supplementAddress['shipping_country']);
        $address->setFirstName($supplementAddress['shipping_first_name']);
        $address->setLastName($supplementAddress['shipping_last_name']);

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
        $this->addRecommendedProducts();

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
            'shipping_due_override' => rand(1, 100),
            'order_items_due_overrides' => [
                [
                    'sku' => $product['sku'],
                    'amount' => rand(1, 100),
                ]
            ],
        ];

        $productTotalDueExpected = $params['order_items_due_overrides'][0]['amount'] * $productQuantity;
        $shippingExpected = $params['shipping_due_override'];
        $taxesExpected = 0;

        $totalDueExpected = $productTotalDueExpected + $shippingExpected;

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
                        'recommendedProducts',
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
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
                'sku' => $product['sku'],
                'name' => $product['name'],
                'quantity' => 1,
                'thumbnail_url' => $product['thumbnail_url'],
                'sales_page_url' => $product['sales_page_url'],
                'description' => $product['description'],
                'stock' => $product['stock'],
                'subscription_interval_type' => $product['subscription_interval_type'],
                'subscription_interval_count' => $product['subscription_interval_count'],
                'subscription_renewal_price' => $product['price'],
                'price_before_discounts' => $product['price'],
                'price_after_discounts' => $product['price'],
                'requires_shipping' => false,
                'is_digital' => !$product['is_physical'],
                'id' => $product['id'],
                'type' => $product['type'],
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
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
                'sku' => $productOne['sku'],
                'name' => $productOne['name'],
                'quantity' => 1,
                'thumbnail_url' => $productOne['thumbnail_url'],
                'sales_page_url' => $productOne['sales_page_url'],
                'description' => $productOne['description'],
                'stock' => $productOne['stock'],
                'subscription_interval_type' => $productOne['subscription_interval_type'],
                'subscription_interval_count' => $productOne['subscription_interval_count'],
                'subscription_renewal_price' => $productOne['price'],
                'price_before_discounts' => $productOne['price'],
                'price_after_discounts' => $productOne['price'],
                'requires_shipping' => false,
                'is_digital' => !$productOne['is_physical'],
                'id' => $productOne['id'],
                'type' => $productOne['type'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $this->assertEquals(
            [
                'sku' => $productTwo['sku'],
                'name' => $productTwo['name'],
                'quantity' => $productTwoQuantity,
                'thumbnail_url' => $productTwo['thumbnail_url'],
                'sales_page_url' => $productTwo['sales_page_url'],
                'description' => $productTwo['description'],
                'stock' => $productTwo['stock'],
                'subscription_interval_type' => $productTwo['subscription_interval_type'],
                'subscription_interval_count' => $productTwo['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts' => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping' => true,
                'is_digital' => !$productTwo['is_physical'],
                'id' => $productTwo['id'],
                'type' => $productTwo['type'],
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
        $this->addRecommendedProducts();

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
                        'recommendedProducts',
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
                'sku' => $productOne['sku'],
                'name' => $productOne['name'],
                'quantity' => $productOneQuantity,
                'thumbnail_url' => $productOne['thumbnail_url'],
                'sales_page_url' => $productOne['sales_page_url'],
                'description' => $productOne['description'],
                'stock' => $productOne['stock'],
                'subscription_interval_type' => $productOne['subscription_interval_type'],
                'subscription_interval_count' => $productOne['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $productOne['price'] * $productOneQuantity,
                'price_after_discounts' => 0,
                'requires_shipping' => true,
                'is_digital' => !$productOne['is_physical'],
                'id' => $productOne['id'],
                'type' => $productOne['type'],
            ],
            $decodedResponse['meta']['cart']['items'][0]
        );

        $this->assertEquals(
            [
                'sku' => $productTwo['sku'],
                'name' => $productTwo['name'],
                'quantity' => $productTwoQuantity,
                'thumbnail_url' => $productTwo['thumbnail_url'],
                'sales_page_url' => $productTwo['sales_page_url'],
                'description' => $productTwo['description'],
                'stock' => $productTwo['stock'],
                'subscription_interval_type' => $productTwo['subscription_interval_type'],
                'subscription_interval_count' => $productTwo['subscription_interval_count'],
                'subscription_renewal_price' => null,
                'price_before_discounts' => $productTwo['price'] * $productTwoQuantity,
                'price_after_discounts' => $productTwo['price'] * $productTwoQuantity,
                'requires_shipping' => true,
                'is_digital' => !$productTwo['is_physical'],
                'id' => $productTwo['id'],
                'type' => $productTwo['type'],
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
