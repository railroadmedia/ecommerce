<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Services\CartService;
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
        $recommendedProducts = $this->addRecommendedProducts();

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

        $expected = [
            'items' => [
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
                    'requires_shipping' => false,
                    'is_digital' => !$product['is_physical'],
                ]
            ],
            'recommendedProducts' => [
                $recommendedProductOne,
                $recommendedProductTwo,
                $recommendedProductThree,
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
                'due' => $totalDue,
                'shipping_before_override' => 0,
                'product_taxes' => 0,
                'shipping_taxes' => 0,
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
            'bonuses' => [],
        ];

        // assert the session has the cart structure
        $response->assertSessionHas(
            'cart',
            $expected
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
        $recommendedProducts = $this->addRecommendedProducts();

        // this has been de-activated, people can still buy products with stock=0
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
            'is_physical' => false,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'weight' => 0,
            'stock' => 0,
            'price' => 92.22,
        ]);

        $quantity = 2;

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
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

        // assert the session has the empty cart structure & error message
        $response->assertSessionHas(
            'cart',
            [
                'items' => [
                    [
                        'sku' => $product['sku'],
                        'name' => $product['name'],
                        'quantity' => $quantity,
                        'thumbnail_url' => $product['thumbnail_url'],
                        'sales_page_url' => $product['sales_page_url'],
                        'description' => $product['description'],
                        'stock' => $product['stock'],
                        'subscription_interval_type' => $product['subscription_interval_type'],
                        'subscription_interval_count' => $product['subscription_interval_count'],
                        'subscription_renewal_price' => null,
                        'price_before_discounts' => $product['price'] * $quantity,
                        'price_after_discounts' => $product['price'] * $quantity,
                        'requires_shipping' => false,
                        'is_digital' => !$product['is_physical'],
                    ]
                ],
                'recommendedProducts' => [
                    $recommendedProductOne,
                    $recommendedProductTwo,
                    $recommendedProductThree,
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
                    'due' => $product['price'] * $quantity,
                    'shipping_before_override' => 0,
                    'product_taxes' => 0,
                    'shipping_taxes' => 0,
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
                'bonuses' => [],
            ]
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertFalse(empty($cart->getItems()));
    }

    public function test_add_inexistent_product_to_cart()
    {
        $recommendedProducts = $this->addRecommendedProducts();

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

        // assert the session has the empty cart structure & error message
        $response->assertSessionHas(
            'cart',
            [
                'items' => [],
                'recommendedProducts' => [
                    $recommendedProductOne,
                    $recommendedProductTwo,
                    $recommendedProductThree,
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
                    'due' => 0,
                    'shipping_before_override' => 0,
                    'product_taxes' => 0,
                    'shipping_taxes' => 0,
                ],
                'errors' => [
                    'No product with SKU ' . $randomSku . ' was found.',],
                'payment_plan_options' => [],
                'bonuses' => [],
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
        $recommendedProducts = $this->addRecommendedProducts();

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
                        'sales_page_url' => $productOne['sales_page_url'],
                        'description' => $productOne['description'],
                        'stock' => $productOne['stock'],
                        'subscription_interval_type' => $productOne['subscription_interval_type'],
                        'subscription_interval_count' => $productOne['subscription_interval_count'],
                        'subscription_renewal_price' => $productOne['price'] * $productOneQuantity,
                        'price_before_discounts' => $productOne['price'] * $productOneQuantity,
                        'price_after_discounts' => $productOne['price'] * $productOneQuantity,
                        'requires_shipping' => false,
                        'is_digital' => !$productOne['is_physical'],
                    ],
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
                        'requires_shipping' => false,
                        'is_digital' => !$productTwo['is_physical'],
                    ],
                ],
                'recommendedProducts' => [
                    $recommendedProductOne,
                    $recommendedProductTwo,
                    $recommendedProductThree,
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
                    'due' => $totalDue,
                    'shipping_before_override' => 0,
                    'product_taxes' => 0,
                    'shipping_taxes' => 0,
                ],
                'payment_plan_options' => [],
                'bonuses' => [],
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
        $recommendedProducts = $this->addRecommendedProducts();

        // this has been de-activated, people can still buy products with stock=0

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
            'is_physical' => false,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'weight' => 0,
            'stock' => 1,
            'price' => 92.22,
        ]);

        $quantity = 2;

        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [$product['sku'] => $quantity],
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

        // assert the session has the empty cart structure & error message
        $response->assertSessionHas(
            'cart',
            [
                'items' => [
                    [
                        'sku' => $product['sku'],
                        'name' => $product['name'],
                        'quantity' => $quantity,
                        'thumbnail_url' => $product['thumbnail_url'],
                        'sales_page_url' => $product['sales_page_url'],
                        'description' => $product['description'],
                        'stock' => $product['stock'],
                        'subscription_interval_type' => $product['subscription_interval_type'],
                        'subscription_interval_count' => $product['subscription_interval_count'],
                        'subscription_renewal_price' => null,
                        'price_before_discounts' => $product['price'] * $quantity,
                        'price_after_discounts' => $product['price'] * $quantity,
                        'requires_shipping' => false,
                        'is_digital' => !$product['is_physical'],
                    ]
                ],
                'recommendedProducts' => [
                    $recommendedProductOne,
                    $recommendedProductTwo,
                    $recommendedProductThree,
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
                    'due' => $product['price'] * $quantity,
                    'shipping_before_override' => 0,
                    'product_taxes' => 0,
                    'shipping_taxes' => 0,
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
                'bonuses' => [],
            ]
        );

        // backend asserts
        $cart = Cart::fromSession();

        // assert cart items collection is empty
        $this->assertTrue(is_array($cart->getItems()));

        $this->assertFalse(empty($cart->getItems()));
    }

    public function test_add_products_available_and_not_available_to_cart()
    {
        $recommendedProducts = $this->addRecommendedProducts();

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

        $productOneQuantity = 1;
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
                    ],
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
                        'requires_shipping' => false,
                        'is_digital' => !$productTwo['is_physical'],
                    ],
                ],
                'recommendedProducts' => [
                    $recommendedProductOne,
                    $recommendedProductTwo,
                    $recommendedProductThree,
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
                    'due' => $totalDue,
                    'shipping_before_override' => 0,
                    'product_taxes' => 0,
                    'shipping_taxes' => 0,
                ],
                'errors' => [
                    'No product with SKU ' . $randomSku1 . ' was found.',
                    'No product with SKU ' . $randomSku2 .  ' was found.',
                ],
                'payment_plan_options' => [],
                'bonuses' => [],
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

    public function test_reset_number_of_payments_with_locked_cart()
    {
        $recommendedProducts = $this->addRecommendedProducts();

        $this->session->flush();

        // create three test products
        $productOne = $this->fakeProduct([
            'sku' => 'a' . $this->faker->word,
            'active' => 1,
            'is_physical' => false,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'stock' => $this->faker->numberBetween(5, 100),
            'price' => 290.92,
        ]);

        $productTwo = $this->fakeProduct([
            'sku' => 'b' . $this->faker->word,
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
            'price' => 150.07,
        ]);

        $productThree = $this->fakeProduct([
            'sku' => 'c' . $this->faker->word,
            'active' => 1,
            'is_physical' => false,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'stock' => $this->faker->numberBetween(5, 100),
            'price' => 26.68,
        ]);

        $cartService = $this->app->make(CartService::class);

        $productOneQuantity = $this->faker->numberBetween(1, 5);

        // add product one to cart
        $cartService->addToCart(
            $productOne['sku'],
            $productOneQuantity,
            false,
            ''
        );

        $numberOfPayments = $this->getPaymentPlanOption();

        // update order number of payments to some random value > 1
        $response = $this->call(
            'PUT',
            '/json/update-number-of-payments/' . $numberOfPayments
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert response cart number of payments
        $this->assertEquals(
            $numberOfPayments,
            $decodedResponse['meta']['cart']['number_of_payments']
        );

        $productTwoQuantity = 1;
        $productThreeQuantity = 1;

        // add product two and three to cart, with locked option
        $response = $this->call('GET', '/add-to-cart/', [
            'products' => [
                $productTwo['sku'] => $productTwoQuantity,
                $productThree['sku'] => $productThreeQuantity,
            ],
            'locked' => true,
        ]);

        $totalDue = $productTwo['price'] * $productTwoQuantity + $productThree['price'] * $productThreeQuantity;

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

        // assert the session has the expected cart structure with number of payments = 1 and expected totalDue
        $response->assertSessionHas(
            'cart',
            [
                'items' => [
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
                        'subscription_renewal_price' => $productTwo['price'] * $productTwoQuantity,
                        'price_before_discounts' => $productTwo['price'] * $productTwoQuantity,
                        'price_after_discounts' => $productTwo['price'] * $productTwoQuantity,
                        'requires_shipping' => false,
                        'is_digital' => !$productTwo['is_physical'],
                    ],
                    [
                        'sku' => $productThree['sku'],
                        'name' => $productThree['name'],
                        'quantity' => $productThreeQuantity,
                        'thumbnail_url' => $productThree['thumbnail_url'],
                        'sales_page_url' => $productThree['sales_page_url'],
                        'description' => $productThree['description'],
                        'stock' => $productThree['stock'],
                        'subscription_interval_type' => $productThree['subscription_interval_type'],
                        'subscription_interval_count' => $productThree['subscription_interval_count'],
                        'subscription_renewal_price' => null,
                        'price_before_discounts' => $productThree['price'] * $productThreeQuantity,
                        'price_after_discounts' => $productThree['price'] * $productThreeQuantity,
                        'requires_shipping' => false,
                        'is_digital' => !$productThree['is_physical'],
                    ],
                ],
                'recommendedProducts' => [
                    $recommendedProductOne,
                    $recommendedProductTwo,
                    $recommendedProductThree,
                ],
                'discounts' => [],
                'shipping_address' => null,
                'billing_address' => null,
                'number_of_payments' => 1, // number of payments has been reset
                'locked' => true,
                'totals' => [
                    'shipping' => 0.0,
                    'tax' => 0.0,
                    'due' => $totalDue, // total due as expected
                    'shipping_before_override' => 0.0,
                    'product_taxes' => 0.0,
                    'shipping_taxes' => 0.0,
                ],
                'payment_plan_options' => [],
                'bonuses' => [],
            ]
        );
    }
}
