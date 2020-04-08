<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Controllers\PaymentJsonController;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Mail\SubscriptionInvoice;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;

class PaymentJsonControllerTest extends EcommerceTestCase
{
    protected $currencyService;

    protected function setUp()
    {
        parent::setUp();

        $this->currencyService = $this->app->make(CurrencyService::class);
    }

    public function test_user_store_payment()
    {
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';

        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $creditCard = $this->fakeCreditCard(
            [
                'payment_gateway_name' => $gateway
            ]
        );

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
            'country' => 'Canada',
            'region' => 'alberta',
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $productTax = $this->faker->numberBetween(1, 10);
        $shippingTax = $this->faker->numberBetween(1, 10);

        $expectedTaxRateProduct = config('ecommerce.product_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];
        $expectedTaxRateShipping = config('ecommerce.shipping_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $productTax,
                        'shipping_tax' => $shippingTax,
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'total_due' => $due,
                        'total_paid' => $due,
                        'total_refunded' => 0,
                        'type' => Payment::TYPE_INITIAL_ORDER,
                        'external_provider' => 'stripe',
                        'status' => Payment::STATUS_PAID,
                        'message' => '',
                        'currency' => $currency,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                ],
            ],
            $response->decodeResponseJson()
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => 0,
                'type' => config('ecommerce.order_payment_type'),
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'conversion_rate' => $conversionRate,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $address['country'],
                'region' => $address['region'],
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => $productTax,
                'shipping_taxes_paid' => $shippingTax,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $gateway,
                'resource_name' => Payment::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_USER,
            ]
        );
    }

    public function test_user_store_payment_order_total_paid()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $alreadyPaid = 100;

        $order = $this->fakeOrder([
            'total_paid' => $alreadyPaid * $conversionRate
        ]);

        $payment = $this->fakePayment([
            'total_paid' => $alreadyPaid,
            'total_refunded' => null,
            'deleted_at' => null,
            'conversion_rate' => $conversionRate,
        ]);

        $orderPayment = $this->fakeOrderPayment([
            'order_id' => $order['id'],
            'payment_id' => $payment['id'],
        ]);

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
            'country' => 'Canada',
            'region' => 'alberta',
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id'],
            'currency' => $currency
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $productTax = $this->faker->numberBetween(1, 10);
        $shippingTax = $this->faker->numberBetween(1, 10);

        $expectedTaxRateProduct = config('ecommerce.product_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];
        $expectedTaxRateShipping = config('ecommerce.shipping_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $productTax,
                        'shipping_tax' => $shippingTax,
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => $order['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'total_due' => $due,
                        'total_paid' => $due,
                        'total_refunded' => 0,
                        'type' => Payment::TYPE_INITIAL_ORDER,
                        'external_provider' => 'stripe',
                        'status' => Payment::STATUS_PAID,
                        'message' => null,
                        'currency' => $currency,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                ],
            ],
            $decodedResponse
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => 0,
                'type' => config('ecommerce.order_payment_type'),
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'conversion_rate' => $conversionRate,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $address['country'],
                'region' => $address['region'],
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => $productTax,
                'shipping_taxes_paid' => $shippingTax,
            ]
        );

        // assert payment is linked to order
        $this->assertDatabaseHas(
            'ecommerce_order_payments',
            [
                'order_id' => $order['id'],
                'payment_id' => $decodedResponse['data']['id'],
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $expectedSum = $alreadyPaid * $conversionRate + $due * $conversionRate;

        // assert order total paied sum
        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'id' => $order['id'],
                'total_paid' => $expectedSum
            ]
        );
    }

    public function test_user_store_paypal_payment()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['paypal'])
        );

        $this->paypalExternalHelperMock
            ->method('createReferenceTransaction')
            ->willReturn(rand());

        $paypalAgreement = $this->fakePaypalBillingAgreement();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
            'country' => 'Canada',
            'region' => 'alberta',
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'paypal_billing_agreement_id' => $paypalAgreement['id'],
            'billing_address_id' => $address['id'],
            'currency' => $currency
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]);

        $productTax = $this->faker->numberBetween(1, 10);
        $shippingTax = $this->faker->numberBetween(1, 10);

        $expectedTaxRateProduct = config('ecommerce.product_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];
        $expectedTaxRateShipping = config('ecommerce.shipping_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $productTax,
                        'shipping_tax' => $shippingTax,
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'total_due' => $due,
                        'total_paid' => $due,
                        'total_refunded' => 0,
                        'type' => config('ecommerce.order_payment_type'),
                        'external_provider' => 'paypal',
                        'status' => Payment::STATUS_PAID,
                        'message' => null,
                        'currency' => $currency,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                ],
            ],
            $decodedResponse
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => 0,
                'type' => config('ecommerce.order_payment_type'),
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'paypal',
                'currency' => $currency,
                'conversion_rate' => $conversionRate,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $address['country'],
                'region' => $address['region'],
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => $productTax,
                'shipping_taxes_paid' => $shippingTax,
            ]
        );
    }

    public function test_admin_store_any_payment()
    {
        $due = $this->faker->numberBetween(0, 1000);
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);
        $customer = $this->fakeUser();
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $this->permissionServiceMock->method('can')->willReturn(true);

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $creditCard = $this->fakeCreditCard(
            [
                'payment_gateway_name' => $gateway
            ]
        );

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
            'country' => 'Canada',
            'region' => 'alberta',
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $productTax = $this->faker->numberBetween(1, 10);
        $shippingTax = $this->faker->numberBetween(1, 10);

        $expectedTaxRateProduct = config('ecommerce.product_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];
        $expectedTaxRateShipping = config('ecommerce.shipping_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $productTax,
                        'shipping_tax' => $shippingTax,
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'total_due' => $due,
                        'total_paid' => $due,
                        'total_refunded' => 0,
                        'type' => Payment::TYPE_INITIAL_ORDER,
                        'external_provider' => 'stripe',
                        'status' => Payment::STATUS_PAID,
                        'message' => null,
                        'currency' => $currency,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                ],
            ],
            $response->decodeResponseJson()
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => 0,
                'type' => config('ecommerce.order_payment_type'),
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'conversion_rate' => $conversionRate,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $address['country'],
                'region' => $address['region'],
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => $productTax,
                'shipping_taxes_paid' => $shippingTax,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $gateway,
                'resource_name' => Payment::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_ADMIN,
            ]
        );
    }

    public function test_admin_store_payment_without_payment_method()
    {
        $due = $this->faker->numberBetween(0, 1000);
        $admin = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $this->permissionServiceMock->method('can')->willReturn(true);

        $productTax = $this->faker->numberBetween(1, 10);
        $shippingTax = 0;

        $user = $this->fakeUser();

        $cycles = $this->faker->numberBetween(0, 10);

        $subscription = $this->fakeSubscription([
            'total_cycles_paid' => $cycles,
            'paid_until' => Carbon::now()->subDays(5)->toDateTimeString(),
            'interval_type' => PaymentJsonController::INTERVAL_TYPE_MONTHLY,
            'interval_count' => 1,
            'user_id' => $user['id'],
        ]);

        // $response = $this->call(
        //     'PUT',
        //     '/payment',
        //     [
        //         'data' => [
        //             'type' => 'payment',
        //             'attributes' => [
        //                 'due' => $due,
        //                 'currency' => $currency,
        //                 'product_tax' => $productTax,
        //                 'shipping_tax' => $shippingTax,
        //             ],
        //             'relationships' => [
        //                 'subscription' => [
        //                     'data' => [
        //                         'type' => 'subscription',
        //                         'id' => $subscription['id']
        //                     ]
        //                 ]
        //             ]
        //         ]
        //     ]
        // );

        // todo - update controller & test

        $this->assertTrue(true);
    }

    public function test_user_can_not_store_other_payment()
    {
        $this->createAndLogInNewUser();

        $due = $this->faker->numberBetween(0, 1000);
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $currency = $this->getCurrency();
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $user = $this->fakeUser();

        $creditCard = $this->fakeCreditCard();

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $user['id'],
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $this->faker->numberBetween(1, 10),
                        'shipping_tax' => $this->faker->numberBetween(1, 10),
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'This action is unauthorized.',
            ],
            $response->decodeResponseJson('error')
        );
    }

    public function test_user_store_payment_invalid_order_id()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $currency = $this->getCurrency();
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => rand(),
        ]);

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $this->faker->numberBetween(1, 10),
                        'shipping_tax' => $this->faker->numberBetween(1, 10),
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => rand()
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            [
                [
                    'title' => 'Validation failed.',
                    'source' => 'data.relationships.order.data.id',
                    'detail' => 'The selected order is invalid.'
                ]
            ],
            $response->decodeResponseJson('errors')
        );
    }

    public function test_user_store_payment_invalid_subscription_id()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $currency = $this->getCurrency();
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => rand(),
        ]);

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $this->faker->numberBetween(1, 10),
                        'shipping_tax' => $this->faker->numberBetween(1, 10),
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ],
                        'subscription' => [
                            'data' => [
                                'type' => 'subscription',
                                'id' => rand()
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            [
                [
                    'title' => 'Validation failed.',
                    'source' => 'data.relationships.subscription.data.id',
                    'detail' => 'The selected subscription is invalid.'
                ]
            ],
            $response->decodeResponseJson('errors')
        );
    }

    public function test_user_store_payment_renew_subscription()
    {
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );
        $cycles = 1;

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->id = $this->faker->word;
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $subscription = $this->fakeSubscription([
            'total_cycles_paid' => $cycles,
            'paid_until' => Carbon::now()->subDays(5)->toDateTimeString(),
            'interval_type' => PaymentJsonController::INTERVAL_TYPE_MONTHLY,
            'interval_count' => 1,
            'user_id' => $userId,
            'brand' => $gateway
        ]);

        $creditCard = $this->fakeCreditCard(
            [
                'payment_gateway_name' => $gateway
            ]
        );

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
            'country' => 'Canada',
            'region' => 'alberta',
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $productTax = $this->faker->numberBetween(1, 10);
        $shippingTax = $this->faker->numberBetween(1, 10);

        $expectedTaxRateProduct = config('ecommerce.product_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];
        $expectedTaxRateShipping = config('ecommerce.shipping_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $productTax,
                        'shipping_tax' => $shippingTax,
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ],
                        'subscription' => [
                            'data' => [
                                'type' => 'subscription',
                                'id' => $subscription['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'total_due' => $due,
                        'total_paid' => $due,
                        'total_refunded' => 0,
                        'type' => config('ecommerce.renewal_payment_type'),
                        'external_provider' => 'stripe',
                        'status' => Payment::STATUS_PAID,
                        'message' => null,
                        'currency' => $currency,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                ],
            ],
            $decodedResponse
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => 0,
                'type' => config('ecommerce.renewal_payment_type'),
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $address['country'],
                'region' => $address['region'],
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => $productTax,
                'shipping_taxes_paid' => $shippingTax,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscription['id'],
                'total_cycles_paid' => $cycles + 1,
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscription['id'],
                'total_cycles_paid' => $cycles + 1,
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => $subscription['id'],
                'payment_id' => $decodedResponse['data']['id']
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $gateway,
                'resource_name' => Payment::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_USER,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $gateway,
                'resource_name' => Subscription::class,
                'resource_id' => 1,
                'action_name' => Subscription::ACTION_RENEW,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_USER,
            ]
        );
    }

    public function test_user_store_paypal_payment_transaction_failed()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $message = 'transaction failed';

        $this->paypalExternalHelperMock
            ->method('createReferenceTransaction')
            ->willThrowException(
                new PaymentFailedException($message)
            );

        $paypalAgreement = $this->fakePaypalBillingAgreement();

        $paymentMethod = $this->fakePaymentMethod([
            'paypal_billing_agreement_id' => $paypalAgreement['id'],
            'currency' => $currency
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]);

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $this->faker->numberBetween(1, 10),
                        'shipping_tax' => $this->faker->numberBetween(1, 10),
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            [
                [
                    'title' => 'Payment failed.',
                    'detail' => 'Payment failed: ' . $message
                ]
            ],
            $response->decodeResponseJson('errors')
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $due,
                'total_paid' => 0,
                'total_refunded' => 0,
                'type' => config('ecommerce.renewal_payment_type'),
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'paypal',
                'currency' => $currency,
                'status' => 'failed',
                'type' => config('ecommerce.order_payment_type'),
                'message' => 'Payment failed: ' . $message,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    public function test_user_store_credit_card_charge_failed()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $message = 'charge failed';

        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $this->stripeExternalHelperMock
            ->method('chargeCard')
            ->willThrowException(
                new PaymentFailedException($message)
            );

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway,
                        'product_tax' => $this->faker->numberBetween(1, 10),
                        'shipping_tax' => $this->faker->numberBetween(1, 10),
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals(
            [
                [
                    'title' => 'Payment failed.',
                    'detail' => 'Payment failed: ' . $message
                ]
            ],
            $response->decodeResponseJson('errors')
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $due,
                'total_paid' => 0,
                'total_refunded' => 0,
                'type' => config('ecommerce.renewal_payment_type'),
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'status' => 'failed',
                'type' => config('ecommerce.order_payment_type'),
                'message' => 'Payment failed: ' . $message,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    public function test_index_by_order()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $order = $this->fakeOrder();

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $expected = ['data' => []];

        for ($i = 0; $i < 5; $i++) {

            $due = $this->faker->numberBetween(0, 1000);

            $payment = $this->fakePayment([
                'total_paid' => $due,
                'payment_method_id' => $paymentMethod['id'],
                'total_refunded' => null,
                'deleted_at' => null
            ]);

            $expected['data'][] = [
                'type' => 'payment',
                'id' => $payment['id'],
                'attributes' => array_merge(
                    array_diff_key(
                        $payment,
                        [
                            'id' => true,
                            'payment_method_id' => true,
                        ]
                    ),
                    [
                        'deleted_at' => null,
                        'updated_at' => null,
                    ]
                ),
                'relationships' => [
                    'paymentMethod' => [
                        'data' => [
                            'type' => 'paymentMethod',
                            'id' => '1',
                        ]
                    ]
                ]
            ];

            $orderPayment = $this->fakeOrderPayment([
                'order_id' => $order['id'],
                'payment_id' => $payment['id'],
            ]);
        }

        $otherOrder = $this->fakeOrder();

        $otherPayment = $this->fakePayment([
            'total_paid' => $this->faker->numberBetween(0, 1000),
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null
        ]);

        $orderPayment = $this->fakeOrderPayment([
            'order_id' => $otherOrder['id'],
            'payment_id' => $otherPayment['id'],
        ]);

        // soft-deleted order and payment, should not be returned in response
        $deletedOrder = $this->fakeOrder(
            [
                'deleted_at' => Carbon::now(),
            ]
        );

        $deletedPayment = $this->fakePayment([
            'total_paid' => $this->faker->numberBetween(0, 1000),
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => Carbon::now(),
        ]);

        $deletedOrderPayment = $this->fakeOrderPayment([
            'order_id' => $otherOrder['id'],
            'payment_id' => $otherPayment['id'],
        ]);

        $response = $this->call(
            'GET',
            '/payment',
            [
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'limit' => 10,
                'order_id' => $order['id']
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            $expected['data'],
            $decodedResponse['data']
        );
    }

    public function test_admin_index_by_order_include_soft_deleted()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $this->permissionServiceMock->method('can')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $order = $this->fakeOrder();

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $expected = ['data' => []];

        for ($i = 0; $i < 5; $i++) {

            $due = $this->faker->numberBetween(0, 1000);

            $payment = $this->fakePayment([
                'total_paid' => $due,
                'payment_method_id' => $paymentMethod['id'],
                'total_refunded' => null,
                'deleted_at' => null
            ]);

            $expected['data'][] = [
                'type' => 'payment',
                'id' => $payment['id'],
                'attributes' => array_merge(
                    array_diff_key(
                        $payment,
                        [
                            'id' => true,
                            'payment_method_id' => true,
                        ]
                    ),
                    [
                        'deleted_at' => null,
                        'updated_at' => null,
                    ]
                ),
                'relationships' => [
                    'paymentMethod' => [
                        'data' => [
                            'type' => 'paymentMethod',
                            'id' => '1',
                        ]
                    ]
                ]
            ];

            $orderPayment = $this->fakeOrderPayment([
                'order_id' => $order['id'],
                'payment_id' => $payment['id'],
            ]);
        }

        $otherOrder = $this->fakeOrder();

        $otherPayment = $this->fakePayment([
            'total_paid' => $this->faker->numberBetween(0, 1000),
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null
        ]);

        $orderPayment = $this->fakeOrderPayment([
            'order_id' => $otherOrder['id'],
            'payment_id' => $otherPayment['id'],
        ]);

        // soft-deleted payment, should be returned in response
        $deletedPayment = $this->fakePayment([
            'total_paid' => $this->faker->numberBetween(0, 1000),
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => Carbon::now(),
        ]);

        $orderPayment = $this->fakeOrderPayment([
            'order_id' => $order['id'],
            'payment_id' => $deletedPayment['id'],
        ]);

        $expected['data'][] = [
            'type' => 'payment',
            'id' => $deletedPayment['id'],
            'attributes' => array_merge(
                array_diff_key(
                    $deletedPayment,
                    [
                        'id' => true,
                        'payment_method_id' => true,
                    ]
                ),
                [
                    'updated_at' => null,
                ]
            ),
            'relationships' => [
                'paymentMethod' => [
                    'data' => [
                        'type' => 'paymentMethod',
                        'id' => '1',
                    ]
                ]
            ]
        ];

        $response = $this->call(
            'GET',
            '/payment',
            [
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'limit' => 10,
                'order_id' => $order['id'],
                'view_deleted' => true,
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            $expected['data'],
            $decodedResponse['data']
        );
    }

    public function test_index_by_subscription()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $cycles = 1;

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $subscription = $this->fakeSubscription([
            'total_cycles_paid' => $cycles,
            'paid_until' => Carbon::now()->subDays(5)->toDateTimeString(),
            'interval_type' => PaymentJsonController::INTERVAL_TYPE_MONTHLY,
            'interval_count' => 1,
        ]);

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $expected = ['data' => []];

        for ($i = 0; $i < 5; $i++) {

            $due = $this->faker->numberBetween(0, 1000);

            $payment = $this->fakePayment([
                'total_paid' => $due,
                'payment_method_id' => $paymentMethod['id'],
                'total_refunded' => null,
                'deleted_at' => null
            ]);

            $expected['data'][] = [
                'type' => 'payment',
                'id' => $payment['id'],
                'attributes' => array_diff_key(
                    $payment,
                    [
                        'id' => true,
                        'payment_method_id' => true
                    ]
                )
            ];

            $subscriptionPayment = $this->fakeSubscriptionPayment([
                'subscription_id' => $subscription['id'],
                'payment_id' => $payment['id'],
            ]);
        }

        $otherSubscription = $this->fakeSubscription([
            'total_cycles_paid' => $cycles,
            'paid_until' => Carbon::now()->subDays(5)->toDateTimeString(),
            'interval_type' => PaymentJsonController::INTERVAL_TYPE_MONTHLY,
            'interval_count' => 1,
        ]);

        $otherPayment = $this->fakePayment([
            'total_paid' => $this->faker->numberBetween(0, 1000),
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null
        ]);

        $subscriptionPayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $otherSubscription['id'],
            'payment_id' => $otherPayment['id'],
        ]);

        $response = $this->call(
            'GET',
            '/payment',
            [
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'limit' => 10,
                'subscription_id' => $subscription['id']
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertArraySubset(
            $expected,
            $decodedResponse
        );
    }

    public function test_delete_payment()
    {
        $payment = $this->fakePayment();

        $results = $this->call('DELETE', '/payment/' . $payment['id']);

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertSoftDeleted(
            'ecommerce_payments',
            [
                'id' => $payment['id']
            ]
        );
    }

    public function test_delete_not_existing_payment()
    {
        $randomId = $this->faker->randomNumber();

        $results = $this->call('DELETE', '/payment/' . $randomId);

        // assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                [
                    'title' => 'Not found.',
                    'detail' => 'Delete failed, payment not found with id: ' . $randomId,
                ]
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_send_subscription_payment_invoice()
    {
        $brand = 'brand';
        config()->set('ecommerce.brand', $brand);

        Mail::fake();

        $email = $this->faker->email;
        $userId  = $this->createAndLogInNewUser($email);

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $subscription = $this->fakeSubscription([
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
        ]);

        $payment = $this->fakePayment([
            'total_paid' => $this->faker->numberBetween(0, 1000),
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null,
            'gateway_name' => $brand,
        ]);

        $subscriptionPayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscription['id'],
            'payment_id' => $payment['id'],
        ]);

        $response = $this->call('PUT', '/send-invoice/' . $payment['id'], []);

        $this->assertEquals(204, $response->getStatusCode());

        Mail::assertSent(SubscriptionInvoice::class, 1);

        Mail::assertSent(
            SubscriptionInvoice::class,
            function ($mail) use ($email) {
                $mail->build();

                return $mail->hasTo($email) &&
                    $mail->hasFrom(config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_sender')) &&
                    $mail->subject(
                        config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_email_subject')
                    );
            }
        );
    }

    public function test_send_order_payment_invoice()
    {
        $brand = 'brand';
        config()->set('ecommerce.brand', $brand);

        Mail::fake();

        $email = $this->faker->email;
        $userId  = $this->createAndLogInNewUser($email);

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => Address::SHIPPING_ADDRESS_TYPE,
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $order = $this->fakeOrder(
            [
                'shipping_address_id' => $address['id'],
                'user_id' => $userId,
            ]
        );

        $payment = $this->fakePayment([
            'total_paid' => $this->faker->numberBetween(0, 1000),
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null,
            'gateway_name' => $brand,
        ]);

        $orderPayment = $this->fakeOrderPayment([
            'order_id' => $order['id'],
            'payment_id' => $payment['id'],
        ]);

        $response = $this->call('PUT', '/send-invoice/' . $payment['id'], []);

        $this->assertEquals(204, $response->getStatusCode());

        Mail::assertSent(OrderInvoice::class, 1);

        Mail::assertSent(
            OrderInvoice::class,
            function ($mail) use ($email) {
                $mail->build();

                return $mail->hasTo($email) &&
                    $mail->hasFrom(config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_sender')) &&
                    $mail->subject(
                        config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_email_subject')
                    );
            }
        );
    }
}
