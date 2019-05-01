<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Controllers\PaymentJsonController;
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
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
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
                        'payment_gateway' => $gateway
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
                        'total_refunded' => null,
                        'type' => 'order',
                        'external_provider' => 'stripe',
                        'status' => '1',
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
            ConfigService::$tablePayment,
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => null,
                'type' => ConfigService::$orderPaymentType,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'conversion_rate' => $conversionRate,
                'status' => '1',
                'message' => '',
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
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
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
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
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
            'currency' => $currency
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
                        'payment_gateway' => $gateway
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
                        'total_refunded' => null,
                        'type' => 'order',
                        'external_provider' => 'stripe',
                        'status' => '1',
                        'message' => '',
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
            ConfigService::$tablePayment,
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => null,
                'type' => ConfigService::$orderPaymentType,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'conversion_rate' => $conversionRate,
                'status' => '1',
                'message' => '',
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // assert payment is linked to order
        $this->assertDatabaseHas(
            ConfigService::$tableOrderPayment,
            [
                'order_id' => $order['id'],
                'payment_id' => $decodedResponse['data']['id'],
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $expectedSum = $alreadyPaid * $conversionRate + $due * $conversionRate;

        // assert order total paied sum
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
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
            array_keys(ConfigService::$paymentGateways['paypal'])
        );

        $this->paypalExternalHelperMock
            ->method('createReferenceTransaction')
            ->willReturn(rand());

        $paypalAgreement = $this->fakePaypalBillingAgreement();

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $paypalAgreement['id'],
            'method_type' => PaymentMethod::TYPE_PAYPAL,
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
                        'payment_gateway' => $gateway
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
                        'total_refunded' => null,
                        'type' => ConfigService::$orderPaymentType,
                        'external_provider' => 'paypal',
                        'status' => '1',
                        'message' => '',
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
            ConfigService::$tablePayment,
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => null,
                'type' => ConfigService::$orderPaymentType,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'paypal',
                'currency' => $currency,
                'conversion_rate' => $conversionRate,
                'status' => '1',
                'message' => '',
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    public function test_admin_store_any_payment()
    {
        $due = $this->faker->numberBetween(0, 1000);
        $userId = $this->createAndLogInNewUser();
        $customer = $this->fakeUser();
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $this->permissionServiceMock->method('can')->willReturn(true);

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
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
                        'payment_gateway' => $gateway
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
                        'total_refunded' => null,
                        'type' => 'order',
                        'external_provider' => 'stripe',
                        'status' => '1',
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
            ConfigService::$tablePayment,
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => null,
                'type' => ConfigService::$orderPaymentType,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'conversion_rate' => $conversionRate,
                'status' => '1',
                'message' => '',
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    public function test_user_can_not_store_other_payment()
    {
        $this->createAndLogInNewUser();

        $due = $this->faker->numberBetween(0, 1000);
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $currency = $this->getCurrency();
        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $user = $this->fakeUser();

        $creditCard = $this->fakeCreditCard();

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
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
                        'payment_gateway' => $gateway
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
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => rand(),
            'method_type' => $methodType,
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
                        'payment_gateway' => $gateway
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
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => rand(),
            'method_type' => $methodType,
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
                        'payment_gateway' => $gateway
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
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );
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
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
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
                        'payment_gateway' => $gateway
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
                        'total_refunded' => null,
                        'type' => ConfigService::$renewalPaymentType,
                        'external_provider' => 'stripe',
                        'status' => '1',
                        'message' => '',
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
            ConfigService::$tablePayment,
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => null,
                'type' => ConfigService::$renewalPaymentType,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'status' => '1',
                'message' => '',
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'id' => $subscription['id'],
                'total_cycles_paid' => $cycles + 1,
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'id' => $subscription['id'],
                'total_cycles_paid' => $cycles + 1,
                'paid_until' => Carbon::now()->addMonth(1)->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscriptionPayment,
            [
                'subscription_id' => $subscription['id'],
                'payment_id' => $decodedResponse['data']['id']
            ]
        );
    }

    public function test_user_store_paypal_payment_transaction_failed()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $message = 'transaction failed';

        $this->paypalExternalHelperMock
            ->method('createReferenceTransaction')
            ->willThrowException(
                new PaymentFailedException($message)
            );

        $paypalAgreement = $this->fakePaypalBillingAgreement();

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $paypalAgreement['id'],
            'method_type' => PaymentMethod::TYPE_PAYPAL,
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
                        'payment_gateway' => $gateway
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
                'title' => 'Payment failed.',
                'detail' => 'Payment failed: ' . $message
            ],
            $response->decodeResponseJson('errors')
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => $due,
                'total_paid' => 0,
                'total_refunded' => null,
                'type' => ConfigService::$renewalPaymentType,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'paypal',
                'currency' => $currency,
                'status' => 'failed',
                'type' => ConfigService::$orderPaymentType,
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
            array_keys(ConfigService::$paymentGateways['stripe'])
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
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
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
                        'payment_gateway' => $gateway
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
                'title' => 'Payment failed.',
                'detail' => 'Payment failed: ' . $message
            ],
            $response->decodeResponseJson('errors')
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => $due,
                'total_paid' => 0,
                'total_refunded' => null,
                'type' => ConfigService::$renewalPaymentType,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'status' => 'failed',
                'type' => ConfigService::$orderPaymentType,
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
            array_keys(ConfigService::$paymentGateways['stripe'])
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
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
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

        $this->assertArraySubset(
            $expected,
            $decodedResponse
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
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
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
            ConfigService::$tablePayment,
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
                'title' => 'Not found.',
                'detail' => 'Delete failed, payment not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson('errors')
        );
    }
}
