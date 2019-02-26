<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Entities\CartItem;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\DiscountCriteriaService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class OrderFormJsonControllerTest extends EcommerceTestCase
{
    use WithoutMiddleware;

    /**
     * @var CartService
     */
    protected $cartService;

    protected function setUp()
    {
        parent::setUp();
        $this->cartService = $this->app->make(CartService::class);
    }

    public function test_submit_order_validation_not_physical_products()
    {
        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productOneQuantity = 1;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productOne['id'],
            ]
        );

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $results = $this->call('PUT', '/order');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'payment_method_type',
                    'detail' => 'The payment method type field is required when payment-method-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'payment-method-id',
                    'detail' => 'The payment-method-id field is required when payment method type is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'billing-country',
                    'detail' => 'The billing-country field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'gateway',
                    'detail' => 'The gateway field is required.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_validation_customer_and_physical_products()
    {
        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productOneQuantity = 1;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productOne['id'],
            ]
        );

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $results = $this->call('PUT', '/order');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'payment_method_type',
                    'detail' => 'The payment method type field is required when payment-method-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'payment-method-id',
                    'detail' => 'The payment-method-id field is required when payment method type is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'billing-country',
                    'detail' => 'The billing-country field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'gateway',
                    'detail' => 'The gateway field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-address-id',
                    'detail' => 'The shipping-address-id field is required when none of shipping-first-name / shipping-last-name / shipping-address-line-1 / shipping-city / shipping-region / shipping-zip-or-postal-code / shipping-country are present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-first-name',
                    'detail' => 'The shipping-first-name field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-last-name',
                    'detail' => 'The shipping-last-name field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-address-line-1',
                    'detail' => 'The shipping-address-line-1 field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-city',
                    'detail' => 'The shipping-city field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-region',
                    'detail' => 'The shipping-region field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-zip-or-postal-code',
                    'detail' => 'The shipping-zip-or-postal-code field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-country',
                    'detail' => 'The shipping-country field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [

                    'source' => 'billing-email',
                    'detail' => 'The billing-email field is required.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_validation_member_and_physical_products()
    {
        $userId = $this->createAndLogInNewUser();

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productOneQuantity = 1;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productOne['id'],
            ]
        );

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $results = $this->call('PUT', '/order');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'payment_method_type',
                    'detail' => 'The payment method type field is required when payment-method-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'payment-method-id',
                    'detail' => 'The payment-method-id field is required when payment method type is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'billing-country',
                    'detail' => 'The billing-country field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'gateway',
                    'detail' => 'The gateway field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-address-id',
                    'detail' => 'The shipping-address-id field is required when none of shipping-first-name / shipping-last-name / shipping-address-line-1 / shipping-city / shipping-region / shipping-zip-or-postal-code / shipping-country are present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-first-name',
                    'detail' => 'The shipping-first-name field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-last-name',
                    'detail' => 'The shipping-last-name field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-address-line-1',
                    'detail' => 'The shipping-address-line-1 field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-city',
                    'detail' => 'The shipping-city field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-region',
                    'detail' => 'The shipping-region field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-zip-or-postal-code',
                    'detail' => 'The shipping-zip-or-postal-code field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'shipping-country',
                    'detail' => 'The shipping-country field is required when shipping-address-id is not present.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_validation_credit_card()
    {
        $this->stripeExternalHelperMock->method('createCardToken')
            ->willThrowException(
                new PaymentFailedException(
                    'The card number is incorrect. Check the card’s number or use a different card.'
                )
            );

        $product = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productQuantity = 1;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => $this->faker->country,
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'card-token',
                    'detail' => 'The card-token field is required when payment method type is credit-card.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_validation_rules_for_canadian_users()
    {
        $product = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productQuantity = 1;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing-country' => 'Canada',
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'billing-region',
                    'detail' => 'The billing-region field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'billing-zip-or-postal-code',
                    'detail' => 'The billing-zip-or-postal-code field is required.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_submit_order_credit_card_payment()
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $fingerPrint = $this->faker->word;
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $session = $this->app->make(Store::class);

        $session->flush();

        $cartAddressService = $this->app->make(CartAddressService::class);

        $requestData = [
            'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'billing-region' => $this->faker->word,
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'card-token' => $fingerPrint,
            'shipping-first-name' => $this->faker->firstName,
            'shipping-last-name' => $this->faker->lastName,
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => $this->faker->city,
            'shipping-region' => 'ab',
            'shipping-zip-or-postal-code' => $this->faker->postcode,
            'shipping-country' => 'Canada',
            'currency' => $currency
        ];

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($requestData['billing-country'])
            ->setState($requestData['billing-region'])
            ->setZipOrPostalCode($requestData['billing-zip-or-postal-code']);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $fakerCard->customer = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 0.20,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productOneQuantity = 1;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productOne['id'],
            ]
        );

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductOneDiscountedPrice = 0;

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $expectedProductTwoTotalPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedProductTwoDiscountedPrice = 0;

        $expectedTotalFromItems = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice;

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = round($expectedTotalFromItems * $taxRate + $shippingCostAmount * $taxRate, 2);

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => null,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $requestData['billing-zip-or-postal-code'],
                            'state' => $requestData['billing-region'],
                            'country' => $requestData['billing-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $requestData['shipping-first-name'],
                            'last_name' => $requestData['shipping-last-name'],
                            'street_line_1' => $requestData['shipping-address-line-1'],
                            'street_line_2' => null,
                            'city' => $requestData['shipping-city'],
                            'zip' => $requestData['shipping-zip-or-postal-code'],
                            'state' => $requestData['shipping-region'],
                            'country' => $requestData['shipping-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
            ]
        );

        // creditCard
        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $fingerPrint,
                'last_four_digits' => $fakerCard->last4,
                'cardholder_name' => '',
                'company_name' => $fakerCard->brand,
                'expiration_date' => Carbon::createFromDate(
                    $fakerCard->exp_year,
                    $fakerCard->exp_month
                )->toDateTimeString(),
                'external_id' => $fakerCard->id,
                'external_customer_id' => $fakerCard->customer,
                'payment_gateway_name' => $requestData['gateway'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // paymentMethod
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_paypal_payment()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $session = $this->app->make(Store::class);

        $session->flush();

        $cartAddressService = $this->app->make(CartAddressService::class);

        $orderRequestData = [
            'payment_method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'billing-region' => $this->faker->word,
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'shipping-first-name' => $this->faker->firstName,
            'shipping-last-name' => $this->faker->lastName,
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => $this->faker->city,
            'shipping-region' => 'ab',
            'shipping-zip-or-postal-code' => $this->faker->postcode,
            'shipping-country' => 'Canada',
            'currency' => $currency
        ];

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($orderRequestData['billing-country'])
            ->setState($orderRequestData['billing-region'])
            ->setZipOrPostalCode($orderRequestData['billing-zip-or-postal-code']);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 0.20,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productOneQuantity = 1;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productOne['id'],
            ]
        );

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $paypalToken = $this->faker->word;

        $this->paypalExternalHelperMock->method('createBillingAgreementExpressCheckoutToken')
            ->willReturn($paypalToken);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        // assert order data was set in the session
        $response->assertSessionHas('order-form-input', $orderRequestData);

        // assert response has redirect information
        $response->assertJsonStructure([
            'data',
            'meta' => ['redirect']
        ]);

        $decodedResponse = $response->decodeResponseJson();

        // assert the redirect link contains the token
        $this->assertContains(
            'token=' . $paypalToken,
            $decodedResponse['meta']['redirect']
        );

        /*
         * the paypal payment flow for an order requires
         * the user to be redirected back from paypal site with an agreement token
         * and this is a different action tested in OrderFormControllerTest
         */
    }

    public function test_submit_order_existing_payment_method_credit_card()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $billingData = [
            'billing-region' => $this->faker->word,
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
        ];

        $session = $this->app->make(Store::class);

        $session->flush();

        $cartAddressService = $this->app->make(CartAddressService::class);

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($billingData['billing-country'])
            ->setState($billingData['billing-region'])
            ->setZipOrPostalCode($billingData['billing-zip-or-postal-code']);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $fingerPrint = $this->faker->word;
        $externalId = 'card_' . $this->faker->password;
        $externalCustomerId = 'cus_' . $this->faker->password;
        $cardExpirationYear = 2019;
        $cardExpirationMonth = 12;
        $cardExpirationDate = Carbon::createFromDate(
                $cardExpirationYear,
                $cardExpirationMonth
            )
            ->toDateTimeString();

        $creditCard = $this->fakeCreditCard([
            'fingerprint' => $fingerPrint,
            'last_four_digits' => $this->faker->randomNumber(4),
            'cardholder_name' => $this->faker->name,
            'company_name' => $this->faker->creditCardType,
            'expiration_date' => $cardExpirationDate,
            'external_id' => $externalId,
            'external_customer_id' => $externalCustomerId,
            'payment_gateway_name' => $brand
        ]);

        $billingAddress = $this->fakeAddress([
            'user_id' => $userId,
            'first_name' => null,
            'last_name' => null,
            'street_line_1' => null,
            'street_line_2' => null,
            'city' => null,
            'type' => ConfigService::$billingAddressType,
            'zip' => $billingData['billing-zip-or-postal-code'],
            'state' => $billingData['billing-region'],
            'country' => $billingData['billing-country'],
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'currency' => $currency,
            'billing_address_id' => $billingAddress['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $orderRequestData = [
            'payment-method-id' => $paymentMethod['id'],
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'shipping-first-name' => $this->faker->firstName,
            'shipping-last-name' => $this->faker->lastName,
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => $this->faker->city,
            'shipping-region' => 'ab',
            'shipping-zip-or-postal-code' => $this->faker->postcode,
            'shipping-country' => 'Canada',
            'currency' => $currency
        ] + $billingData;

        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $creditCard['company_name'];
        $fakerCard->last4 = $creditCard['last_four_digits'];
        $fakerCard->exp_year = $cardExpirationYear;
        $fakerCard->exp_month = $cardExpirationMonth;
        $fakerCard->id = $externalId;
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn($fakerCard);

        $chargeAmount = $this->faker->numberBetween(100, 200);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $chargeAmount;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 0.20,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $discount = $this->fakeDiscount([
            'active' => true,
            'type' => 'order total amount off',
            'amount' => 10
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id'],
            'product_id' => $productOne['id'],
            'type' => 'order total requirement',
            'min' => '2',
            'max' => '2000000',
        ]);

        $productOneQuantity = 1;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productOne['id'],
            ]
        );

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductOneDiscountedPrice = 0;

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $expectedProductTwoTotalPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedProductTwoDiscountedPrice = 0;

        $expectedTotalFromItems = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice;

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = round($expectedTotalFromItems * $taxRate + $shippingCostAmount * $taxRate, 2);

        $expectedOrderDiscount = $discount['amount'];

        $expectedOrderTotalDue = round($expectedTotalFromItems - $expectedOrderDiscount + $shippingCostAmount + $expectedTaxes, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService
            ->convertFromBase(round($expectedOrderTotalDue, 2), $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => null,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $orderRequestData['billing-zip-or-postal-code'],
                            'state' => $orderRequestData['billing-region'],
                            'country' => $orderRequestData['billing-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $orderRequestData['shipping-first-name'],
                            'last_name' => $orderRequestData['shipping-last-name'],
                            'street_line_1' => $orderRequestData['shipping-address-line-1'],
                            'street_line_2' => null,
                            'city' => $orderRequestData['shipping-city'],
                            'zip' => $orderRequestData['shipping-zip-or-postal-code'],
                            'state' => $orderRequestData['shipping-region'],
                            'country' => $orderRequestData['shipping-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
            ]
        );

        // assert payment
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => 'order',
                'external_id' => $fakerCharge->id,
                'external_provider' => 'stripe',
                'status' => 'paid',
                'message' => '',
                'payment_method_id' => $paymentMethod['id'],
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_existing_payment_method_paypal()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $billingData = [
            'billing-region' => $this->faker->word,
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
        ];

        $session = $this->app->make(Store::class);

        $session->flush();

        $cartAddressService = $this->app->make(CartAddressService::class);

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($billingData['billing-country'])
            ->setState($billingData['billing-region'])
            ->setZipOrPostalCode($billingData['billing-zip-or-postal-code']);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $billingAddress = $this->fakeAddress([
            'user_id' => $userId,
            'first_name' => null,
            'last_name' => null,
            'street_line_1' => null,
            'street_line_2' => null,
            'city' => null,
            'type' => ConfigService::$billingAddressType,
            'zip' => $billingData['billing-zip-or-postal-code'],
            'state' => $billingData['billing-region'],
            'country' => $billingData['billing-country'],
        ]);

        $billingAgreementExternalId = 'B-' . $this->faker->password;

        $paypalAgreement = $this->fakePaypalBillingAgreement([
            'external_id' => $billingAgreementExternalId,
            'payment_gateway_name' => $brand,
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $paypalAgreement['id'],
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'currency' => $currency,
            'billing_address_id' => $billingAddress['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $orderRequestData = [
            'payment-method-id' => $paymentMethod['id'],
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'shipping-first-name' => $this->faker->firstName,
            'shipping-last-name' => $this->faker->lastName,
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => $this->faker->city,
            'shipping-region' => 'ab',
            'shipping-zip-or-postal-code' => $this->faker->postcode,
            'shipping-country' => 'Canada',
            'currency' => $currency
        ] + $billingData;

        $transactionId = rand(1, 100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 0.20,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $discount = $this->fakeDiscount([
            'active' => true,
            'type' => 'order total amount off',
            'amount' => 10
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id'],
            'product_id' => $productOne['id'],
            'type' => 'order total requirement',
            'min' => '2',
            'max' => '2000000',
        ]);

        $productOneQuantity = 1;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productOne['id'],
            ]
        );

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductOneDiscountedPrice = 0;

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $expectedProductTwoTotalPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedProductTwoDiscountedPrice = 0;

        $expectedTotalFromItems = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice;

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = round($expectedTotalFromItems * $taxRate + $shippingCostAmount * $taxRate, 2);

        $expectedOrderDiscount = $discount['amount'];

        $expectedOrderTotalDue = round($expectedTotalFromItems - $expectedOrderDiscount + $shippingCostAmount + $expectedTaxes, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService
            ->convertFromBase(round($expectedOrderTotalDue, 2), $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => null,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $orderRequestData['billing-zip-or-postal-code'],
                            'state' => $orderRequestData['billing-region'],
                            'country' => $orderRequestData['billing-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $orderRequestData['shipping-first-name'],
                            'last_name' => $orderRequestData['shipping-last-name'],
                            'street_line_1' => $orderRequestData['shipping-address-line-1'],
                            'street_line_2' => null,
                            'city' => $orderRequestData['shipping-city'],
                            'zip' => $orderRequestData['shipping-zip-or-postal-code'],
                            'state' => $orderRequestData['shipping-region'],
                            'country' => $orderRequestData['shipping-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
            ]
        );

        // assert payment
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => 'order',
                'external_id' => $transactionId,
                'external_provider' => 'paypal',
                'status' => 'paid',
                'message' => '',
                'payment_method_id' => $paymentMethod['id'],
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_existing_shipping_address()
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $fingerPrint = $this->faker->word;
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $session = $this->app->make(Store::class);

        $session->flush();

        $cartAddressService = $this->app->make(CartAddressService::class);

        $shippingAddressData = [
            'type' => ConfigService::$shippingAddressType,
            'brand' => $brand,
            'user_id' => $userId,
            'customer_id' => null,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'street_line_1' => $this->faker->address,
            'street_line_2' => null,
            'city' => $this->faker->city,
            'zip' => $this->faker->postcode,
            'state' => 'AB',
            'country' => 'Canada'
        ];

        $shippingAddress = $this->fakeAddress($shippingAddressData);

        $sessionShippingAddress = new Address();

        $sessionShippingAddress
            ->setCountry($shippingAddress['country'])
            ->setState($shippingAddress['state'])
            ->setZipOrPostalCode($shippingAddress['zip'])
            ->setFirstName($shippingAddress['first_name'])
            ->setLastName($shippingAddress['last_name'])
            ->setStreetLineOne($shippingAddress['street_line_1'])
            ->setCity($shippingAddress['city']);

        $cartAddressService->setAddress(
            $sessionShippingAddress,
            CartAddressService::SHIPPING_ADDRESS_TYPE
        );

        $requestData = [
            'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'billing-region' => $this->faker->word,
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'card-token' => $fingerPrint,
            'shipping-address-id' => $shippingAddress['id'],
            'currency' => $currency
        ];

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($requestData['billing-country'])
            ->setState($requestData['billing-region'])
            ->setZipOrPostalCode($requestData['billing-zip-or-postal-code']);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $fakerCard->customer = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $productOne = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 0.20,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productTwo = $this->fakeProduct([
            'price' => 247,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productOneQuantity = 1;

        $this->cartService->addCartItem(
            $productOne['name'],
            $productOne['description'],
            $productOneQuantity,
            $productOne['price'],
            $productOne['is_physical'],
            $productOne['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productOne['id'],
            ]
        );

        $expectedProductOneTotalPrice = $productOne['price'] * $productOneQuantity;

        $expectedProductOneDiscountedPrice = 0;

        $productTwoQuantity = 1;

        $this->cartService->addCartItem(
            $productTwo['name'],
            $productTwo['description'],
            $productTwoQuantity,
            $productTwo['price'],
            $productTwo['is_physical'],
            $productTwo['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $productTwo['id'],
            ]
        );

        $expectedProductTwoTotalPrice = $productTwo['price'] * $productTwoQuantity;

        $expectedProductTwoDiscountedPrice = 0;

        $expectedTotalFromItems = $expectedProductOneTotalPrice + $expectedProductTwoTotalPrice;

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = round($expectedTotalFromItems * $taxRate + $shippingCostAmount * $taxRate, 2);

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService
            ->convertFromBase(round($expectedOrderTotalDue, 2), $currency);

        $expectedConversionRate = $currencyService->getRate($currency);

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => null,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => [
                                'type' => 'address',
                                'id' => $shippingAddress['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $requestData['billing-zip-or-postal-code'],
                            'state' => $requestData['billing-region'],
                            'country' => $requestData['billing-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'id' => $shippingAddress['id'],
                        'attributes' => array_diff_key(
                            $shippingAddress,
                            [
                                'id' => true,
                                'user_id' => true,
                                'customer_id' => true
                            ]
                        ),
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'expiration_date' => null,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoQuantity,
                'expiration_date' => null,
            ]
        );

        // creditCard
        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $fingerPrint,
                'last_four_digits' => $fakerCard->last4,
                'cardholder_name' => '',
                'company_name' => $fakerCard->brand,
                'expiration_date' => Carbon::createFromDate(
                    $fakerCard->exp_year,
                    $fakerCard->exp_month
                )->toDateTimeString(),
                'external_id' => $fakerCard->id,
                'external_customer_id' => $fakerCard->customer,
                'payment_gateway_name' => $requestData['gateway'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // assert payment
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => 'order',
                'external_id' => $fakerCharge->id,
                'external_provider' => 'stripe',
                'status' => 'paid',
                'message' => '',
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_subscription()
    {
        $userId = $this->createAndLogInNewUser();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;
        $currency = $this->getCurrency();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $product = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeSubscription,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $productQuantity = 1;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $product['id'],
            ]
        );

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $requestData = [
            'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card-token' => $cardToken,
            'billing-region' => $this->faker->word,
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_with_discount_based_on_shipping_requirements()
    {
        $userId = $this->createAndLogInNewUser();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;
        $currency = $this->getCurrency();

        $session = $this->app->make(Store::class);

        $session->flush();

        $cartAddressService = $this->app->make(CartAddressService::class);

        $cardToken = $this->faker->word;

        $orderRequestData = [
            'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card-token' => $cardToken,
            'billing-region' => 'ab',
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'shipping-first-name' => $this->faker->firstName,
            'shipping-last-name' => $this->faker->lastName,
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => $this->faker->city,
            'shipping-region' => 'ab',
            'shipping-zip-or-postal-code' => $this->faker->postcode,
            'shipping-country' => 'Canada',
            'currency' => $currency
        ];

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($orderRequestData['billing-country'])
            ->setState($orderRequestData['billing-region'])
            ->setZipOrPostalCode($orderRequestData['billing-zip-or-postal-code']);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 2,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productQuantity = 1;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $product['id'],
            ]
        );

        $discount = $this->fakeDiscount([
            'active' => true,
            'product_id' => $product['id'],
            'type' => 'product amount off',
            'amount' => 10
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id'],
            'product_id' => $product['id'],
            'type' => 'shipping total requirement',
            'min' => '1',
            'max' => '2000',
        ]);

        $expectedProductTotalPrice = $product['price'] * $productQuantity;

        $expectedProductDiscountedPrice = round(
            $expectedProductTotalPrice - ($discount['amount'] * $productQuantity),
            2
        );

        $expectedTotalFromItems = $expectedProductDiscountedPrice;

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = round($expectedTotalFromItems * $taxRate + $shippingCostAmount * $taxRate, 2);

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => null,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $orderRequestData['billing-zip-or-postal-code'],
                            'state' => $orderRequestData['billing-region'],
                            'country' => $orderRequestData['billing-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $orderRequestData['shipping-first-name'],
                            'last_name' => $orderRequestData['shipping-last-name'],
                            'street_line_1' => $orderRequestData['shipping-address-line-1'],
                            'street_line_2' => null,
                            'city' => $orderRequestData['shipping-city'],
                            'zip' => $orderRequestData['shipping-zip-or-postal-code'],
                            'state' => $orderRequestData['shipping-region'],
                            'country' => $orderRequestData['shipping-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'total_due' => round($expectedOrderTotalDue, 2),
                'product_due' => null,
                'taxes_due' => round($expectedTaxes, 2),
                'shipping_due' => $shippingCostAmount,
                'finance_due' => null,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => ConfigService::$brand,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_with_discount_based_on_product_quantity()
    {
        $userId = $this->createAndLogInNewUser();

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $brand = 'drumeo';
        ConfigService::$brand = $brand;
        $currency = $this->getCurrency();

        $session = $this->app->make(Store::class);

        $session->flush();

        $cartAddressService = $this->app->make(CartAddressService::class);

        $cardToken = $this->faker->word;

        $orderRequestData = [
            'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card-token' => $cardToken,
            'billing-region' => 'ab',
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
            'company_name' => $this->faker->creditCardType,
            'gateway' => $brand,
            'shipping-first-name' => $this->faker->firstName,
            'shipping-last-name' => $this->faker->lastName,
            'shipping-address-line-1' => $this->faker->address,
            'shipping-city' => $this->faker->city,
            'shipping-region' => 'ab',
            'shipping-zip-or-postal-code' => $this->faker->postcode,
            'shipping-country' => 'Canada',
            'currency' => $currency
        ];

        $sessionBillingAddress = new Address();

        $sessionBillingAddress
            ->setCountry($orderRequestData['billing-country'])
            ->setState($orderRequestData['billing-region'])
            ->setZipOrPostalCode($orderRequestData['billing-zip-or-postal-code']);

        $cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $shippingOption = $this->fakeShippingOption([
            'country' => 'Canada',
            'active' => 1,
            'priority' => 1,
        ]);

        $shippingCostAmount = 5.50;

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => 0,
            'max' => 10,
            'price' => $shippingCostAmount,
        ]);

        $product = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeProduct,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 1,
            'weight' => 2,
            'subscription_interval_type' => '',
            'subscription_interval_count' => '',
        ]);

        $productQuantity = 2;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $product['id'],
            ]
        );

        $discount = $this->fakeDiscount([
            'active' => true,
            'product_id' => $product['id'],
            'type' => 'product amount off',
            'amount' => 1.4,
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id'],
            'product_id' => $product['id'],
            'type' => 'product quantity requirement',
            'min' => 2,
            'max' => 5,
        ]);

        $expectedProductTotalPrice = $product['price'] * $productQuantity;

        $expectedProductDiscountedPrice = round(
            $expectedProductTotalPrice - ($discount['amount'] * $productQuantity),
            2
        );

        $expectedTotalFromItems = $expectedProductDiscountedPrice;

        $taxService = $this->app->make(TaxService::class);

        $billingAddress = $cartAddressService->getAddress(
                                    CartAddressService::BILLING_ADDRESS_TYPE
                                );

        $taxRate = $taxService->getTaxRate($billingAddress);

        $expectedTaxes = round($expectedTotalFromItems * $taxRate + $shippingCostAmount * $taxRate, 2);

        $expectedOrderTotalDue = round($expectedTotalFromItems + $shippingCostAmount + $expectedTaxes, 2);

        $response = $this->call(
            'PUT',
            '/order',
            $orderRequestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $expectedOrderTotalDue,
                        'product_due' => null,
                        'taxes_due' => $expectedTaxes,
                        'shipping_due' => $shippingCostAmount,
                        'finance_due' => null,
                        'total_paid' => $expectedOrderTotalDue,
                        'brand' => $brand,
                        'created_at' => Carbon::now()->toDateTimeString(),
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId,
                            ]
                        ],
                        'billingAddress' => [
                            'data' => ['type' => 'address']
                        ],
                        'shippingAddress' => [
                            'data' => ['type' => 'address']
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$billingAddressType,
                            'brand' => $brand,
                            'first_name' => null,
                            'last_name' => null,
                            'street_line_1' => null,
                            'street_line_2' => null,
                            'city' => null,
                            'zip' => $orderRequestData['billing-zip-or-postal-code'],
                            'state' => $orderRequestData['billing-region'],
                            'country' => $orderRequestData['billing-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'address',
                        'attributes' => [
                            'type' => ConfigService::$shippingAddressType,
                            'brand' => $brand,
                            'first_name' => $orderRequestData['shipping-first-name'],
                            'last_name' => $orderRequestData['shipping-last-name'],
                            'street_line_1' => $orderRequestData['shipping-address-line-1'],
                            'street_line_2' => null,
                            'city' => $orderRequestData['shipping-city'],
                            'zip' => $orderRequestData['shipping-zip-or-postal-code'],
                            'state' => $orderRequestData['shipping-region'],
                            'country' => $orderRequestData['shipping-country'],
                            'created_at' => Carbon::now()->toDateTimeString(),
                        ],
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId,
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'total_due' => round($expectedOrderTotalDue, 2),
                'product_due' => null,
                'taxes_due' => round($expectedTaxes, 2),
                'shipping_due' => $shippingCostAmount,
                'finance_due' => null,
                'user_id' => $userId,
                'customer_id' => null,
                'brand' => ConfigService::$brand,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_submit_order_subscription_with_discount_free_days()
    {
        $userId = $this->createAndLogInNewUser();

        $brand = 'drumeo';
        ConfigService::$brand = $brand;
        $currency = $this->getCurrency();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $product = $this->fakeProduct([
            'price' => 12.95,
            'type' => ConfigService::$typeSubscription,
            'active' => 1,
            'description' => $this->faker->word,
            'is_physical' => 0,
            'weight' => 0,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $discount = $this->fakeDiscount([
            'active' => true,
            'product_id' => $product['id'],
            'type' => DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE,
            'amount' => 10,
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id'],
            'product_id' => $product['id'],
            'type' => 'date requirement',
            'min' => Carbon::now()->subDay(1),
            'max' => Carbon::now()->addDays(3),
        ]);

        $productQuantity = 1;

        $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $productQuantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $product['id'],
            ]
        );

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $requestData = [
            'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card-token' => $cardToken,
            'billing-region' => $this->faker->word,
            'billing-zip-or-postal-code' => $this->faker->postcode,
            'billing-country' => 'Canada',
            'gateway' => $brand,
        ];

        $response = $this->call(
            'PUT',
            '/order',
            $requestData
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'brand' => $brand,
                'product_id' => $product['id'],
                'user_id' => $userId,
                'is_active' => "1",
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->addDays(10)
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->addDays(10)
                    ->toDateTimeString(),
            ]
        );
    }

    /*
    public function test_submit_order_subscription_with_discount_recurring_amount()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeSubscription,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                    'subscription_interval_count' => 1,
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'product_id' => $product['id'],
                    'type' => DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE,
                    'amount' => 10,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => 'date requirement',
                    'min' => $this->faker->dateTimeInInterval('', '-5days'),
                    'max' => $this->faker->dateTimeInInterval('', '+5days'),
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Canada',
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount days are added to the paid_until data
        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'brand' => ConfigService::$brand,
                'product_id' => $product['id'],
                'user_id' => $userId,
                'is_active' => "1",
                'start_date' => Carbon::now()
                    ->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->toDateTimeString(),
                'total_price_per_payment' => $product['price'] - $discount['amount'],
            ]
        );
    }

    public function test_submit_order_with_discount_order_total_amount()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'type' => DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
                    'amount' => 10,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                    'min' => 5,
                    'max' => 500,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'due' => $product['price'] * $quantity - $discount['amount'],
                'tax' => 0,
                'shipping_costs' => 0,
                'paid' => $product['price'] * $quantity - $discount['amount'],
            ]
        );
    }

    public function test_submit_order_with_discount_order_total_percent()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'type' => DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE,
                    'amount' => 10,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                    'min' => 5,
                    'max' => 500,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'due' => $product['price'] * $quantity - $discount['amount'] / 100 * $product['price'] * $quantity,
                'tax' => 0,
                'shipping_costs' => 0,
                'paid' => $product['price'] * $quantity - $discount['amount'] / 100 * $product['price'] * $quantity,
            ]
        );
    }

    public function test_submit_order_with_discount_product_amount()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'product_id' => $product['id'],
                    'type' => DiscountService::PRODUCT_AMOUNT_OFF_TYPE,
                    'amount' => 10,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                    'min' => 5,
                    'max' => 500,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'due' => ($product['price'] - $discount['amount']) * $quantity,
                'tax' => 0,
                'shipping_costs' => 0,
                'paid' => ($product['price'] - $discount['amount']) * $quantity,
            ]
        );

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'initial_price' => $product['price'],
                'discount' => $discount['amount'] * $quantity,
                'total_price' => ($product['price'] - $discount['amount']) * $quantity,
            ]
        );
    }

    public function test_submit_order_with_discount_product_percent()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'product_id' => $product['id'],
                    'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
                    'amount' => 10,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                    'min' => 5,
                    'max' => 500,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'due' => $product['price'] * $quantity - $discount['amount'] / 100 * $product['price'] * $quantity,
                'tax' => 0,
                'shipping_costs' => 0,
                'paid' => $product['price'] * $quantity - $discount['amount'] / 100 * $product['price'] * $quantity,
            ]
        );

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'initial_price' => $product['price'],
                'discount' => $product['price'] * $quantity * $discount['amount'] / 100,
                'total_price' => ($product['price'] - $product['price'] * $discount['amount'] / 100) * $quantity,
            ]
        );
    }

    public function test_submit_order_with_discount_shipping_costs_amount()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;

        $shippingOption = $this->shippingOptionRepository->create(
            $this->faker->shippingOption(
                [
                    'country' => 'Canada',
                    'active' => 1,
                    'priority' => 1,
                ]
            )
        );
        $shippingCosts = $this->shippingCostsRepository->create(
            $this->faker->shippingCost(
                [
                    'shipping_option_id' => $shippingOption['id'],
                    'min' => 0,
                    'max' => 10,
                    'price' => 5.50,
                ]
            )
        );

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 1,
                    'weight' => 2,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'type' => DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
                    'amount' => 2,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                    'min' => 5,
                    'max' => 500,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
                'shipping-first-name' => $this->faker->firstName,
                'shipping-last-name' => $this->faker->lastName,
                'shipping-address-line-1' => $this->faker->address,
                'shipping-city' => 'Canada',
                'shipping-region' => 'ab',
                'shipping-zip-or-postal-code' => $this->faker->postcode,
                'shipping-country' => 'Canada',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'due' => $product['price'] * $quantity + $shippingCosts['price'] - $discount['amount'],
                'tax' => 0,
                'shipping_costs' => $shippingCosts['price'] - $discount['amount'],
                'paid' => $product['price'] * $quantity + $shippingCosts['price'] - $discount['amount'],
            ]
        );

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'initial_price' => $product['price'],
                'total_price' => $product['price'] * $quantity,
            ]
        );
    }

    public function test_submit_order_with_discount_shipping_costs_percent()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;

        $shippingOption = $this->shippingOptionRepository->create(
            $this->faker->shippingOption(
                [
                    'country' => 'Canada',
                    'active' => 1,
                    'priority' => 1,
                ]
            )
        );
        $shippingCosts = $this->shippingCostsRepository->create(
            $this->faker->shippingCost(
                [
                    'shipping_option_id' => $shippingOption['id'],
                    'min' => 0,
                    'max' => 10,
                    'price' => 5.50,
                ]
            )
        );

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 1,
                    'weight' => 2,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'type' => DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
                    'amount' => 10,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                    'min' => 1,
                    'max' => 500,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
                'shipping-first-name' => $this->faker->firstName,
                'shipping-last-name' => $this->faker->lastName,
                'shipping-address-line-1' => $this->faker->address,
                'shipping-city' => 'Canada',
                'shipping-region' => 'ab',
                'shipping-zip-or-postal-code' => $this->faker->postcode,
                'shipping-country' => 'Canada',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'due' => $product['price'] * $quantity +
                    $shippingCosts['price'] -
                    $discount['amount'] / 100 * $shippingCosts['price'],
                'tax' => 0,
                'shipping_costs' => $shippingCosts['price'] - $discount['amount'] / 100 * $shippingCosts['price'],
                'paid' => $product['price'] * $quantity +
                    $shippingCosts['price'] -
                    $discount['amount'] / 100 * $shippingCosts['price'],
            ]
        );

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'initial_price' => $product['price'],
                'total_price' => $product['price'] * $quantity,
            ]
        );
    }

    public function test_submit_order_with_discount_shipping_costs_overwrite()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;

        $shippingOption = $this->shippingOptionRepository->create(
            $this->faker->shippingOption(
                [
                    'country' => 'Canada',
                    'active' => 1,
                    'priority' => 1,
                ]
            )
        );
        $shippingCosts = $this->shippingCostsRepository->create(
            $this->faker->shippingCost(
                [
                    'shipping_option_id' => $shippingOption['id'],
                    'min' => 0,
                    'max' => 10,
                    'price' => 5.50,
                ]
            )
        );

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 1,
                    'weight' => 2,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'type' => DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
                    'amount' => 10,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                    'min' => 5,
                    'max' => 500,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
                'shipping-first-name' => $this->faker->firstName,
                'shipping-last-name' => $this->faker->lastName,
                'shipping-address-line-1' => $this->faker->address,
                'shipping-city' => 'Canada',
                'shipping-region' => 'ab',
                'shipping-zip-or-postal-code' => $this->faker->postcode,
                'shipping-country' => 'Canada',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'due' => $product['price'] * $quantity + $discount['amount'],
                'tax' => 0,
                'shipping_costs' => $discount['amount'],
                'paid' => $product['price'] * $quantity + $discount['amount'],
            ]
        );

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'initial_price' => $product['price'],
                'total_price' => $product['price'] * $quantity,
            ]
        );
    }

    public function test_customer_submit_order()
    {
        $fingerPrint = '4242424242424242';
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->shippingOptionRepository->create(
            $this->faker->shippingOption(
                [
                    'country' => 'Canada',
                    'active' => 1,
                    'priority' => 1,
                ]
            )
        );
        $shippingCost = $this->shippingCostsRepository->create(
            $this->faker->shippingCost(
                [
                    'shipping_option_id' => $shippingOption['id'],
                    'min' => 0,
                    'max' => 10,
                    'price' => 5.50,
                ]
            )
        );

        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 12.95,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 1,
                    'weight' => 0.20,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 247,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );
        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'amount' => 5,
                    'type' => 'order total amount off',
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product1['id'],
                    'type' => 'order total requirement',
                    'min' => '2',
                    'max' => '2000000',
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $product1['id'],
            ]
        );

        $this->cartService->addCartItem(
            $product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $this->faker->word,
            rand(),
            [
                'product-id' => $product2['id'],
            ]
        );

        $expirationDate = $this->faker->creditCardExpirationDate;
        $billingEmailAddress = $this->faker->email;

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Canada',
                'company_name' => $this->faker->creditCardType,
                'credit-card-year-selector' => $expirationDate->format('Y'),
                'credit-card-month-selector' => $expirationDate->format('m'),
                'credit-card-number' => $fingerPrint,
                'credit-card-cvv' => $this->faker->randomNumber(4),
                'gateway' => 'drumeo',
                'card-token' => '4242424242424242',
                'shipping-first-name' => $this->faker->firstName,
                'shipping-last-name' => $this->faker->lastName,
                'shipping-address-line-1' => $this->faker->address,
                'shipping-city' => 'Canada',
                'shipping-region' => 'ab',
                'shipping-zip-or-postal-code' => $this->faker->postcode,
                'shipping-country' => 'Canada',
                'billing-email' => $billingEmailAddress,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableCustomer,
            [
                'email' => $billingEmailAddress,
                'brand' => ConfigService::$brand,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'user_id' => null,
                'customer_id' => 1,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_submit_order_new_user()
    {
        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $accountCreationMail = $this->faker->email;
        $accountCreationPassword = $this->faker->password;

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
                'shipping-first-name' => $this->faker->firstName,
                'shipping-last-name' => $this->faker->lastName,
                'shipping-address-line-1' => $this->faker->address,
                'shipping-city' => 'Canada',
                'shipping-region' => 'ab',
                'shipping-zip-or-postal-code' => $this->faker->postcode,
                'shipping-country' => 'Canada',
                'account-creation-email' => $accountCreationMail,
                'account-creation-password' => $accountCreationPassword,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => 1,
                'due' => $product['price'] * $quantity,
                'tax' => 0,
                'shipping_costs' => 0,
                'paid' => $product['price'] * $quantity,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => 1,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_invoice_order_send_after_submit()
    {
        Mail::fake();

        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Canada',
                'gateway' => 'drumeo',
                'shipping-first-name' => $this->faker->firstName,
                'shipping-last-name' => $this->faker->lastName,
                'shipping-address-line-1' => $this->faker->address,
                'shipping-city' => 'Canada',
                'shipping-region' => 'ab',
                'shipping-zip-or-postal-code' => $this->faker->postcode,
                'shipping-country' => 'Canada',
            ]
        );

        // Assert a message was sent to the given users...
        Mail::assertSent(
            OrderInvoice::class,
            function ($mail) {
                $mail->build();

                return $mail->hasTo(auth()->user()['email']) &&
                    $mail->hasFrom(config('ecommerce.invoiceSender')) &&
                    $mail->subject(config('ecommerce.invoicerEmailSubject'));
            }
        );

        //assert a mailable was sent
        Mail::assertSent(OrderInvoice::class, 1);

        //assert cart it's empty after submit
        $this->assertEmpty($this->cartService->getAllCartItems());
    }

    public function test_payment_plan()
    {
        $userId = $this->createAndLogInNewUser();
        $fingerPrint = '4242424242424242';
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        // $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->shippingOptionRepository->create(
            $this->faker->shippingOption(
                [
                    'country' => 'Canada',
                    'active' => 1,
                    'priority' => 1,
                ]
            )
        );
        $shippingCost = $this->shippingCostsRepository->create(
            $this->faker->shippingCost(
                [
                    'shipping_option_id' => $shippingOption['id'],
                    'min' => 5,
                    'max' => 10,
                    'price' => 5.50,
                ]
            )
        );

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 247,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'type' => 'product amount off',
                    'amount' => 50,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => 'product quantity requirement',
                    'min' => '1',
                    'max' => '100',
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            1,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $expirationDate = $this->faker->creditCardExpirationDate;
        $paymentPlanOption = $this->faker->randomElement([2, 5]);
        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region' => 'british columbia',
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Canada',
                'company_name' => $this->faker->creditCardType,
                'credit-card-year-selector' => $expirationDate->format('Y'),
                'credit-card-month-selector' => $expirationDate->format('m'),
                'credit-card-number' => $fingerPrint,
                'credit-card-cvv' => $this->faker->randomNumber(4),
                'gateway' => 'drumeo',
                'card-token' => '4242424242424242',
                'shipping-first-name' => $this->faker->firstName,
                'shipping-last-name' => $this->faker->lastName,
                'shipping-address-line-1' => $this->faker->address,
                'shipping-city' => 'Canada',
                'shipping-region' => 'british columbia',
                'shipping-zip-or-postal-code' => $this->faker->postcode,
                'shipping-country' => 'Canada',
                'payment-plan-selector' => $paymentPlanOption,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'type' => ConfigService::$paymentPlanType,
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'start_date' => Carbon::now()
                    ->toDateTimeString(),
                'paid_until' => Carbon::now()
                    ->addMonth(1)
                    ->toDateTimeString(),
                'total_cycles_due' => $paymentPlanOption,
                'total_cycles_paid' => 1,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_multiple_discounts()
    {
        $userId = $this->createAndLogInNewUser();
        $fingerPrint = '4242424242424242';
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        // $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->shippingOptionRepository->create(
            $this->faker->shippingOption(
                [
                    'country' => 'Canada',
                    'active' => 1,
                    'priority' => 1,
                ]
            )
        );
        $shippingCost = $this->shippingCostsRepository->create(
            $this->faker->shippingCost(
                [
                    'shipping_option_id' => $shippingOption['id'],
                    'min' => 5,
                    'max' => 10,
                    'price' => 19,
                ]
            )
        );

        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 147,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 79,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 1,
                    'weight' => 5.10,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );
        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'product_id' => $product1['id'],
                    'type' => 'product amount off',
                    'amount' => 20,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product1['id'],
                    'type' => 'product quantity requirement',
                    'min' => '1',
                    'max' => '100',
                ]
            )
        );
        $discount2 = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'product_id' => $product2['id'],
                    'type' => 'product amount off',
                    'amount' => 20,
                ]
            )
        );
        $discountCriteria2 = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount2['id'],
                    'product_id' => $product2['id'],
                    'type' => 'product quantity requirement',
                    'min' => '1',
                    'max' => '100',
                ]
            )
        );

        $this->cartService->addCartItem(
            $product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $product1['subscription_interval_type'],
            $product1['subscription_interval_count'],
            [
                'product-id' => $product1['id'],
            ]
        );
        $this->cartService->addCartItem(
            $product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $product2['subscription_interval_type'],
            $product2['subscription_interval_count'],
            [
                'product-id' => $product2['id'],
            ]
        );

        $expirationDate = $this->faker->creditCardExpirationDate;

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region' => 'ro',
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romania',
                'company_name' => $this->faker->creditCardType,
                'credit-card-year-selector' => $expirationDate->format('Y'),
                'credit-card-month-selector' => $expirationDate->format('m'),
                'credit-card-number' => $fingerPrint,
                'credit-card-cvv' => $this->faker->randomNumber(4),
                'gateway' => 'drumeo',
                'card-token' => '4242424242424242',
                'shipping-first-name' => $this->faker->firstName,
                'shipping-last-name' => $this->faker->lastName,
                'shipping-address-line-1' => $this->faker->address,
                'shipping-city' => 'Canada',
                'shipping-region' => 'british columbia',
                'shipping-zip-or-postal-code' => $this->faker->postcode,
                'shipping-country' => 'Canada',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'due' => ($product1['price'] -
                    $discount['amount'] +
                    $product2['price'] -
                    $discount2['amount'] +
                    $shippingCost['price']),
                'paid' => ($product1['price'] -
                    $discount['amount'] +
                    $product2['price'] -
                    $discount2['amount'] +
                    $shippingCost['price']),
                'tax' => 0,
                'shipping_costs' => $shippingCost['price'],
            ]
        );
    }

    public function test_prepare_form_order_empty_cart()
    {
        $results = $this->call('GET', '/order');
        $this->assertEquals(404, $results->getStatusCode());
    }

    public function test_prepare_order_form()
    {
        $userId = $this->createAndLogInNewUser();
        $fingerPrint = '4242424242424242';
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $shippingOption = $this->shippingOptionRepository->create(
            $this->faker->shippingOption(
                [
                    'country' => 'Canada',
                    'active' => 1,
                    'priority' => 1,
                ]
            )
        );
        $shippingCost = $this->shippingCostsRepository->create(
            $this->faker->shippingCost(
                [
                    'shipping_option_id' => $shippingOption['id'],
                    'min' => 5,
                    'max' => 10,
                    'price' => 19,
                ]
            )
        );

        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 147,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 79,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 1,
                    'weight' => 5.10,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $this->cartService->addCartItem(
            $product1['name'],
            $product1['description'],
            1,
            $product1['price'],
            $product1['is_physical'],
            $product1['is_physical'],
            $product1['subscription_interval_type'],
            $product1['subscription_interval_count'],
            [
                'product-id' => $product1['id'],
            ]
        );
        $this->cartService->addCartItem(
            $product2['name'],
            $product2['description'],
            1,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $product2['subscription_interval_type'],
            $product2['subscription_interval_count'],
            [
                'product-id' => $product2['id'],
            ]
        );

        $results = $this->call('GET', '/order');

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                [
                    'name' => $product1['name'],
                    'description' => $product1['description'],
                    'price' => $product1['price'],
                    'totalPrice' => $product1['price'],
                    'requiresShippingAddress' => $product1['is_physical'] ?? 0,
                    //'requiresBillinggAddress' => $product1['is_physical'] ?? 0,
                    'options' => [
                        'product-id' => $product1['id'],
                    ],

                ],
                [
                    'name' => $product2['name'],
                    'description' => $product2['description'],
                    'price' => $product2['price'],
                    'totalPrice' => $product2['price'],
                    'requiresShippingAddress' => $product2['is_physical'] ?? 0,
                    // 'requiresBillinggAddress' => $product2['is_physical'] ?? 0,
                    'options' => [
                        'product-id' => $product2['id'],
                    ],

                ],
            ],
            $results->decodeResponseJson('cartItems')
        );

        $tax = 27.12;
        $financeCharge = 1;
        $this->assertEquals($product1['price'] + $product2['price'] + $tax, $results->decodeResponseJson('totalDue'));

    }

    public function test_order_with_promo_code()
    {
        $userId = $this->createAndLogInNewUser();
        $fingerPrint = '4242424242424242';
        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);
        $fakerCustomer = new Customer();
        // $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $fingerPrint;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(3);
        $fakerCard->exp_year = 2020;
        $fakerCard->exp_month = 12;
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = 100;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $product1 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 147,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );
        $promoCode = $this->faker->word;

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'type' => 'order total amount off',
                    'amount' => 10,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product1['id'],
                    'type' => DiscountCriteriaService::PROMO_CODE_REQUIREMENT_TYPE,
                    'min' => $promoCode,
                    'max' => $promoCode,
                ]
            )
        );

        $this->call(
            'GET',
            '/add-to-cart/',
            [
                'products' => [
                    $product1['sku'] => 1,
                ],
                'promo-code' => $promoCode,
            ]
        );
        $expirationDate = $this->faker->creditCardExpirationDate;
        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'billing-region' => 'ro',
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romania',
                'company_name' => $this->faker->creditCardType,
                'credit-card-year-selector' => $expirationDate->format('Y'),
                'credit-card-month-selector' => $expirationDate->format('m'),
                'credit-card-number' => $fingerPrint,
                'credit-card-cvv' => $this->faker->randomNumber(4),
                'gateway' => 'drumeo',
                'card-token' => '4242424242424242',
                'shipping-first-name' => $this->faker->firstName,
                'shipping-last-name' => $this->faker->lastName,
                'shipping-address-line-1' => $this->faker->address,
                'shipping-city' => 'Canada',
                'shipping-region' => 'british columbia',
                'shipping-zip-or-postal-code' => $this->faker->postcode,
                'shipping-country' => 'Canada',
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'tax' => 0,
                'due' => $product1['price'] - $discount['amount'],
                'shipping_costs' => 0,
                'user_id' => $userId,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_user_products_updated_after_order_submit()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $userExistingProducts =
            $this->userProductRepository->query()
                ->create(
                    [
                        'user_id' => $userId,
                        'product_id' => $product['id'],
                        'quantity' => 1,
                        'created_on' => Carbon::now()
                            ->toDateTimeString(),
                    ]
                );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => $userExistingProducts['quantity'] + $quantity,
                'expiration_date' => null,
            ]
        );
    }

    public function test_submit_order_with_discount_product_category()
    {
        $userId = $this->createAndLogInNewUser();

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 2;
        $productCategory = $this->faker->word;
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'category' => $this->faker->word,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );
        $product2 = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 15,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'category' => $productCategory,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => '',
                    'subscription_interval_count' => '',
                ]
            )
        );

        $discount = $this->discountRepository->create(
            $this->faker->discount(
                [
                    'active' => true,
                    'product_id' => $product['id'],
                    'product_category' => $productCategory,
                    'type' => DiscountService::PRODUCT_PERCENT_OFF_TYPE,
                    'amount' => 10,
                ]
            )
        );
        $discountCriteria = $this->discountCriteriaRepository->create(
            $this->faker->discountCriteria(
                [
                    'discount_id' => $discount['id'],
                    'product_id' => $product['id'],
                    'type' => DiscountCriteriaService::ORDER_TOTAL_REQUIREMENT_TYPE,
                    'min' => 5,
                    'max' => 500,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );
        $this->cartService->addCartItem(
            $product2['name'],
            $product2['description'],
            $quantity,
            $product2['price'],
            $product2['is_physical'],
            $product2['is_physical'],
            $product2['subscription_interval_type'],
            $product2['subscription_interval_count'],
            [
                'product-id' => $product2['id'],
            ]
        );

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'due' => $product['price'] * $quantity -
                    $discount['amount'] / 100 * $product['price'] * $quantity +
                    $product2['price'] * $quantity -
                    $discount['amount'] / 100 * $product2['price'] * $quantity,
                'tax' => 0,
                'shipping_costs' => 0,
                'paid' => $product['price'] * $quantity -
                    $discount['amount'] / 100 * $product['price'] * $quantity +
                    $product2['price'] * $quantity -
                    $discount['amount'] / 100 * $product2['price'] * $quantity,
            ]
        );

        //assert the discount amount it's saved in order item data
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'initial_price' => $product['price'],
                'discount' => $product['price'] * $quantity * $discount['amount'] / 100,
                'total_price' => ($product['price'] - $product['price'] * $discount['amount'] / 100) * $quantity,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItem,
            [
                'product_id' => $product2['id'],
                'quantity' => $quantity,
                'initial_price' => $product2['price'],
                'discount' => $product2['price'] * $quantity * $discount['amount'] / 100,
                'total_price' => ($product2['price'] - $product2['price'] * $discount['amount'] / 100) * $quantity,
            ]
        );
    }

    public function test_admin_submit_subscription_for_other_user()
    {
        $this->permissionServiceMock->method('can')
            ->willReturn(true);
        $randomUser = $this->faker->randomNumber(2);
        $brand = $this->faker->word;

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 1;

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeSubscription,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                    'subscription_interval_count' => 1,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ],
            $brand
        );
        $shipping = $this->faker->address(['type' => ConfigService::$shippingAddressType]);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
                'shipping-first-name' => $shipping['first_name'],
                'shipping-last-name' => $shipping['last_name'],
                'shipping-address-line-1' => $shipping['street_line_1'],
                'shipping-city' => $shipping['city'],
                'shipping-region' => $shipping['city'],
                'shipping-zip-or-postal-code' => $shipping['zip'],
                'shipping-country' => $shipping['country'],
                'user_id' => $randomUser,
                'brand' => $brand
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => $brand,
                'user_id' => $randomUser,
                'due' => $product['price'] * $quantity,
                'tax' => 0,
                'shipping_costs' => 0,
                'paid' => $product['price'] * $quantity,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $randomUser,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'user_id' => $randomUser,
                'brand' => $brand,
                'type' => ConfigService::$shippingAddressType,
                'first_name' => $shipping['first_name'],
                'last_name' => $shipping['last_name'],
                'street_line_1' => $shipping['street_line_1'],
                'street_line_2' => $shipping['street_line_2'],
                'city' => $shipping['city'],
                'zip' => $shipping['zip'],
                'country' => $shipping['country'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $randomUser,
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'expiration_date' => Carbon::now()->addYear(1)->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'user_id' => $randomUser,
                'brand' => $brand,
                'type' => ConfigService::$typeSubscription,
                'product_id' => $product['id'],
                'is_active' => 1,
                'start_date' => Carbon::now()->toDateTimeString(),
                'paid_until' => Carbon::now()->addYear(1)->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'total_cycles_paid' => 1,
                'interval_type' => $product['subscription_interval_type'],
                'interval_count' => $product['subscription_interval_count'],
                'total_price_per_payment' => $product['price'],
                'canceled_on' => null
            ]
        );
    }

    public function test_admin_submit_product_for_other_user()
    {
        $this->permissionServiceMock->method('can')
            ->willReturn(true);
        $randomUser = $this->faker->randomNumber(2);
        $brand = $this->faker->word;

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 1;

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => null,
                    'subscription_interval_count' => null,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ]
        );
        $shipping = $this->faker->address(['type' => ConfigService::$shippingAddressType]);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
                'shipping-first-name' => $shipping['first_name'],
                'shipping-last-name' => $shipping['last_name'],
                'shipping-address-line-1' => $shipping['street_line_1'],
                'shipping-city' => $shipping['city'],
                'shipping-region' => $shipping['city'],
                'shipping-zip-or-postal-code' => $shipping['zip'],
                'shipping-country' => $shipping['country'],
                'user_id' => $randomUser
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => ConfigService::$brand,
                'user_id' => $randomUser,
                'due' => $product['price'] * $quantity,
                'tax' => 0,
                'shipping_costs' => 0,
                'paid' => $product['price'] * $quantity,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $randomUser,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'user_id' => $randomUser,
                'brand' => ConfigService::$brand,
                'type' => ConfigService::$shippingAddressType,
                'first_name' => $shipping['first_name'],
                'last_name' => $shipping['last_name'],
                'street_line_1' => $shipping['street_line_1'],
                'street_line_2' => $shipping['street_line_2'],
                'city' => $shipping['city'],
                'zip' => $shipping['zip'],
                'country' => $shipping['country'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $randomUser,
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'expiration_date' => null,
                'created_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_admin_submit_order_on_different_branch()
    {
        $this->permissionServiceMock->method('can')
            ->willReturn(true);
        $randomUser = $this->faker->randomNumber(2);
        $brand = $this->faker->word;

        $cardToken = $this->faker->word;

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => '']);

        $fakerCustomer = new Customer();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $fakerCharge = new Charge();

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $fakerToken = new Token();

        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $quantity = 1;

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'price' => 25,
                    'type' => ConfigService::$typeProduct,
                    'active' => 1,
                    'description' => $this->faker->word,
                    'is_physical' => 0,
                    'weight' => 0,
                    'subscription_interval_type' => null,
                    'subscription_interval_count' => null,
                ]
            )
        );

        $cart = $this->cartService->addCartItem(
            $product['name'],
            $product['description'],
            $quantity,
            $product['price'],
            $product['is_physical'],
            $product['is_physical'],
            $product['subscription_interval_type'],
            $product['subscription_interval_count'],
            [
                'product-id' => $product['id'],
            ],
            $brand
        );
        $shipping = $this->faker->address(['type' => ConfigService::$shippingAddressType]);

        $results = $this->call(
            'PUT',
            '/order',
            [
                'payment_method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card-token' => $cardToken,
                'billing-region' => $this->faker->word,
                'billing-zip-or-postal-code' => $this->faker->postcode,
                'billing-country' => 'Romanian',
                'gateway' => 'drumeo',
                'shipping-first-name' => $shipping['first_name'],
                'shipping-last-name' => $shipping['last_name'],
                'shipping-address-line-1' => $shipping['street_line_1'],
                'shipping-city' => $shipping['city'],
                'shipping-region' => $shipping['city'],
                'shipping-zip-or-postal-code' => $shipping['zip'],
                'shipping-country' => $shipping['country'],
                'user_id' => $randomUser,
                'brand' => $brand
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        //assert the discount amount it's included in order due
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            [
                'brand' => $brand,
                'user_id' => $randomUser,
                'due' => $product['price'] * $quantity,
                'tax' => 0,
                'shipping_costs' => 0,
                'paid' => $product['price'] * $quantity,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $randomUser,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'user_id' => $randomUser,
                'brand' => $brand,
                'type' => ConfigService::$shippingAddressType,
                'first_name' => $shipping['first_name'],
                'last_name' => $shipping['last_name'],
                'street_line_1' => $shipping['street_line_1'],
                'street_line_2' => $shipping['street_line_2'],
                'city' => $shipping['city'],
                'zip' => $shipping['zip'],
                'country' => $shipping['country'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $randomUser,
                'product_id' => $product['id'],
                'quantity' => $quantity,
                'expiration_date' => null,
                'created_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }
    */
}
