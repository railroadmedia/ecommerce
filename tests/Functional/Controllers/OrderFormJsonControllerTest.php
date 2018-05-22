<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\CartFactory;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\ProductFactory;
use Railroad\Ecommerce\Factories\ShippingCostsFactory;
use Railroad\Ecommerce\Factories\ShippingOptionFactory;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderFormJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ShippingOptionRepository
     */
    protected $shippingOptionRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ShippingCostsRepository
     */
    protected $shippingCostsRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentGatewayRepository
     */
    protected $paymentGatewayRepository;

    /**
     * @var \Railroad\Ecommerce\Factories\CartFactory
     */
    protected $cartFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->productRepository        = $this->app->make(ProductRepository::class);
        $this->shippingOptionRepository = $this->app->make(ShippingOptionRepository::class);
        $this->shippingCostsRepository  = $this->app->make(ShippingCostsRepository::class);
        $this->paymentGatewayRepository = $this->app->make(PaymentGatewayRepository::class);
        $this->cartFactory              = $this->app->make(CartFactory::class);
    }

    public function testIndex()
    {
        $product1 = $this->productRepository->create($this->faker->product([
            'price'       => 10,
            'type'        => ProductService::TYPE_PRODUCT,
            'active'      => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight'      => 2,
        ]));

        $product2 = $this->productRepository->create($this->faker->product([
            'price'       => 5,
            'type'        => ProductService::TYPE_PRODUCT,
            'active'      => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight'      => 1,
        ]));

        $shippingOption  = $this->shippingOptionRepository->create($this->faker->shippingOption([
            'country'  => 'Canada',
            'active'   => 1,
            'priority' => 1
        ]));
        $shippingOption2 = $this->shippingOptionRepository->create($this->faker->shippingOption([
            'country'  => '*',
            'active'   => 1,
            'priority' => 0
        ]));

        $this->shippingCostsRepository->create($this->faker->shippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min'                => 0,
            'max'                => 1000,
            'price'              => 520
        ]));

        $this->shippingCostsRepository->create($this->faker->shippingCost([
            'shipping_option_id' => $shippingOption2['id'],
            'min'                => 0,
            'max'                => 1,
            'price'              => 13.50
        ]));
        $this->shippingCostsRepository->create($this->faker->shippingCost([
            'shipping_option_id' => $shippingOption2['id'],
            'min'                => 1,
            'max'                =>2,
            'price'              => 19
        ]));
        $this->shippingCostsRepository->create($this->faker->shippingCost([
            'shipping_option_id' => $shippingOption2['id'],
            'min'                => 2,
            'max'                => 50,
            'price'              => 24
        ]));

        $response = $this->call('PUT', '/add-to-cart/', [
            'products' => [
                $product1['sku'] => 2,
                $product2['sku'] => 3
            ]
        ]);

        $results        = $this->call('GET', '/order');
        $decodedResults = $results->decodeResponseJson();

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertNull($decodedResults['results']['shippingAddress']);
        $this->assertEquals(0, $decodedResults['results']['shippingCosts']);
    }

    public function test_submit_order_validation_not_physical_products()
    {
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostsFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product1 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0.20);

        $product2 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            247,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0);

        $cart = $this->cartFactory->addCartItem($product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            $product1['weight'],
            [
                'product-id' => $product1['id']
            ]);

        $this->cartFactory->addCartItem($product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            $product2['weight'],
            [
                'product-id' => $product2['id']
            ]);
        $results = $this->call('PUT', '/order');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "payment-type-selector",
                "detail" => "The payment-type-selector field is required.",
            ],
            [
                "source" => "billing-country",
                "detail" => "The billing-country field is required.",
            ],
            [
                "source" => "gateway",
                "detail" => "The gateway field is required.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_submit_order_validation_customer_and_physical_products()
    {
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostsFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product1 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            1,
            0.20);

        $product2 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            247,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            1,
            0);

        $cart = $this->cartFactory->addCartItem($product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            $product1['weight'],
            [
                'product-id' => $product1['id']
            ]);

        $this->cartFactory->addCartItem($product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            $product2['weight'],
            [
                'product-id' => $product2['id']
            ]);
        $results = $this->call('PUT', '/order');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "payment-type-selector",
                "detail" => "The payment-type-selector field is required.",
            ],
            [
                "source" => "billing-country",
                "detail" => "The billing-country field is required.",
            ],
            [
                "source" => "gateway",
                "detail" => "The gateway field is required.",
            ],
            [
                "source" => "shipping-first-name",
                "detail" => "The shipping-first-name field is required.",
            ],
            [
                "source" => "shipping-last-name",
                "detail" => "The shipping-last-name field is required.",
            ],
            [
                "source" => "shipping-address-line-1",
                "detail" => "The shipping-address-line-1 field is required.",
            ],
            [
                "source" => "shipping-city",
                "detail" => "The shipping-city field is required.",
            ],
            [
                "source" => "shipping-region",
                "detail" => "The shipping-region field is required.",
            ],
            [
                "source" => "shipping-zip",
                "detail" => "The shipping-zip field is required.",
            ],
            [
                "source" => "shipping-country",
                "detail" => "The shipping-country field is required.",
            ],
            [

                "source" => "billing-email",
                "detail" => "The billing-email field is required.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_submit_order_validation_member_and_physical_products()
    {
        $userId         = $this->createAndLogInNewUser();
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostsFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product1 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            1,
            0.20);

        $product2 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            247,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            1,
            0);

        $cart = $this->cartFactory->addCartItem($product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            $product1['weight'],
            [
                'product-id' => $product1['id']
            ]);

        $this->cartFactory->addCartItem($product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            $product2['weight'],
            [
                'product-id' => $product2['id']
            ]);
        $results = $this->call('PUT', '/order');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "payment-type-selector",
                "detail" => "The payment-type-selector field is required.",
            ],
            [
                "source" => "billing-country",
                "detail" => "The billing-country field is required.",
            ],
            [
                "source" => "gateway",
                "detail" => "The gateway field is required.",
            ],
            [
                "source" => "shipping-first-name",
                "detail" => "The shipping-first-name field is required.",
            ],
            [
                "source" => "shipping-last-name",
                "detail" => "The shipping-last-name field is required.",
            ],
            [
                "source" => "shipping-address-line-1",
                "detail" => "The shipping-address-line-1 field is required.",
            ],
            [
                "source" => "shipping-city",
                "detail" => "The shipping-city field is required.",
            ],
            [
                "source" => "shipping-region",
                "detail" => "The shipping-region field is required.",
            ],
            [
                "source" => "shipping-zip",
                "detail" => "The shipping-zip field is required.",
            ],
            [
                "source" => "shipping-country",
                "detail" => "The shipping-country field is required.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_submit_order_validation_credit_card()
    {
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostsFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product1 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0.20);

        $product2 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            247,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0);

        $cart = $this->cartFactory->addCartItem($product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            $product1['weight'],
            [
                'product-id' => $product1['id']
            ]);

        $this->cartFactory->addCartItem($product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            $product2['weight'],
            [
                'product-id' => $product2['id']
            ]);
        $results = $this->call('PUT', '/order',
            [
                'payment-type-selector'      => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region'             => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country'            => $this->faker->country,
                'gateway'                    => $paymentGateway['id']
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "credit-card-month-selector",
                "detail" => "The credit-card-month-selector field is required when payment-type-selector is credit card.",
            ],
            [
                "source" => "credit-card-year-selector",
                "detail" => "The credit-card-year-selector field is required when payment-type-selector is credit card.",
            ],
            [
                "source" => "credit-card-number",
                "detail" => "The credit-card-number field is required when payment-type-selector is credit card.",
            ],
            [
                "source" => "credit-card-cvv",
                "detail" => "The credit-card-cvv field is required when payment-type-selector is credit card.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_submit_order_validation_credit_card_expiration_date()
    {
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostsFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product1 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0.20);

        $product2 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            247,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0);

        $cart = $this->cartFactory->addCartItem($product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            $product1['weight'],
            [
                'product-id' => $product1['id']
            ]);

        $this->cartFactory->addCartItem($product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            $product2['weight'],
            [
                'product-id' => $product2['id']
            ]);
        $results = $this->call('PUT', '/order',
            [
                'payment-type-selector'      => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region'             => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country'            => $this->faker->country,
                'credit-card-year-selector'  => 1990,
                'gateway'                    => 1
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "credit-card-month-selector",
                "detail" => "The credit-card-month-selector field is required when payment-type-selector is credit card.",
            ],
            [
                "source" => "credit-card-year-selector",
                "detail" => "The credit-card-year-selector must be at least 2018.",
            ],
            [
                "source" => "credit-card-number",
                "detail" => "The credit-card-number field is required when payment-type-selector is credit card.",
            ],
            [
                "source" => "credit-card-cvv",
                "detail" => "The credit-card-cvv field is required when payment-type-selector is credit card.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_submit_order_validation_rules_for_canadian_users()
    {
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostsFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product1 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0.20);

        $product2 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            247,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0);

        $cart = $this->cartFactory->addCartItem($product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            $product1['weight'],
            [
                'product-id' => $product1['id']
            ]);

        $this->cartFactory->addCartItem($product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            $product2['weight'],
            [
                'product-id' => $product2['id']
            ]);
        $results = $this->call('PUT', '/order',
            [
                'payment-type-selector' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-country'       => 'Canada',
                'gateway'               => 1
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "billing-region",
                "detail" => "The billing-region field is required.",
            ],
            [
                "source" => "billing-zip-or-postal-code",
                "detail" => "The billing-zip-or-postal-code field is required.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_submit_order()
    {
        $userId         = $this->createAndLogInNewUser();
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostsFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product1 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0.20);

        $product2 = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            247,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0);

        $cart = $this->cartFactory->addCartItem($product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            $product1['weight'],
            [
                'product-id' => $product1['id']
            ]);

        $this->cartFactory->addCartItem($product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            $product2['weight'],
            [
                'product-id' => $product2['id']
            ]);

        $expirationDate = $this->faker->creditCardExpirationDate;
        $results        = $this->call('PUT', '/order',
            [
                'payment-type-selector'      => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region'             => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country'            => 'Canada',
                'credit-card-year-selector'  => $expirationDate->format('Y'),
                'credit-card-month-selector' => $expirationDate->format('m'),
                'credit-card-number'         => '4242424242424242',
                'credit-card-cvv'            => $this->faker->randomNumber(4),
                'gateway'                    => $paymentGateway['id']
            ]);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals(1, $results->decodeResponseJson()['results']['id']);
    }

    public function test_submit_order_invalid_credit_card_number()
    {
        $userId         = $this->createAndLogInNewUser();
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostsFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $product = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0.20);

        $cart = $this->cartFactory->addCartItem($product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $expirationDate = $this->faker->creditCardExpirationDate;
        $results        = $this->call('PUT', '/order',
            [
                'payment-type-selector'      => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region'             => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country'            => 'Canada',
                'credit-card-year-selector'  => $expirationDate->format('Y'),
                'credit-card-month-selector' => $expirationDate->format('m'),
                'credit-card-number'         => rand(),
                'credit-card-cvv'            => $this->faker->randomNumber(4),
                'gateway'                    => $paymentGateway['id']
            ]);

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Unprocessable Entity.",
                "detail" => "Order failed. Error message: Can not create token:: Your card number is incorrect.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_submit_order_invalid_paypal()
    {
        $userId         = $this->createAndLogInNewUser();
        $shippingOption = $this->shippingOptionFactory->store('Canada', 1, 1);
        $shippingCost   = $this->shippingCostsFactory->store($shippingOption['id'], 0, 10, 5.50);
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal', 'paypal_1');

        $product = $this->productFactory->store(ConfigService::$brand,
            $this->faker->word,
            $this->faker->word,
            12.95,
            ProductService::TYPE_PRODUCT,
            1,
            $this->faker->text,
            $this->faker->url,
            0,
            0.20);

        $cart = $this->cartFactory->addCartItem($product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $expirationDate = $this->faker->creditCardExpirationDate;
        $results        = $this->call('PUT', '/order',
            [
                'payment-type-selector'         => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                => $this->faker->word,
                'billing-zip-or-postal-code'    => $this->faker->postcode,
                'billing-country'               => 'Canada',
                'paypal-express-checkout-token' => rand(),
                'gateway'                       => $paymentGateway['id']
            ]);

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertArraySubset(
            [
                "title" => "Unprocessable Entity."
            ]
            , $results->decodeResponseJson()['error']);
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
