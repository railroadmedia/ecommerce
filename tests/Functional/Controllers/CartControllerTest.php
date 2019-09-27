<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class CartControllerTest extends EcommerceTestCase
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

        $country = 'United States';
        $region = 'Alaska';

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $cart = Cart::fromSession();

        $cart->setBillingAddress($billingAddress);

        $cart->toSession();

        $productPrice = $this->faker->randomFloat(2, 50, 90);

        $product = $this->fakeProduct([
            'active' => 1,
            'is_physical' => false,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'weight' => 0,
            'stock' => $this->faker->numberBetween(5, 100),
            'price' => 92.22,
        ]);

        $initialQuantity = 2;

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [$product['sku'] => $initialQuantity],
        ]);

        $totalDue = $product['price'] * $initialQuantity;

        // assert the session has the cart structure
        $response->assertSessionHas(
            'cart',
            [
                'items' => [
                    [
                        'sku' => $product['sku'],
                        'name' => $product['name'],
                        'quantity' => $initialQuantity,
                        'thumbnail_url' => $product['thumbnail_url'],
                        'description' => $product['description'],
                        'stock' => $product['stock'],
                        'subscription_interval_type' => $product['subscription_interval_type'],
                        'subscription_interval_count' => $product['subscription_interval_count'],
                        'price_before_discounts' => $product['price'],
                        'price_after_discounts' => $product['price'],
                        'requires_shipping' => false,
                    ]
                ],
                'discounts' => [],
                'shipping_address' => null,
                'billing_address' => [
                    'zip_or_postal_code' => null,
                    'street_line_two' => null,
                    'street_line_one' => null,
                    'last_name' => null,
                    'first_name' => null,
                    'region' => 'Alaska',
                    'country' => 'United States',
                    'city' => null,
                ],
                'number_of_payments' => 1,
                'locked' => false,
                'totals' => [
                    'shipping' => 0,
                    'tax' => 0,
                    'due' => $totalDue
                ],
                'payment_plan_options' => [
                    [
                        "value" => 1,
                        "label" => "1 payment of $184.44",
                    ],
                    [
                        "value" => 2,
                        "label" => "2 payments of $92.72 ($1.00 finance charge)",
                    ],
                    [
                        "value" => 5,
                        "label" => "5 payments of $37.09 ($1.00 finance charge)",
                    ]
                ],
            ]
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

        $country = 'United States';
        $region = 'Alaska';

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $cart = Cart::fromSession();

        $cart->setBillingAddress($billingAddress);

        $cart->toSession();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => 0,
        ]);

        $quantity = $this->faker->numberBetween(2, 10);

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
        ]);

        // assert the session has the empty cart structure & error message
        $response->assertSessionHas(
            'cart',
            [
                'items' => [],
                'discounts' => [],
                'shipping_address' => null,
                'billing_address' => [
                    'zip_or_postal_code' => null,
                    'street_line_two' => null,
                    'street_line_one' => null,
                    'last_name' => null,
                    'first_name' => null,
                    'region' => 'Alaska',
                    'country' => 'United States',
                    'city' => null,
                ],
                'number_of_payments' => 1,
                'locked' => false,
                'totals' => [
                    'shipping' => 0,
                    'tax' => 0,
                    'due' => 0
                ],
                'errors' => ['Product ' . $product['name'] . ' is currently out of stock, please check back later.'],
                'payment_plan_options' => [],
            ]
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertTrue(empty($cart->getItems()));
    }

    public function test_add_inexistent_product_to_cart()
    {
        $this->session->flush();

        $country = 'United States';
        $region = 'Alaska';

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $cart = Cart::fromSession();

        $cart->setBillingAddress($billingAddress);

        $cart->toSession();

        $randomSku = $this->faker->word;

        $response = $this->call('GET', '/add-to-cart', [
            'products' => [$randomSku => 10],
        ]);

        // assert the session has the empty cart structure & error message
        $response->assertSessionHas(
            'cart',
            [
                'items' => [],
                'discounts' => [],
                'shipping_address' => null,
                'billing_address' => [
                    'zip_or_postal_code' => null,
                    'street_line_two' => null,
                    'street_line_one' => null,
                    'last_name' => null,
                    'first_name' => null,
                    'region' => 'Alaska',
                    'country' => 'United States',
                    'city' => null,
                ],
                'number_of_payments' => 1,
                'locked' => false,
                'totals' => [
                    'shipping' => 0,
                    'tax' => 0,
                    'due' => 0
                ],
                'errors' => [
                    'No product with SKU ' . $randomSku . ' was found.',],
                'payment_plan_options' => [],
            ]
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertTrue(empty($cart->getItems()));
    }

    public function test_add_many_products_to_cart()
    {
        $country = 'United States';
        $region = 'Alaska';

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $cart = Cart::fromSession();

        $cart->setBillingAddress($billingAddress);

        $cart->toSession();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'is_physical' => false,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => $this->faker->randomElement(
                [
                    config('ecommerce.interval_type_daily'),
                    config('ecommerce.interval_type_monthly'),
                    config('ecommerce.interval_type_yearly'),
                ]
            ),
            'stock' => $this->faker->numberBetween(5, 100),
            'price' => 47.07,
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'is_physical' => false,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'stock' => $this->faker->numberBetween(5, 100),
            'price' => 100.92,
        ]);

        $productOneQuantity = 2;
        $productTwoQuantity = 2;

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [
                $productOne['sku'] => $productOneQuantity,
                $productTwo['sku'] => $productTwoQuantity,
            ],
        ]);

        $totalDue = $productOne['price'] * $productOneQuantity + $productTwo['price'] * $productTwoQuantity;

        // assert the session has the cart structure
        $response->assertSessionHas(
            'cart',
            [
                'items' => [
                    [
                        'sku' => $productOne['sku'],
                        'name' => $productOne['name'],
                        'quantity' => $productOneQuantity,
                        'thumbnail_url' => $productOne['thumbnail_url'],
                        'description' => $productOne['description'],
                        'stock' => $productOne['stock'],
                        'subscription_interval_type' => $productOne['subscription_interval_type'],
                        'subscription_interval_count' => $productOne['subscription_interval_count'],
                        'price_before_discounts' => $productOne['price'],
                        'price_after_discounts' => $productOne['price'],
                        'requires_shipping' => false,
                    ],
                    [
                        'sku' => $productTwo['sku'],
                        'name' => $productTwo['name'],
                        'quantity' => $productTwoQuantity,
                        'thumbnail_url' => $productTwo['thumbnail_url'],
                        'description' => $productTwo['description'],
                        'stock' => $productTwo['stock'],
                        'subscription_interval_type' => $productTwo['subscription_interval_type'],
                        'subscription_interval_count' => $productTwo['subscription_interval_count'],
                        'price_before_discounts' => $productTwo['price'],
                        'price_after_discounts' => $productTwo['price'],
                        'requires_shipping' => false,
                    ],
                ],
                'discounts' => [],
                'shipping_address' => null,
                'billing_address' => [
                    'zip_or_postal_code' => null,
                    'street_line_two' => null,
                    'street_line_one' => null,
                    'last_name' => null,
                    'first_name' => null,
                    'region' => 'Alaska',
                    'country' => 'United States',
                    'city' => null,
                ],
                'number_of_payments' => 1,
                'locked' => false,
                'totals' => [
                    'shipping' => 0,
                    'tax' => 0,
                    'due' => $totalDue
                ],
                'payment_plan_options' => [
                    [
                        "value" => 1,
                        "label" => "1 payment of $295.98",
                    ],
                    [
                        "value" => 2,
                        "label" => "2 payments of $148.49 ($1.00 finance charge)",
                    ],
                    [
                        "value" => 5,
                        "label" => "5 payments of $59.4 ($1.00 finance charge)",
                    ]
                ],
            ]
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

    public function test_add_to_cart_higher_amount_than_product_stock()
    {
        $this->session->flush();

        $country = 'United States';
        $region = 'Alaska';

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $cart = Cart::fromSession();

        $cart->setBillingAddress($billingAddress);

        $cart->toSession();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(1, 3),
        ]);

        $quantity = $this->faker->numberBetween(5, 100);

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
        ]);

        // assert the session has the empty cart structure & error message
        $response->assertSessionHas(
            'cart',
            [
                'items' => [],
                'discounts' => [],
                'shipping_address' => null,
                'billing_address' => [
                    'zip_or_postal_code' => null,
                    'street_line_two' => null,
                    'street_line_one' => null,
                    'last_name' => null,
                    'first_name' => null,
                    'region' => 'Alaska',
                    'country' => 'United States',
                    'city' => null,
                ],
                'number_of_payments' => 1,
                'locked' => false,
                'totals' => [
                    'shipping' => 0,
                    'tax' => 0,
                    'due' => 0
                ],
                'errors' => ['Product ' . $product['name'] . ' is currently out of stock, please check back later.'],
                'payment_plan_options' => [],
            ]
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertTrue(empty($cart->getItems()));
    }

    public function test_add_products_available_and_not_available_to_cart()
    {
        $country = 'United States';
        $region = 'Alaska';

        $billingAddress = new Address();
        $billingAddress->setCountry($country);
        $billingAddress->setRegion($region);

        $cart = Cart::fromSession();

        $cart->setBillingAddress($billingAddress);

        $cart->toSession();

        $productOne = $this->fakeProduct([
            'active' => 1,
            'is_physical' => false,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => $this->faker->randomElement(
                [
                    config('ecommerce.interval_type_daily'),
                    config('ecommerce.interval_type_monthly'),
                    config('ecommerce.interval_type_yearly'),
                ]
            ),
            'subscription_interval_count' => $this->faker->numberBetween(0, 12),
            'stock' => $this->faker->numberBetween(5, 100),
            'price' => 22.82,
            'weight' => 0,
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'is_physical' => false,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'stock' => $this->faker->numberBetween(5, 100),
            'price' => 1.02,
            'weight' => 0,
        ]);

        $randomSku1 = $this->faker->word . 'sku1';
        $randomSku2 = $this->faker->word . 'sku2';

        $productOneQuantity = 3;
        $productTwoQuantity = 4;

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [
                $productOne['sku'] => $productOneQuantity,
                $randomSku1 => 2,
                $productTwo['sku'] => $productTwoQuantity,
                $randomSku2 => 2,
            ],
        ]);

        $totalDue = $productOne['price'] * $productOneQuantity + $productTwo['price'] * $productTwoQuantity;

        // assert the session has the cart structure
        $response->assertSessionHas(
            'cart',
            [
                'items' => [
                    [
                        'sku' => $productOne['sku'],
                        'name' => $productOne['name'],
                        'quantity' => $productOneQuantity,
                        'thumbnail_url' => $productOne['thumbnail_url'],
                        'description' => $productOne['description'],
                        'stock' => $productOne['stock'],
                        'subscription_interval_type' => $productOne['subscription_interval_type'],
                        'subscription_interval_count' => $productOne['subscription_interval_count'],
                        'price_before_discounts' => $productOne['price'],
                        'price_after_discounts' => $productOne['price'],
                        'requires_shipping' => false,
                    ],
                    [
                        'sku' => $productTwo['sku'],
                        'name' => $productTwo['name'],
                        'quantity' => $productTwoQuantity,
                        'thumbnail_url' => $productTwo['thumbnail_url'],
                        'description' => $productTwo['description'],
                        'stock' => $productTwo['stock'],
                        'subscription_interval_type' => $productTwo['subscription_interval_type'],
                        'subscription_interval_count' => $productTwo['subscription_interval_count'],
                        'price_before_discounts' => $productTwo['price'],
                        'price_after_discounts' => $productTwo['price'],
                        'requires_shipping' => false,
                    ],
                ],
                'discounts' => [],
                'shipping_address' => null,
                'billing_address' => [
                    'zip_or_postal_code' => null,
                    'street_line_two' => null,
                    'street_line_one' => null,
                    'last_name' => null,
                    'first_name' => null,
                    'region' => 'Alaska',
                    'country' => 'United States',
                    'city' => null,
                ],
                'number_of_payments' => 1,
                'locked' => false,
                'totals' => [
                    'shipping' => 0,
                    'tax' => 0,
                    'due' => $totalDue
                ],
                'errors' => [
                    'No product with SKU ' . $randomSku1 . ' was found.',
                    'No product with SKU ' . $randomSku2 .  ' was found.',
                ],
                'payment_plan_options' => [
                    [
                        "value" => 1,
                        "label" => "1 payment of $72.54",
                    ],
                    [
                        "value" => 2,
                        "label" => "2 payments of $36.77 ($1.00 finance charge)",
                    ],
                    [
                        "value" => 5,
                        "label" => "5 payments of $14.71 ($1.00 finance charge)",
                    ]
                ],
            ]
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
