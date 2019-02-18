<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class RefundJsonControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->currencyService = $this->app->make(CurrencyService::class);
    }

    public function test_store_validation() // ok
    {
        $this->permissionServiceMock->method('canOrThrow');

        $response = $this->call(
            'PUT',
            '/refund',
            []
        );

        $this->assertEquals(422, $response->getStatusCode());

        $this->assertEquals(
            [
                [
                    'title' => 'Validation failed.',
                    'source' => 'data.attributes.refund_amount',
                    'detail' => 'The refund amount field is required.',
                ],
                [
                    'title' => 'Validation failed.',
                    'source' => 'data.attributes.gateway_name',
                    'detail' => 'The gateway name field is required.',
                ],
                [
                    'title' => 'Validation failed.',
                    'source' => 'data.relationships.payment.data.id',
                    'detail' => 'The payment field is required.',
                ]
            ],
            $response->decodeResponseJson('errors')
        );
    }

    public function test_user_create_own_refund_credit_card() // ok
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );
        $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;
        $refund = new \stdClass();
        $refund->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createRefund')
            ->willReturn($refund);
        $due = $this->faker->numberBetween(11, 1000);

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

        $refundAmount = $due - 10;

        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'external_id' => $this->faker->word,
            'currency' => $currency,
            'total_due' => $due,
            'external_provider' => 'stripe',
            'total_refunded' => 0,
            'conversion_rate' => $conversionRate
        ]);

        $response = $this->call(
            'PUT',
            '/refund',
            [
                'data' => [
                    'type' => 'refund',
                    'attributes' => [
                        'refund_amount' => $refundAmount,
                        'gateway_name' => $gateway,
                    ],
                    'relationships' => [
                        'payment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'refund',
                    'attributes' => [
                        'payment_amount' => $due,
                        'refunded_amount' => $refundAmount,
                        'note' => NULL,
                        'external_id' => $refund->id,
                        'external_provider' => 'stripe',
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                    'relationships' => [
                        'payment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'payment',
                        'id' => $payment['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $payment,
                                [
                                    'id' => true,
                                    'payment_method_id' => true
                                ]
                            ),
                            ['total_refunded' => $refundAmount]
                        ),
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        // assert refund raw saved in db
        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['total_due'],
                'refunded_amount' => $refundAmount,
                'note' => null,
                'external_provider' => $payment['external_provider'],
                'external_id' => $refund->id,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // assert refund value saved in payment table
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'id' => $payment['id'],
                'total_refunded' => $payment['total_refunded'] + $refundAmount,
            ]
        );
    }

    public function test_user_create_own_refund_paypal() // ok 
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['paypal'])
        );
        $methodType = PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE;
        $refundId = $this->faker->word;
        $this->paypalExternalHelperMock->method('createTransactionRefund')
            ->willReturn($refundId);
        $due = $this->faker->numberBetween(11, 1000);

        $paypalBillingAgreement = $this->fakePaypalBillingAgreement();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $paypalBillingAgreement['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $refundAmount = $due - 10;

        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'external_id' => $this->faker->word,
            'currency' => $currency,
            'total_due' => $due,
            'external_provider' => 'stripe',
            'total_refunded' => 0,
            'conversion_rate' => $conversionRate
        ]);

        $response = $this->call(
            'PUT',
            '/refund',
            [
                'data' => [
                    'type' => 'refund',
                    'attributes' => [
                        'refund_amount' => $refundAmount,
                        'gateway_name' => $gateway,
                    ],
                    'relationships' => [
                        'payment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'refund',
                    'attributes' => [
                        'payment_amount' => $due,
                        'refunded_amount' => $refundAmount,
                        'note' => NULL,
                        'external_id' => $refundId,
                        'external_provider' => 'stripe',
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                    'relationships' => [
                        'payment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'payment',
                        'id' => $payment['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $payment,
                                [
                                    'id' => true,
                                    'payment_method_id' => true
                                ]
                            ),
                            ['total_refunded' => $refundAmount]
                        ),
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        // assert refund raw saved in db
        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['total_due'],
                'refunded_amount' => $refundAmount,
                'note' => null,
                'external_provider' => $payment['external_provider'],
                'external_id' => $refundId,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // assert refund value saved in payment table
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'id' => $payment['id'],
                'total_refunded' => $payment['total_refunded'] + $refundAmount,
            ]
        );
    }

    public function test_refund_order_and_cancel_fulfilment() // ok
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );
        $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;
        $refund = new \stdClass();
        $refund->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createRefund')
            ->willReturn($refund);
        $due = $this->faker->numberBetween(11, 1000);

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

        $refundAmount = $due - 10;

        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'external_id' => $this->faker->word,
            'currency' => $currency,
            'total_due' => $due,
            'external_provider' => 'stripe',
            'total_refunded' => 0,
            'conversion_rate' => $conversionRate
        ]);

        $order = $this->fakeOrder();

        $orderPayment = $this->fakeOrderPayment([
            'order_id' => $order['id'],
            'payment_id' => $payment['id'],
            'created_at' => Carbon::now()->toDateTimeString()
        ]);

        $orderItemFulfillment = $this->fakeOrderItemFulfillment([
            'order_id' => $order['id'],
            'status' => ConfigService::$fulfillmentStatusPending
        ]);

        $response = $this->call(
            'PUT',
            '/refund',
            [
                'data' => [
                    'type' => 'refund',
                    'attributes' => [
                        'refund_amount' => $refundAmount,
                        'gateway_name' => $gateway,
                    ],
                    'relationships' => [
                        'payment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'refund',
                    'attributes' => [
                        'payment_amount' => $due,
                        'refunded_amount' => $refundAmount,
                        'note' => NULL,
                        'external_id' => $refund->id,
                        'external_provider' => 'stripe',
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                    'relationships' => [
                        'payment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'payment',
                        'id' => $payment['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $payment,
                                [
                                    'id' => true,
                                    'payment_method_id' => true
                                ]
                            ),
                            ['total_refunded' => $refundAmount]
                        ),
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        // assert refund raw saved in db
        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['total_due'],
                'refunded_amount' => $refundAmount,
                'note' => null,
                'external_provider' => $payment['external_provider'],
                'external_id' => $refund->id,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // assert refund value saved in payment table
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'id' => $payment['id'],
                'total_refunded' => $payment['total_refunded'] + $refundAmount,
            ]
        );

        // assert shipping fulfillment deleted
        $this->assertDatabaseMissing(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id' => $orderItemFulfillment['id'],
                'order_id' => $order['id'],
            ]
        );
    }

    public function test_refund_order_shipped() // ok
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );
        $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;
        $refund = new \stdClass();
        $refund->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createRefund')
            ->willReturn($refund);
        $due = $this->faker->numberBetween(11, 1000);

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

        $refundAmount = $due - 10;

        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'external_id' => $this->faker->word,
            'currency' => $currency,
            'total_due' => $due,
            'external_provider' => 'stripe',
            'total_refunded' => 0,
            'conversion_rate' => $conversionRate
        ]);

        $order = $this->fakeOrder();

        $orderPayment = $this->fakeOrderPayment([
            'order_id' => $order['id'],
            'payment_id' => $payment['id'],
            'created_at' => Carbon::now()->toDateTimeString()
        ]);

        $orderItemFulfillment = $this->fakeOrderItemFulfillment([
            'order_id' => $order['id'],
            'status' => ConfigService::$fulfillmentStatusFulfilled,
            'fulfilled_on' => Carbon::now()->toDateTimeString()
        ]);

        $response = $this->call(
            'PUT',
            '/refund',
            [
                'data' => [
                    'type' => 'refund',
                    'attributes' => [
                        'refund_amount' => $refundAmount,
                        'gateway_name' => $gateway,
                    ],
                    'relationships' => [
                        'payment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'refund',
                    'attributes' => [
                        'payment_amount' => $due,
                        'refunded_amount' => $refundAmount,
                        'note' => NULL,
                        'external_id' => $refund->id,
                        'external_provider' => 'stripe',
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                    'relationships' => [
                        'payment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'payment',
                        'id' => $payment['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $payment,
                                [
                                    'id' => true,
                                    'payment_method_id' => true
                                ]
                            ),
                            ['total_refunded' => $refundAmount]
                        ),
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        // assert refund raw saved in db
        $this->assertDatabaseHas(
            ConfigService::$tableRefund,
            [
                'payment_id' => $payment['id'],
                'payment_amount' => $payment['total_due'],
                'refunded_amount' => $refundAmount,
                'note' => null,
                'external_provider' => $payment['external_provider'],
                'external_id' => $refund->id,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // assert refund value saved in payment table
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'id' => $payment['id'],
                'total_refunded' => $payment['total_refunded'] + $refundAmount,
            ]
        );

        // assert shipping fulfillment deleted
        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id' => $orderItemFulfillment['id'],
                'order_id' => $order['id'],
            ]
        );
    }
}
