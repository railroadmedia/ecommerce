<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Doctrine\ORM\EntityManager;
use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShoppingCartControllerTest extends EcommerceTestCase
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

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $initialQuantity],
            ]
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the session has addedProducts key
        $response->assertSessionHas('addedProducts');

        $response->assertSessionHas('cartNumberOfItems', 1);

        $cartService = $this->app->make(CartService::class);

        $cartItems = $cartService->getCart()->getItems();

        // assert cart items count
        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(1, count($cartItems));

        // assert cart item
        $cartItemOne = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($initialQuantity, $cartItemOne->getQuantity());

        $this->assertEquals($product['price'], $cartItemOne->getPrice());

        // assert the product was added to the cart
        $cartItemProduct = $cartItemOne->getProduct();

        $this->assertNotNull($cartItemProduct);
        $this->assertEquals(Product::class, get_class($cartItemProduct));
        $this->assertEquals($product['id'], $cartItemProduct->getId());
    }

    public function test_add_product_with_stock_empty_to_cart()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
        ]);

        $quantity = $this->faker->numberBetween(1, 1000);
        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $quantity],
            ]
        );

        // assert the session has the messages set on false
        $response->assertSessionHas('success', false);

        // assert the items was not added to cart
        $response->assertSessionHas('addedProducts', []);
        $response->assertSessionHas('cartNumberOfItems', 0);

        $em = $this->app->make(EntityManager::class);

        $productEntity = $em->getRepository(Product::class)
                                ->find($product['id']);

        // assert the session has the error message
        $response->assertSessionHas(
            'notAvailableProducts',
            [
                [
                    'message' =>
                        'Product with SKU:' .
                        $product['sku'] .
                        ' could not be added to cart. The product stock(' .
                        $product['stock'] .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantity .
                        ')',
                    'product' => $productEntity
                ]
            ]
        );
    }

    public function test_add_inexistent_product_to_cart()
    {
        $randomSku = $this->faker->word;
        $response = $this->call(
            'GET',
            '/add-to-cart',
            [
                'products' => [$randomSku => 10],
            ]
        );

        // assert the session has the success message set to false
        $response->assertSessionHas('success', false);

        //assert the item was not added to the cart
        $response->assertSessionHas('addedProducts', []);
        $response->assertSessionHas('cartNumberOfItems', 0);

        // assert the session has the error message
        $response->assertSessionHas(
            'notAvailableProducts',
            [
                [
                    'message' => 'Product with SKU:' . $randomSku . ' could not be added to cart.',
                    'product' => null
                ]
            ]
        );
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

        $productOneQuantity = 2;
        $productTwoQuantity = 2;

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $productOne['sku'] => $productOneQuantity,
                    $productTwo['sku'] => $productTwoQuantity,
                ],
            ]
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        //assert the items were added to the cart
        $response->assertSessionHas('addedProducts');
        $response->assertSessionHas('cartNumberOfItems', 2);
        $response->assertSessionHas('notAvailableProducts', []);

        $cartService = $this->app->make(CartService::class);

        $cartItems = $cartService->getCart()->getItems();

        // assert cart items count
        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(2, count($cartItems));

        // assert cart item one
        $cartItemOne = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($productOneQuantity, $cartItemOne->getQuantity());

        $this->assertEquals($productOne['price'], $cartItemOne->getPrice());

        // assert the product one was added to the cart
        $cartItemOneProduct = $cartItemOne->getProduct();

        $this->assertNotNull($cartItemOneProduct);
        $this->assertEquals(Product::class, get_class($cartItemOneProduct));
        $this->assertEquals($productOne['id'], $cartItemOneProduct->getId());

        // assert cart item two
        $cartItemTwo = $cartItems[1];

        $this->assertEquals(CartItem::class, get_class($cartItemTwo));

        $this->assertEquals($productTwoQuantity, $cartItemTwo->getQuantity());

        $this->assertEquals($productTwo['price'], $cartItemTwo->getPrice());

        // assert the product two was added to the cart
        $cartItemTwoProduct = $cartItemTwo->getProduct();

        $this->assertNotNull($cartItemTwoProduct);
        $this->assertEquals(Product::class, get_class($cartItemTwoProduct));
        $this->assertEquals($productTwo['id'], $cartItemTwoProduct->getId());
    }

    public function test_add_to_cart_higher_amount_than_product_stock()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(1, 3),
        ]);

        $quantity = $this->faker->numberBetween(5, 100);

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $quantity],
            ]
        );

        // assert the session has the success message set to false
        $response->assertSessionHas('success', false);

        // assert the product was not added into the cart
        $response->assertSessionHas('addedProducts', []);
        $response->assertSessionHas('cartNumberOfItems', 0);

        $em = $this->app->make(EntityManager::class);

        $productEntity = $em->getRepository(Product::class)
                                ->find($product['id']);

        // assert the session has the error message
        $response->assertSessionHas(
            'notAvailableProducts',
            [
                [
                    'message' => 'Product with SKU:' .
                        $product['sku'] .
                        ' could not be added to cart. The product stock(' .
                        $product['stock'] .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantity .
                        ')',
                    'product' => $productEntity
                ]
            ]
        );
    }

    public function test_add_products_available_and_not_available_to_cart()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $randomSku1 = $this->faker->word . 'sku1';
        $randomSku2 = $this->faker->word . 'sku2';

        $productOneQuantity = $this->faker->numberBetween(1, 5);
        $productTwoQuantity = $this->faker->numberBetween(1, 5);

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $productOne['sku'] => $productOneQuantity,
                    $randomSku1 => 2,
                    $productTwo['sku'] => $productTwoQuantity,
                    $randomSku2 => 2,
                ],
            ]
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        //assert valid items was added into the cart
        $response->assertSessionHas('addedProducts');
        $response->assertSessionHas('cartNumberOfItems', 2);

        // assert the session has the error messages for the invalid products
        $response->assertSessionHas(
            'notAvailableProducts',
            [
                [
                    'message' => 'Product with SKU:' . $randomSku1 . ' could not be added to cart.',
                    'product' => null,
                ],
                [
                    'message' => 'Product with SKU:' . $randomSku2 . ' could not be added to cart.',
                    'product' => null,
                ]
            ]
        );

        $cartService = $this->app->make(CartService::class);

        $cartItems = $cartService->getCart()->getItems();

        // assert cart items count
        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(2, count($cartItems));

        // assert cart item one
        $cartItemOne = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($productOneQuantity, $cartItemOne->getQuantity());

        $this->assertEquals($productOne['price'], $cartItemOne->getPrice());

        // assert the product one was added to the cart
        $cartItemOneProduct = $cartItemOne->getProduct();

        $this->assertNotNull($cartItemOneProduct);
        $this->assertEquals(Product::class, get_class($cartItemOneProduct));
        $this->assertEquals($productOne['id'], $cartItemOneProduct->getId());

        // assert cart item two
        $cartItemTwo = $cartItems[1];

        $this->assertEquals(CartItem::class, get_class($cartItemTwo));

        $this->assertEquals($productTwoQuantity, $cartItemTwo->getQuantity());

        $this->assertEquals($productTwo['price'], $cartItemTwo->getPrice());

        // assert the product two was added to the cart
        $cartItemTwoProduct = $cartItemTwo->getProduct();

        $this->assertNotNull($cartItemTwoProduct);
        $this->assertEquals(Product::class, get_class($cartItemTwoProduct));
        $this->assertEquals($productTwo['id'], $cartItemTwoProduct->getId());
    }

    public function test_remove_product_from_cart()
    {
        $this->session->flush();

        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
        ]);

        $productQuantity = 1;

        $cartService = $this->app->make(CartService::class);

        $cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $response = $this->call('PUT', '/remove-from-cart/' . $product['id']);

        // assert cart data response
        $this->assertEquals(
            [
                'data' => [],
                'meta' => [
                    'tax' => 0,
                    'total' => 0
                ]
            ],
            $response->decodeResponseJson()
        );

        // assert the session has the success message and the product was removed from the cart
        $response->assertSessionMissing('addedProducts');

        // assert response status code
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_remove_product_from_cart_cart_not_empty()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productThree = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $cartService = $this->app->make(CartService::class);
        $cartAddressService = $this->app->make(CartAddressService::class);

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($country)
            ->setState($state)
            ->setZipOrPostalCode($zip);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $productOneQuantity = $this->faker->numberBetween(1, 3);

        $cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $productOne['subscription_interval_type'],
            $productOne['subscription_interval_count'],
            [
                'product-id' => $productOne['id'],
            ]
        );

        $productTwoQuantity = $this->faker->numberBetween(1, 3);

        $cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $productTwo['subscription_interval_type'],
            $productTwo['subscription_interval_count'],
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $productThreeQuantity = $this->faker->numberBetween(1, 3);

        $cartService->addCartItem(
            $productThree['name'],
            $productThree['description'],
            $productThreeQuantity,
            $productThree['price'],
            $productThree['is_physical'],
            $productThree['is_physical'],
            $productThree['subscription_interval_type'],
            $productThree['subscription_interval_count'],
            [
                'product-id' => $productThree['id'],
            ]
        );

        $response = $this->call(
            'PUT',
            '/remove-from-cart/' . $productOne['id']
        );

        // assert response status code
        $this->assertEquals(200, $response->getStatusCode());

        $expectedCartItemTwoPrice = round($productTwo['price'] * $productTwoQuantity, 2);
        $expectedCartItemThreePrice = round($productThree['price'] * $productThreeQuantity, 2);

        $expectedCartItemsTotalPrice = $expectedCartItemTwoPrice + $expectedCartItemThreePrice;

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = round($expectedCartItemsTotalPrice * $taxRate, 2);

        $expectedTotal = round($expectedCartItemsTotalPrice + $expectedTaxes, 2);

        $paymentPlanService = $this->app->make(PaymentPlanService::class);

        $isPaymentPlanEligible = $paymentPlanService->isPaymentPlanEligible();
        $paymentPlanPricing = $paymentPlanService->getPaymentPlanPricingForCartItems();

        // assert cart data response
        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'type' => 'cartItem',
                        'attributes' => [
                            'name' => $productTwo['name'],
                            'description' => $productTwo['description'],
                            'quantity' => $productTwoQuantity,
                            'totalPrice' => $productTwo['price'] * $productTwoQuantity,
                            'requiresShippingAddress' => $productTwo['is_physical'],
                            'requiresBillingAddress' => $productTwo['is_physical'],
                            'subscriptionIntervalType' => $productTwo['subscription_interval_type'],
                            'subscriptionIntervalCount' => $productTwo['subscription_interval_count'],
                            'discountedPrice' => null,
                            'options' => ['product-id' => $productTwo['id']]
                        ]
                    ],
                    [
                        'type' => 'cartItem',
                        'attributes' => [
                            'name' => $productThree['name'],
                            'description' => $productThree['description'],
                            'quantity' => $productThreeQuantity,
                            'totalPrice' => $productThree['price'] * $productThreeQuantity,
                            'requiresShippingAddress' => $productThree['is_physical'],
                            'requiresBillingAddress' => $productThree['is_physical'],
                            'subscriptionIntervalType' => $productThree['subscription_interval_type'],
                            'subscriptionIntervalCount' => $productThree['subscription_interval_count'],
                            'discountedPrice' => null,
                            'options' => ['product-id' => $productThree['id']]
                        ]
                    ]
                ],
                'meta' => [
                    'tax' => $expectedTaxes,
                    'total' => $expectedTotal,
                    'isPaymentPlanEligible' => $isPaymentPlanEligible,
                    'paymentPlanPricing' => $paymentPlanPricing
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_update_cart_item_quantity()
    {
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
        ]);

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $cartAddressService = $this->app->make(CartAddressService::class);

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($country)
            ->setState($state)
            ->setZipOrPostalCode($zip);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $initialQuantity = 2;

        $cartService = $this->app->make(CartService::class);

        $cartService->addCartItem(
            $product['name'],
            $product['description'],
            $initialQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $newQuantity = $this->faker->numberBetween(6, 10);

        $expectedCartItemPrice = round($product['price'] * $newQuantity, 2);

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = round($expectedCartItemPrice * $taxRate, 2);

        $expectedTotal = round($expectedCartItemPrice + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/update-product-quantity/' . $product['id'] . '/' . $newQuantity
        );

        $paymentPlanService = $this->app->make(PaymentPlanService::class);

        $isPaymentPlanEligible = $paymentPlanService->isPaymentPlanEligible();
        $paymentPlanPricing = $paymentPlanService->getPaymentPlanPricingForCartItems();

        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'type' => 'cartItem',
                        'attributes' => [
                            'name' => $product['name'],
                            'description' => $product['description'],
                            'quantity' => $newQuantity,
                            'totalPrice' => $product['price'] * $newQuantity,
                            'requiresShippingAddress' => $product['is_physical'],
                            'requiresBillingAddress' => $product['is_physical'],
                            'subscriptionIntervalType' => $product['subscription_interval_type'],
                            'subscriptionIntervalCount' => $product['subscription_interval_count'],
                            'discountedPrice' => null,
                            'options' => ['product-id' => $product['id']]
                        ]
                    ],
                ],
                'meta' => [
                    'tax' => $expectedTaxes,
                    'total' => $expectedTotal,
                    'isPaymentPlanEligible' => $isPaymentPlanEligible,
                    'paymentPlanPricing' => $paymentPlanPricing
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_update_cart_item_quantity_insufficient_stock()
    {
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(2, 5),
        ]);

        $country = 'Canada';
        $state = $this->faker->word;
        $zip = $this->faker->postcode;

        $cartAddressService = $this->app->make(CartAddressService::class);

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($country)
            ->setState($state)
            ->setZipOrPostalCode($zip);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $initialQuantity = $this->faker->numberBetween(1, 2);

        $cartService = $this->app->make(CartService::class);

        $cartService->addCartItem(
            $product['name'],
            $product['description'],
            $initialQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $newQuantity = $this->faker->numberBetween(6, 10);

        $expectedCartItemPrice = round($product['price'] * $initialQuantity, 2);

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = round($expectedCartItemPrice * $taxRate, 2);

        $expectedTotal = round($expectedCartItemPrice + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/update-product-quantity/' . $product['id'] . '/' . $newQuantity
        );

        // assert response
        $this->assertEquals(200, $response->getStatusCode());

        $paymentPlanService = $this->app->make(PaymentPlanService::class);

        $isPaymentPlanEligible = $paymentPlanService->isPaymentPlanEligible();
        $paymentPlanPricing = $paymentPlanService->getPaymentPlanPricingForCartItems();

        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'type' => 'cartItem',
                        'attributes' => [
                            'name' => $product['name'],
                            'description' => $product['description'],
                            'quantity' => $initialQuantity,
                            'totalPrice' => $product['price'] * $initialQuantity,
                            'requiresShippingAddress' => $product['is_physical'],
                            'requiresBillingAddress' => $product['is_physical'],
                            'subscriptionIntervalType' => $product['subscription_interval_type'],
                            'subscriptionIntervalCount' => $product['subscription_interval_count'],
                            'discountedPrice' => null,
                            'options' => ['product-id' => $product['id']]
                        ]
                    ],
                ],
                'meta' => [
                    'tax' => $expectedTaxes,
                    'total' => $expectedTotal,
                    'isPaymentPlanEligible' => $isPaymentPlanEligible,
                    'paymentPlanPricing' => $paymentPlanPricing
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_redirect_to_shop_with_added_product_data()
    {
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(15, 100),
        ]);

        $quantity = 2;

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $quantity],
                'redirect' => '/shop',
            ]
        );

        //assert redirect was done
        $response->assertRedirect('/shop');

        //assert product info exists on session
        $response->assertSessionHas('success', true);
        $response->assertSessionHas('addedProducts');
        $response->assertSessionHas('cartNumberOfItems', 1);
        $response->assertSessionHas('cartSubTotal');

        $cartService = $this->app->make(CartService::class);

        $cartItems = $cartService->getCart()->getItems();

        // assert cart items count
        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(1, count($cartItems));

        // assert cart item
        $cartItemOne = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($quantity, $cartItemOne->getQuantity());

        $this->assertEquals($product['price'], $cartItemOne->getPrice());

        // assert the product was added to the cart
        $cartItemProduct = $cartItemOne->getProduct();

        $this->assertNotNull($cartItemProduct);
        $this->assertEquals(Product::class, get_class($cartItemProduct));
        $this->assertEquals($product['id'], $cartItemProduct->getId());
    }

    public function test_redirect_checkout()
    {
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(1, 100),
        ]);

        $quantity = 2;

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [$product['sku'] => $quantity],
            ],
            [],
            [],
            ['HTTP_REFERER' => '/checkout']
        );

        // assert user redirected to previous page
        $response->assertRedirect('/checkout');

        // assert product info exists on session
        $response->assertSessionHas('success', true);
        $response->assertSessionHas('addedProducts');
        $response->assertSessionHas('cartNumberOfItems', 1);
        $response->assertSessionHas('cartSubTotal');
    }

    public function test_promo_code()
    {
        $product = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(2, 5),
        ]);

        $quantity = $this->faker->numberBetween(1, 2);

        $promoCode = $this->faker->word;

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $product['sku'] => $quantity,
                ],
                'promo-code' => $promoCode,
            ]
        );

        $response->assertSessionHas('promo-code', $promoCode);

        $cartService = $this->app->make(CartService::class);

        $cartItems = $cartService->getCart()->getItems();

        // assert cart promo code
        $this->assertEquals($promoCode, $cartService->getPromoCode());

        // assert cart items count
        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(1, count($cartItems));

        // assert cart item
        $cartItemOne = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($quantity, $cartItemOne->getQuantity());

        $this->assertEquals($product['price'], $cartItemOne->getPrice());

        // assert the product was added to the cart
        $cartItemProduct = $cartItemOne->getProduct();

        $this->assertNotNull($cartItemProduct);
        $this->assertEquals(Product::class, get_class($cartItemProduct));
        $this->assertEquals($product['id'], $cartItemProduct->getId());
    }

    public function test_lock_cart()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productOneQuantity = 2;

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $productOne['sku'] => $productOneQuantity,
                ],
                'locked' => true
            ]
        );

        $productTwoQuantity = 10;

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $productTwo['sku'] => $productTwoQuantity,
                ]
            ]
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the number of items contain only the products added to cart
        $response->assertSessionHas('cartNumberOfItems', 1);

        // assert that the cart was cleared and only the second product was added to the cart
        $response->assertSessionHas('addedProducts');

        $cartService = $this->app->make(CartService::class);

        $cartItems = $cartService->getCart()->getItems();

        // assert cart items count
        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(1, count($cartItems));

        // assert cart item
        $cartItem = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItem));

        $this->assertEquals($productTwoQuantity, $cartItem->getQuantity());

        $this->assertEquals($productTwo['price'], $cartItem->getPrice());

        // assert the product was added to the cart
        $cartItemProduct = $cartItem->getProduct();

        $this->assertNotNull($cartItemProduct);
        $this->assertEquals(Product::class, get_class($cartItemProduct));
        $this->assertEquals($productTwo['id'], $cartItemProduct->getId());
    }

    public function test_multiple_add_to_cart()
    {
        $productOne = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productTwo = $this->fakeProduct([
            'active' => 1,
            'stock' => $this->faker->numberBetween(5, 100),
        ]);

        $productOneQuantity = 2;

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $productOne['sku'] => $productOneQuantity,
                ],
            ]
        );

        $productTwoQuantity = 10;

        $response = $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $productTwo['sku'] => $productTwoQuantity,
                ],
            ]
        );

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        $em = $this->app->make(EntityManager::class);

        $productEntity = $em->getRepository(Product::class)
                                ->find($productTwo['id']);

        //assert the items were added to the cart
        $response->assertSessionHas('addedProducts', [$productEntity]);
        $response->assertSessionHas('cartNumberOfItems', 2);
        $response->assertSessionHas('notAvailableProducts', []);

        $cartService = $this->app->make(CartService::class);

        $cartItems = $cartService->getCart()->getItems();

        // assert cart items count
        $this->assertTrue(is_array($cartItems));

        $this->assertEquals(2, count($cartItems));

        // assert cart item one
        $cartItemOne = $cartItems[0];

        $this->assertEquals(CartItem::class, get_class($cartItemOne));

        $this->assertEquals($productOneQuantity, $cartItemOne->getQuantity());

        $this->assertEquals($productOne['price'], $cartItemOne->getPrice());

        // assert the product one was added to the cart
        $cartItemOneProduct = $cartItemOne->getProduct();

        $this->assertNotNull($cartItemOneProduct);
        $this->assertEquals(Product::class, get_class($cartItemOneProduct));
        $this->assertEquals($productOne['id'], $cartItemOneProduct->getId());

        // assert cart item two
        $cartItemTwo = $cartItems[1];

        $this->assertEquals(CartItem::class, get_class($cartItemTwo));

        $this->assertEquals($productTwoQuantity, $cartItemTwo->getQuantity());

        $this->assertEquals($productTwo['price'], $cartItemTwo->getPrice());

        // assert the product two was added to the cart
        $cartItemTwoProduct = $cartItemTwo->getProduct();

        $this->assertNotNull($cartItemTwoProduct);
        $this->assertEquals(Product::class, get_class($cartItemTwoProduct));
        $this->assertEquals($productTwo['id'], $cartItemTwoProduct->getId());
    }
}
