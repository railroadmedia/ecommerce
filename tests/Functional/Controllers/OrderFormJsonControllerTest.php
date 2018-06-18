<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\PaymentMethodService;
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
     * @var CartService
     */
    protected $cartService;

    protected function setUp()
    {
        parent::setUp();
        $this->productRepository          = $this->app->make(ProductRepository::class);
        $this->shippingOptionRepository   = $this->app->make(ShippingOptionRepository::class);
        $this->shippingCostsRepository    = $this->app->make(ShippingCostsRepository::class);
        $this->paymentGatewayRepository   = $this->app->make(PaymentGatewayRepository::class);
        $this->cartService                = $this->app->make(CartService::class);
        $this->discountCriteriaRepository = $this->app->make(DiscountCriteriaRepository::class);
        $this->discountRepository         = $this->app->make(DiscountRepository::class);
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

        $product1 = $this->productRepository->create($this->faker->product([
            'price'                       => 12.95,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $product2 = $this->productRepository->create($this->faker->product([
            'price'                       => 274,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $cart = $this->cartService->addCartItem($product1['name'],
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

        $this->cartService->addCartItem($product2['name'],
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
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $product2 = $this->productRepository->create($this->faker->product([
            'price'                       => 4,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $cart = $this->cartService->addCartItem($product1['name'],
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

        $this->cartService->addCartItem($product2['name'],
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
        $userId = $this->createAndLogInNewUser();

        $product1 = $this->productRepository->create($this->faker->product([
            'price'                       => 4,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $product2 = $this->productRepository->create($this->faker->product([
            'price'                       => 4,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 12,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $cart = $this->cartService->addCartItem($product1['name'],
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

        $this->cartService->addCartItem($product2['name'],
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
        $this->stripeExternalHelperMock->method('createCardToken')->willThrowException(new PaymentFailedException('The card number is incorrect. Check the card’s number or use a different card.'));

        $product1 = $this->productRepository->create($this->faker->product(['is_physical' => 0]));

        $cart = $this->cartService->addCartItem($product1['name'],
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

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'        => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region'             => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country'            => $this->faker->country,
                'gateway'                    => 'drumeo'
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "credit-card-month-selector",
                "detail" => "The credit-card-month-selector field is required when payment method type is credit-card.",
            ],
            [
                "source" => "credit-card-year-selector",
                "detail" => "The credit-card-year-selector field is required when payment method type is credit-card.",
            ],
            [
                "source" => "credit-card-number",
                "detail" => "The credit-card-number field is required when payment method type is credit-card.",
            ],
            [
                "source" => "credit-card-cvv",
                "detail" => "The credit-card-cvv field is required when payment method type is credit-card.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_submit_order_validation_rules_for_canadian_users()
    {
        $product1 = $this->productRepository->create($this->faker->product());

        $cart = $this->cartService->addCartItem($product1['name'],
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

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-country'     => 'Canada',
                'gateway'             => 1
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertArraySubset([
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
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 0.20,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $product2         = $this->productRepository->create($this->faker->product([
            'price'                       => 247,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));
        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => 'order total amount off'
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product1['id'],
            'type'        => 'order total requirement',
            'min'         => '2',
            'max'         => '2000000'
        ]));

        $cart = $this->cartService->addCartItem($product1['name'],
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

        $this->cartService->addCartItem($product2['name'],
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

        $this->assertEquals(200, $results->getStatusCode());
    }

    public function test_submit_order_subscription()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 12.95,
            'type'                        => config('constants.TYPE_SUBSCRIPTION'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => config('constants.INTERVAL_TYPE_YEARLY'),
            'subscription_interval_count' => 1
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
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

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Canada',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word
            ]);

        $this->assertEquals(200, $results->getStatusCode());
    }

    public function test_submit_order_with_discount_based_on_shipping_requirements()
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
            'min'                => 1,
            'max'                => 10,
            'price'              => 5.50
        ]));

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 12.95,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 2,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => 'product amount off',
            'amount' => 1.95
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => 'shipping total requirement',
            'min'         => '1',
            'max'         => '2000'
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
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

        $this->assertEquals(200, $results->getStatusCode());
        $tax = round(0.05 * ($product['price'] - $discount['amount']) + 0.05 * $shippingCost['price'], 2);

        $this->assertDatabaseHas(ConfigService::$tableOrder, [
            'due'            => $product['price'] - $discount['amount'] + $shippingCost['price'] + $tax,
            'shipping_costs' => $shippingCost['price'],
            'user_id'        => $userId
        ]);
    }

    public function test_submit_order_with_discount_based_on_product_quantity()
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

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 12.95,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 2,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => 'product amount off',
            'amount' => 1.95
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => 'product quantity requirement',
            'min'         => 2,
            'max'         => 5
        ]));

        $quantity = 2;
        $cart     = $this->cartService->addCartItem($product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
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
                'shipping-region'            => 'bc',
                'shipping-zip'               => $this->faker->postcode,
                'shipping-country'           => 'Canada'
            ]);

        $tax = round(0.05 * ($product['price'] - $discount['amount']) * $quantity + 0.05 * $shippingCost['price'], 2);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertDatabaseHas(ConfigService::$tableOrder, [
            'due'            => ($product['price'] - $discount['amount']) * 2 + $shippingCost['price'] + $tax,
            'shipping_costs' => $shippingCost['price'],
            'user_id'        => $userId
        ]);
    }

    public function test_submit_order_subscription_with_discount_free_days()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 12.95,
            'type'                        => config('constants.TYPE_SUBSCRIPTION'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => 'year',
            'subscription_interval_count' => 1
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE,
            'amount' => 10
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => 'date requirement',
            'min'         => $this->faker->dateTimeInInterval('', '-5days'),
            'max'         => $this->faker->dateTimeInInterval('', '+5days')
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Canada',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount days are added to the paid_until data
        $this->assertDatabaseHas(ConfigService::$tableSubscription,
            [
                'brand'      => ConfigService::$brand,
                'product_id' => $product['id'],
                'user_id'    => $userId,
                'is_active'  => "1",
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()->addYear(1)->addDays(10)->toDateTimeString()
            ]);
    }

    public function test_submit_order_subscription_with_discount_recurring_amount()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_SUBSCRIPTION'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => 'year',
            'subscription_interval_count' => 1
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE,
            'amount' => 10
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => 'date requirement',
            'min'         => $this->faker->dateTimeInInterval('', '-5days'),
            'max'         => $this->faker->dateTimeInInterval('', '+5days')
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Canada',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount days are added to the paid_until data
        $this->assertDatabaseHas(ConfigService::$tableSubscription,
            [
                'brand'                   => ConfigService::$brand,
                'product_id'              => $product['id'],
                'user_id'                 => $userId,
                'is_active'               => "1",
                'start_date'              => Carbon::now()->toDateTimeString(),
                'paid_until'              => Carbon::now()->addYear(1)->toDateTimeString(),
                'total_price_per_payment' => $product['price'] - $discount['amount']
            ]);
    }

    public function test_submit_order_with_discount_order_total_amount()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $quantity = 2;

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
            'amount' => 10
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min'         => 5,
            'max'         => 500
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Romanian',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'brand'          => ConfigService::$brand,
                'user_id'        => $userId,
                'due'            => $product['price'] * $quantity - $discount['amount'],
                'tax'            => 0,
                'shipping_costs' => 0,
                'paid'           => $product['price'] * $quantity - $discount['amount']
            ]);
    }

    public function test_submit_order_with_discount_order_total_percent()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $quantity = 2;

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE,
            'amount' => 10
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min'         => 5,
            'max'         => 500
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Romanian',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'brand'          => ConfigService::$brand,
                'user_id'        => $userId,
                'due'            => $product['price'] * $quantity - $discount['amount'] / 100 * $product['price'] * $quantity,
                'tax'            => 0,
                'shipping_costs' => 0,
                'paid'           => $product['price'] * $quantity - $discount['amount'] / 100 * $product['price'] * $quantity
            ]);
    }

    public function test_submit_order_with_discount_product_amount()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $quantity = 2;

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
            'amount' => 10
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min'         => 5,
            'max'         => 500
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Romanian',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'brand'          => ConfigService::$brand,
                'user_id'        => $userId,
                'due'            => ($product['price'] - $discount['amount']) * $quantity,
                'tax'            => 0,
                'shipping_costs' => 0,
                'paid'           => ($product['price'] - $discount['amount']) * $quantity
            ]);

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(ConfigService::$tableOrderItem,
            [
                'product_id'    => $product['id'],
                'quantity'      => $quantity,
                'initial_price' => $product['price'] * $quantity,
                'discount'      => $discount['amount'] * $quantity,
                'total_price'   => ($product['price'] - $discount['amount']) * $quantity
            ]);
    }

    public function test_submit_order_with_discount_product_percent()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $quantity = 2;

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
            'amount' => 10
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min'         => 5,
            'max'         => 500
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Romanian',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'brand'          => ConfigService::$brand,
                'user_id'        => $userId,
                'due'            => $product['price'] * $quantity - $discount['amount'] / 100 * $product['price'] * $quantity,
                'tax'            => 0,
                'shipping_costs' => 0,
                'paid'           => $product['price'] * $quantity - $discount['amount'] / 100 * $product['price'] * $quantity
            ]);

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(ConfigService::$tableOrderItem,
            [
                'product_id'    => $product['id'],
                'quantity'      => $quantity,
                'initial_price' => $product['price'] * $quantity,
                'discount'      => $product['price'] * $quantity * $discount['amount'] / 100,
                'total_price'   => ($product['price'] - $product['price'] * $discount['amount'] / 100) * $quantity
            ]);
    }

    public function test_submit_order_with_discount_shipping_costs_amount()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $quantity = 2;

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption([
            'country'  => 'Canada',
            'active'   => 1,
            'priority' => 1
        ]));
        $shippingCosts  = $this->shippingCostsRepository->create($this->faker->shippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min'                => 0,
            'max'                => 10,
            'price'              => 5.50
        ]));

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 2,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
            'amount' => 2
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min'         => 5,
            'max'         => 500
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Romanian',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word,
                'shipping-first-name'              => $this->faker->firstName,
                'shipping-last-name'               => $this->faker->lastName,
                'shipping-address-line-1'          => $this->faker->address,
                'shipping-city'                    => 'Canada',
                'shipping-region'                  => 'ab',
                'shipping-zip'                     => $this->faker->postcode,
                'shipping-country'                 => 'Canada'
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'brand'          => ConfigService::$brand,
                'user_id'        => $userId,
                'due'            => $product['price'] * $quantity + $shippingCosts['price'] - $discount['amount'],
                'tax'            => 0,
                'shipping_costs' => $shippingCosts['price'] - $discount['amount'],
                'paid'           => $product['price'] * $quantity + $shippingCosts['price'] - $discount['amount']
            ]);

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(ConfigService::$tableOrderItem,
            [
                'product_id'    => $product['id'],
                'quantity'      => $quantity,
                'initial_price' => $product['price'] * $quantity,
                'total_price'   => $product['price'] * $quantity + $shippingCosts['price'] - $discount['amount']
            ]);
    }

    public function test_submit_order_with_discount_shipping_costs_percent()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $quantity = 2;

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption([
            'country'  => 'Canada',
            'active'   => 1,
            'priority' => 1
        ]));
        $shippingCosts  = $this->shippingCostsRepository->create($this->faker->shippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min'                => 0,
            'max'                => 10,
            'price'              => 5.50
        ]));

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 2,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
            'amount' => 10
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min'         => 5,
            'max'         => 500
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Romanian',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word,
                'shipping-first-name'              => $this->faker->firstName,
                'shipping-last-name'               => $this->faker->lastName,
                'shipping-address-line-1'          => $this->faker->address,
                'shipping-city'                    => 'Canada',
                'shipping-region'                  => 'ab',
                'shipping-zip'                     => $this->faker->postcode,
                'shipping-country'                 => 'Canada'
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'brand'          => ConfigService::$brand,
                'user_id'        => $userId,
                'due'            => $product['price'] * $quantity + $shippingCosts['price'] - $discount['amount'] / 100 * $shippingCosts['price'],
                'tax'            => 0,
                'shipping_costs' => $shippingCosts['price'] - $discount['amount'] / 100 * $shippingCosts['price'],
                'paid'           => $product['price'] * $quantity + $shippingCosts['price'] - $discount['amount'] / 100 * $shippingCosts['price']
            ]);

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(ConfigService::$tableOrderItem,
            [
                'product_id'    => $product['id'],
                'quantity'      => $quantity,
                'initial_price' => $product['price'] * $quantity,
                'total_price'   => $product['price'] * $quantity + $shippingCosts['price'] - $discount['amount'] / 100 * $shippingCosts['price']
            ]);
    }

    public function test_submit_order_with_discount_shipping_costs_overwrite()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $quantity = 2;

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption([
            'country'  => 'Canada',
            'active'   => 1,
            'priority' => 1
        ]));
        $shippingCosts  = $this->shippingCostsRepository->create($this->faker->shippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min'                => 0,
            'max'                => 10,
            'price'              => 5.50
        ]));

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 2,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
            'amount' => 10
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
            'min'         => 5,
            'max'         => 500
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Romanian',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word,
                'shipping-first-name'              => $this->faker->firstName,
                'shipping-last-name'               => $this->faker->lastName,
                'shipping-address-line-1'          => $this->faker->address,
                'shipping-city'                    => 'Canada',
                'shipping-region'                  => 'ab',
                'shipping-zip'                     => $this->faker->postcode,
                'shipping-country'                 => 'Canada'
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'brand'          => ConfigService::$brand,
                'user_id'        => $userId,
                'due'            => $product['price'] * $quantity + $discount['amount'],
                'tax'            => 0,
                'shipping_costs' => $discount['amount'],
                'paid'           => $product['price'] * $quantity + $discount['amount']
            ]);

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(ConfigService::$tableOrderItem,
            [
                'product_id'    => $product['id'],
                'quantity'      => $quantity,
                'initial_price' => $product['price'] * $quantity,
                'total_price'   => $product['price'] * $quantity + $discount['amount']
            ]);
    }

    public function test_customer_submit_order()
    {
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
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 0.20,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $product2         = $this->productRepository->create($this->faker->product([
            'price'                       => 247,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));
        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => 'order total amount off'
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product1['id'],
            'type'        => 'order total requirement',
            'min'         => '2',
            'max'         => '2000000'
        ]));

        $cart = $this->cartService->addCartItem($product1['name'],
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

        $this->cartService->addCartItem($product2['name'],
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

        $expirationDate      = $this->faker->creditCardExpirationDate;
        $billingEmailAddress = $this->faker->email;

        $results = $this->call('PUT', '/order',
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
                'shipping-country'           => 'Canada',
                'billing-email'              => $billingEmailAddress
            ]);
        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(ConfigService::$tableCustomer,
            [
                'email'      => $billingEmailAddress,
                'brand'      => ConfigService::$brand,
                'created_on' => Carbon::now()->toDateTimeString()
            ]);

        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'user_id'     => null,
                'customer_id' => 1,
                'created_on'  => Carbon::now()->toDateTimeString()
            ]);
    }

    public function test_submit_order_new_user()
    {
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $quantity = 2;

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $accountCreationMail     = $this->faker->email;
        $accountCreationPassword = $this->faker->password;

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Romanian',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word,
                'shipping-first-name'              => $this->faker->firstName,
                'shipping-last-name'               => $this->faker->lastName,
                'shipping-address-line-1'          => $this->faker->address,
                'shipping-city'                    => 'Canada',
                'shipping-region'                  => 'ab',
                'shipping-zip'                     => $this->faker->postcode,
                'shipping-country'                 => 'Canada',
                'account-creation-email'           => $accountCreationMail,
                'account-creation-password'        => $accountCreationPassword
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'brand'          => ConfigService::$brand,
                'user_id'        => 1,
                'due'            => $product['price'] * $quantity,
                'tax'            => 0,
                'shipping_costs' => 0,
                'paid'           => $product['price'] * $quantity
            ]);
        $this->assertDatabaseHas(ConfigService::$tableUserPaymentMethods,
            [
                'user_id'    => 1,
                'created_on' => Carbon::now()->toDateTimeString()
            ]);
    }

    public function test_invoice_order_send_after_submit()
    {
        Mail::fake();

        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 25,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'              => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-region'                   => $this->faker->word,
                'billing-zip-or-postal-code'       => $this->faker->postcode,
                'billing-country'                  => 'Canada',
                'gateway'                          => 'drumeo',
                'validated-express-checkout-token' => $this->faker->word,
                'shipping-first-name'              => $this->faker->firstName,
                'shipping-last-name'               => $this->faker->lastName,
                'shipping-address-line-1'          => $this->faker->address,
                'shipping-city'                    => 'Canada',
                'shipping-region'                  => 'ab',
                'shipping-zip'                     => $this->faker->postcode,
                'shipping-country'                 => 'Canada'
            ]);

        // Assert a message was sent to the given users...
        Mail::assertSent(OrderInvoice::class, function ($mail) {
            $mail->build();

            return $mail->hasTo(auth()->user()['email']) &&
                $mail->hasFrom(config('ecommerce.invoiceSender')) &&
                $mail->subject(config('ecommerce.invoicerEmailSubject'));
        });

        //assert a mailable was sent
        Mail::assertSent(OrderInvoice::class, 1);

        //assert cart it's empty after submit
        $this->assertEmpty($this->cartService->getAllCartItems());
    }

    public function test_payment_plan()
    {
        $userId      = $this->createAndLogInNewUser();
        $fingerPrint = '4242424242424242';
        $this->stripeExternalHelperMock->method('getCustomersByEmail')->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        // $fakerCustomer->email = $this->faker->email;
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
            'min'                => 5,
            'max'                => 10,
            'price'              => 5.50
        ]));

        $product = $this->productRepository->create($this->faker->product([
            'price'                       => 247,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));

        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => 'product amount off',
            'amount' => 50
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product['id'],
            'type'        => 'product quantity requirement',
            'min'         => '1',
            'max'         => '100'
        ]));

        $cart = $this->cartService->addCartItem($product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            $product['weight'],
            [
                'product-id' => $product['id']
            ]);

        $expirationDate    = $this->faker->creditCardExpirationDate;
        $paymentPlanOption = $this->faker->randomElement([2, 5]);
        $results           = $this->call('PUT', '/order',
            [
                'payment_method_type'        => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region'             => 'british columbia',
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
                'shipping-region'            => 'british columbia',
                'shipping-zip'               => $this->faker->postcode,
                'shipping-country'           => 'Canada',
                'payment-plan-selector'      => $paymentPlanOption
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription, [
                'type'              => config('constants.TYPE_PAYMENT_PLAN'),
                'brand'             => ConfigService::$brand,
                'user_id'           => $userId,
                'start_date'        => Carbon::now()->toDateTimeString(),
                'paid_until'        => Carbon::now()->addMonth(1)->toDateTimeString(),
                'total_cycles_due'  => $paymentPlanOption,
                'total_cycles_paid' => 1,
                'created_on'        => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_multiple_discounts()
    {
        $userId      = $this->createAndLogInNewUser();
        $fingerPrint = '4242424242424242';
        $this->stripeExternalHelperMock->method('getCustomersByEmail')->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        // $fakerCustomer->email = $this->faker->email;
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
            'min'                => 5,
            'max'                => 10,
            'price'              => 19
        ]));

        $product1          = $this->productRepository->create($this->faker->product([
            'price'                       => 147,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 0,
            'weight'                      => 0,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));
        $product2          = $this->productRepository->create($this->faker->product([
            'price'                       => 79,
            'type'                        => config('constants.TYPE_PRODUCT'),
            'active'                      => 1,
            'description'                 => $this->faker->word,
            'is_physical'                 => 1,
            'weight'                      => 5.10,
            'subscription_interval_type'  => '',
            'subscription_interval_count' => ''
        ]));
        $discount          = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => 'product amount off',
            'amount' => 20
        ]));
        $discountCriteria  = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount['id'],
            'product_id'  => $product1['id'],
            'type'        => 'product quantity requirement',
            'min'         => '1',
            'max'         => '100'
        ]));
        $discount2         = $this->discountRepository->create($this->faker->discount([
            'active' => true,
            'type'   => 'product amount off',
            'amount' => 20
        ]));
        $discountCriteria2 = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'discount_id' => $discount2['id'],
            'product_id'  => $product2['id'],
            'type'        => 'product quantity requirement',
            'min'         => '1',
            'max'         => '100'
        ]));

        $this->cartService->addCartItem($product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $product1['subscription_interval_type'],
            $product1['subscription_interval_count'],
            $product1['weight'],
            [
                'product-id' => $product1['id']
            ]);
        $this->cartService->addCartItem($product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $product2['subscription_interval_type'],
            $product2['subscription_interval_count'],
            $product2['weight'],
            [
                'product-id' => $product2['id']
            ]);

        $expirationDate = $this->faker->creditCardExpirationDate;

        $results = $this->call('PUT', '/order',
            [
                'payment_method_type'        => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region'             => 'ro',
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country'            => 'Romania',
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
                'shipping-region'            => 'british columbia',
                'shipping-zip'               => $this->faker->postcode,
                'shipping-country'           => 'Canada'
            ]);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertDatabaseHas(ConfigService::$tableOrder,
            [
                'due'            => ($product1['price'] - $discount['amount'] + $product2['price'] - $discount2['amount'] + $shippingCost['price']),
                'paid'            => ($product1['price'] - $discount['amount'] + $product2['price'] - $discount2['amount'] + $shippingCost['price']),
                'tax'            => 0,
                'shipping_costs' => $shippingCost['price']
            ]);
    }
}
