<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Refund;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class RefundJsonControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->currencyService = $this->app->make(CurrencyService::class);
    }

    public function test_store_validation()
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

    public function test_user_create_own_refund_credit_card()
    {
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);

        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $refund = new \stdClass();
        $refund->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createRefund')
            ->willReturn($refund);
        $due = $this->faker->numberBetween(11, 1000);

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

        $refundAmount = $due - 10;

        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'external_id' => $this->faker->word,
            'currency' => $currency,
            'total_due' => $due,
            'external_provider' => 'stripe',
            'total_refunded' => 0,
            'conversion_rate' => $conversionRate,
            'gateway_name' => $gateway,
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
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'creditCard',
                        'id' => $creditCard['id'],
                        'attributes' => []
                    ],
                    [
                        'type' => 'userPaymentMethod',
                        'id' => $userPaymentMethod['id'],
                        'attributes' => array_diff_key(
                            $userPaymentMethod,
                            [
                                'id' => true,
                                'user_id' => true,
                                'payment_method_id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'address',
                        'id' => $address['id'],
                        'attributes' => []
                    ],
                    [
                        'type' => 'paymentMethod',
                        'id' => $paymentMethod['id'],
                        'attributes' => array_diff_key(
                            $paymentMethod,
                            [
                                'id' => true,
                                'method_id' => true,
                                'billing_address_id' => true
                            ]
                        ),
                        'relationships' => [
                            'billingAddress' => [
                                'data' => [
                                    'type' => 'address',
                                    'id' => $address['id']
                                ]
                            ]
                        ]
                    ],
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
            'ecommerce_refunds',
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
            'ecommerce_payments',
            [
                'id' => $payment['id'],
                'total_refunded' => $payment['total_refunded'] + $refundAmount,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $gateway,
                'resource_name' => Refund::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_USER,
            ]
        );
    }

    public function test_user_create_own_refund_paypal()
    {
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);

        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['paypal'])
        );
        $methodType = PaymentMethod::TYPE_PAYPAL;
        $refundId = $this->faker->word;
        $this->paypalExternalHelperMock->method('createTransactionRefund')
            ->willReturn($refundId);
        $due = $this->faker->numberBetween(11, 1000);

        $paypalBillingAgreement = $this->fakePaypalBillingAgreement();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'paypal_billing_agreement_id' => $paypalBillingAgreement['id'],
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
            'conversion_rate' => $conversionRate,
            'gateway_name' => $gateway,
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
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'paypalBillingAgreement',
                        'id' => $paypalBillingAgreement['id'],
                        'attributes' => []
                    ],
                    [
                        'type' => 'userPaymentMethod',
                        'id' => $userPaymentMethod['id'],
                        'attributes' => array_diff_key(
                            $userPaymentMethod,
                            [
                                'id' => true,
                                'user_id' => true,
                                'payment_method_id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'address',
                        'id' => $address['id'],
                        'attributes' => []
                    ],
                    [
                        'type' => 'paymentMethod',
                        'id' => $paymentMethod['id'],
                        'attributes' => array_diff_key(
                            $paymentMethod,
                            [
                                'id' => true,
                                'method_id' => true,
                                'billing_address_id' => true
                            ]
                        ),
                        'relationships' => [
                            'billingAddress' => [
                                'data' => [
                                    'type' => 'address',
                                    'id' => $address['id']
                                ]
                            ],
                        ]
                    ],
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
            'ecommerce_refunds',
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
            'ecommerce_payments',
            [
                'id' => $payment['id'],
                'total_refunded' => $payment['total_refunded'] + $refundAmount,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $gateway,
                'resource_name' => Refund::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_USER,
            ]
        );
    }

    public function test_refund_order_and_cancel_fulfilment()
    {
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);

        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $refund = new \stdClass();
        $refund->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createRefund')
            ->willReturn($refund);
        $due = $this->faker->numberBetween(11, 1000);

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

        $refundAmount = $due - 10;

        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'external_id' => $this->faker->word,
            'currency' => $currency,
            'total_due' => $due,
            'external_provider' => 'stripe',
            'total_refunded' => 0,
            'conversion_rate' => $conversionRate,
            'gateway_name' => $gateway,
        ]);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $order = $this->fakeOrder();

        $orderItem = $this->fakeOrderItem([
            'order_id' => $order['id'],
            'product_id' => $product['id'],
            'quantity' => 1
        ]);

        $orderPayment = $this->fakeOrderPayment([
            'order_id' => $order['id'],
            'payment_id' => $payment['id'],
        ]);

        $orderItem = $this->fakeOrderItem([
            'order_id' => $order['id'],
            'product_id' => $product['id'],
            'quantity' => 1
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'quantity' => 1
        ]);

        $orderItemFulfillment = $this->fakeOrderItemFulfillment([
            'order_id' => $order['id'],
            'status' => config('ecommerce.fulfillment_status_pending')
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
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'creditCard',
                        'id' => $creditCard['id'],
                        'attributes' => []
                    ],
                    [
                        'type' => 'userPaymentMethod',
                        'id' => $userPaymentMethod['id'],
                        'attributes' => array_diff_key(
                            $userPaymentMethod,
                            [
                                'id' => true,
                                'user_id' => true,
                                'payment_method_id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'address',
                        'id' => $address['id'],
                        'attributes' => []
                    ],
                    [
                        'type' => 'paymentMethod',
                        'id' => $paymentMethod['id'],
                        'attributes' => array_diff_key(
                            $paymentMethod,
                            [
                                'id' => true,
                                'method_id' => true,
                                'billing_address_id' => true
                            ]
                        ),
                        'relationships' => [
                            'billingAddress' => [
                                'data' => [
                                    'type' => 'address',
                                    'id' => $address['id']
                                ]
                            ]
                        ]
                    ],
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
            'ecommerce_refunds',
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
            'ecommerce_payments',
            [
                'id' => $payment['id'],
                'total_refunded' => $payment['total_refunded'] + $refundAmount,
            ]
        );

        // assert shipping fulfillment deleted
        $this->assertDatabaseMissing(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillment['id'],
                'order_id' => $order['id'],
            ]
        );

        // assert user products were not removed on partial refund
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $gateway,
                'resource_name' => Refund::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_CREATE,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_USER,
            ]
        );
    }

    public function test_refund_order_shipped()
    {
        $userId = $this->createAndLogInNewUser();
        $currency = $this->getCurrency();
        $conversionRate = $this->currencyService->getRate($currency);
        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );
        $methodType = PaymentMethod::TYPE_CREDIT_CARD;
        $refund = new \stdClass();
        $refund->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createRefund')
            ->willReturn($refund);
        $due = $this->faker->numberBetween(11, 1000);

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
            'status' => config('ecommerce.fulfillment_status_fulfilled'),
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
                        'type' => 'user',
                        'id' => $userId,
                        'attributes' => []
                    ],
                    [
                        'type' => 'creditCard',
                        'id' => $creditCard['id'],
                        'attributes' => []
                    ],
                    [
                        'type' => 'userPaymentMethod',
                        'id' => $userPaymentMethod['id'],
                        'attributes' => array_diff_key(
                            $userPaymentMethod,
                            [
                                'id' => true,
                                'user_id' => true,
                                'payment_method_id' => true,
                            ]
                        )
                    ],
                    [
                        'type' => 'address',
                        'id' => $address['id'],
                        'attributes' => []
                    ],
                    [
                        'type' => 'paymentMethod',
                        'id' => $paymentMethod['id'],
                        'attributes' => array_diff_key(
                            $paymentMethod,
                            [
                                'id' => true,
                                'method_id' => true,
                                'billing_address_id' => true
                            ]
                        ),
                        'relationships' => [
                            'billingAddress' => [
                                'data' => [
                                    'type' => 'address',
                                    'id' => $address['id']
                                ]
                            ]
                        ]
                    ],
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
            'ecommerce_refunds',
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
            'ecommerce_payments',
            [
                'id' => $payment['id'],
                'total_refunded' => $payment['total_refunded'] + $refundAmount,
            ]
        );

        // assert shipping fulfillment still exists in the database
        $this->assertDatabaseHas(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillment['id'],
                'order_id' => $order['id'],
            ]
        );
    }

    public function test_refund_mobile_app_payment_exception()
    {
        $mobileAppPaymentTypes = [
            Payment::TYPE_APPLE_INITIAL_ORDER,
            Payment::TYPE_APPLE_SUBSCRIPTION_RENEWAL,
            Payment::TYPE_GOOGLE_SUBSCRIPTION_RENEWAL,
            Payment::TYPE_GOOGLE_INITIAL_ORDER,
        ];

        $due = $this->faker->numberBetween(11, 1000);

        $payment = $this->fakePayment([
            'type' => $this->faker->randomElement($mobileAppPaymentTypes),
            'total_due' => $due,
            'total_refunded' => 0,
            'conversion_rate' => 1
        ]);

        $refundAmount = $due;

        $gateway = $this->faker->randomElement(
            array_keys(config('ecommerce.payment_gateways')['stripe'])
        );

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

        $this->assertEquals(400, $response->getStatusCode());

        $this->assertEquals(
            [
                [
                    'title' => 'Payment refund failed.',
                    'detail' => 'Payments made in-app by mobile applications my not be refunded on web application',
                ],
            ],
            $response->decodeResponseJson('errors')
        );
    }
}
