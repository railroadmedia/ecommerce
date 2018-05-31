<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\CartFactory;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\ShippingCostsFactory;
use Railroad\Ecommerce\Factories\ShippingOptionFactory;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Services\SubscriptionService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

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
     * @var \Railroad\Ecommerce\Repositories\DiscountRepository
     */
    protected $discountRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountCriteriaRepository
     */
    protected $discountCriteriaRepository;

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
        $this->discountCriteriaRepository = $this->app->make(DiscountCriteriaRepository::class);
        $this->discountRepository = $this->app->make(DiscountRepository::class);
    }

    public function test_submit_order_validation_not_physical_products()
    {
        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption([
            'country'  => 'Canada',
            'active'   => 1,
            'priority' => 1
        ]));
        $this->shippingCostsRepository->create($this->faker->shippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min'                => 0,
            'max'                => 10,
            'price'              => 5.50
        ]));


        $product1 =$this->productRepository->create($this->faker->product([
            'price'                       => 12.95,
            'type'                        => ProductService::TYPE_PRODUCT,
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $product2 = $this->productRepository->create($this->faker->product([
            'price'                       => 274,
            'type'                        => ProductService::TYPE_PRODUCT,
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

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
                "source" => "payment_method_type",
                "detail" => "The payment method type field is required.",
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

        $product1 = $this->productRepository->create($this->faker->product([
            'price'                       => 274,
            'type'                        => ProductService::TYPE_PRODUCT,
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $product2 = $this->productRepository->create($this->faker->product([
            'price'                       => 4,
            'type'                        => ProductService::TYPE_PRODUCT,
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

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
                "source" => "payment_method_type",
                "detail" => "The payment method type field is required.",
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

        $product1 = $this->productRepository->create($this->faker->product([
            'price'                       => 4,
            'type'                        => ProductService::TYPE_PRODUCT,
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $product2 = $this->productRepository->create($this->faker->product([
            'price'                       => 4,
            'type'                        => ProductService::TYPE_PRODUCT,
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 12,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

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
                "source" => "payment_method_type",
                "detail" => "The payment method type field is required.",
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
        $userId      = $this->createAndLogInNewUser();
        $fingerPrint = '4242424242424242';
        $this->stripeExternalHelperMock->method('getCustomersByEmail')->willReturn(['data' => '']);
        $fakerCustomer        = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')->willReturn($fakerCustomer);

        $fakerCard              = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand       = $this->faker->word;
        $fakerCard->last4       = $this->faker->randomNumber(3);
        $fakerCard->exp_year    = 2020;
        $fakerCard->exp_month   = 12;
        $fakerCard->id          = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')->willReturn($fakerCard);

        $fakerCharge           = new Charge();
        $fakerCharge->id       = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount   = 100;
        $fakerCharge->status   = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')->willReturn($fakerToken);

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption([
            'country'  => 'Canada',
            'active'   => 1,
            'priority' => 1
        ]));
        $shippingCost   = $this->shippingCostsRepository->create($this->faker->shippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min'                => 0,
            'max'                => 10,
            'price'              => 5.50
        ]));

        $product1 = $this->productRepository->create($this->faker->product([
            'price'                       => 12.95,
            'type'                        => ProductService::TYPE_PRODUCT,
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 0.20,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $product2 = $this->productRepository->create($this->faker->product([
            'price'                       => 247,
            'type'                        => ProductService::TYPE_PRODUCT,
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));
        $discount = $this->discountRepository->create($this->faker->discount(['active' => true,
            'type' => 'order total amount off']));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id' => $product1['id'],
            'type' => 'order total requirement',
            'min' => '2',
            'max' => '2000000'
        ]));

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
                'payment_method_type'        => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region'             => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country'            => 'Canada',
                'company_name'               => $this->faker->creditCardType,
                'credit-card-year-selector'  => $expirationDate->format('Y'),
                'credit-card-month-selector' => $expirationDate->format('m'),
                'credit-card-number'         => $fingerPrint,
                'credit-card-cvv'            => $this->faker->randomNumber(4),
                'gateway'                    => 'drumeo',
                'card-token'                 => '4242424242424242',
                'shipping-first-name'        => $this->faker->firstName,
                'shipping-last-name'         => $this->faker->lastName,
                'shipping-address-line-1'    => $this->faker->address,
                'shipping-city'              => 'Canada',
                'shipping-region'            => 'ab',
                'shipping-zip'               => $this->faker->postcode,
                'shipping-country'           => 'Canada'
            ]);
dd($results);
        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals(1, $results->decodeResponseJson()['results']['id']);
    }

    public function test_submit_order_subscription()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 12.95,
            'type'                        => ProductService::TYPE_SUBSCRIPTION,
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => SubscriptionService::INTERVAL_TYPE_YEARLY,
            'subscription_interval_count' => 1
        ]));

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

        $results        = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Canada',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word
            ]);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals(1, $results->decodeResponseJson()['results']['id']);
    }


    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
