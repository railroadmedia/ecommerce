<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Doctrine\ORM\EntityManager;
use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class SubscriptionJsonControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_delete()
    {
        $em = $this->app->make(EntityManager::class);
        $em->getMetadataFactory()
            ->getCacheDriver()
            ->deleteAll();

        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $subscription = $this->fakeSubscription();

        $results = $this->call('DELETE', '/subscription/' . $subscription['id']);

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertSoftDeleted(
            ConfigService::$tableSubscription,
            [
                'id' => $subscription['id'],
            ]
        );
    }

    public function test_delete_not_existing_subscription()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $randomId = $this->faker->randomNumber();

        $results = $this->call('DELETE', '/subscription/' . $randomId);

        // assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                'title' => 'Not found.',
                'detail' => 'Delete failed, subscription not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_pull_subscriptions()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $page = 1;
        $limit = 10;
        $nrSubscriptions = $this->faker->numberBetween(15, 25);

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $paymentMethod = $this->fakePaymentMethod();
            $order = $this->fakeOrder();

            $subscription = $this->fakeSubscription([
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $userId,
                'order_id' => $order['id'],
                'updated_at' => null
            ]);

            if ($i < $limit) {
                $subscriptions[] = [
                    'type' => 'subscription',
                    'id' => $subscription['id'],
                    'attributes' => array_diff_key(
                        $subscription,
                        [
                            'id' => true,
                            'product_id' => true,
                            'user_id' => true,
                            'customer_id' => true,
                            'payment_method_id' => true,
                            'order_id' => true
                        ]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ],
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => $order['id']
                            ]
                        ],
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ];
            }
        }

        $results = $this->call(
            'GET',
            '/subscriptions',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc'
            ]
        );

        $this->assertEquals(
            $subscriptions,
            $results->decodeResponseJson('data')
        );
    }

    public function test_pull_subscriptions_for_specific_user()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $otherUser = $this->fakeUser();

        $page = 1;
        $limit = 10;
        $nrSubscriptions = $this->faker->numberBetween(15, 25);

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $paymentMethod = $this->fakePaymentMethod();
            $order = $this->fakeOrder();

            $subscription = $this->fakeSubscription([
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $this->faker->randomElement([
                    $userId,
                    $otherUser['id']
                ]),
                'order_id' => $order['id'],
                'updated_at' => null
            ]);

            if (
                count($subscriptions) < $limit &&
                $subscription['user_id'] == $userId
            ) {

                $subscriptions[] = [
                    'type' => 'subscription',
                    'id' => $subscription['id'],
                    'attributes' => array_diff_key(
                        $subscription,
                        [
                            'id' => true,
                            'product_id' => true,
                            'user_id' => true,
                            'customer_id' => true,
                            'payment_method_id' => true,
                            'order_id' => true
                        ]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ],
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => $order['id']
                            ]
                        ],
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ];
            }
        }

        $results = $this->call(
            'GET',
            '/subscriptions',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'user_id' => $userId
            ]
        );

        $this->assertEquals(
            $subscriptions,
            $results->decodeResponseJson('data')
        );
    }

    public function test_store_validation()
    {
        $results = $this->call('PUT', '/subscription', []);

        // assert the response status code
        $this->assertEquals(422, $results->getStatusCode());

        // assert the validation errors
        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.brand',
                    'detail' => 'The brand field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.type',
                    'detail' => 'The type field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.is_active',
                    'detail' => 'The is_active field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.start_date',
                    'detail' => 'The start_date field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.paid_until',
                    'detail' => 'The paid_until field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.total_price',
                    'detail' => 'The total_price field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.currency',
                    'detail' => 'The currency field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.interval_type',
                    'detail' => 'The interval_type field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.interval_count',
                    'detail' => 'The interval_count field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.total_cycles_paid',
                    'detail' => 'The total_cycles_paid field is required.',
                    'title' => 'Validation failed.'
                ]
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_store()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $discount = $this->faker->discount([
            'product_id' => $product['id']
        ]);

        $paymentMethod = $this->fakePaymentMethod();

        $order = $this->fakeOrder();

        $subscription = $this->faker->subscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null
        ]);

        $results  = $this->call(
            'PUT',
            '/subscription',
            [
                'data' => [
                    'type' => 'subscription',
                    'attributes' => $subscription,
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ],
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ],
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => $order['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(200, $results->getStatusCode());

        // assert returned JSON
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'subscription',
                    'attributes' => array_diff_key(
                        $subscription,
                        [
                            'product_id' => true,
                            'payment_method_id' => true,
                            'user_id' => true,
                            'order_id' => true,
                            'customer_id' => true,
                            'updated_at' => true
                        ]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ],
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ],
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => $order['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $product['id'],
                    ]
                ]
            ],
            $results->decodeResponseJson()
        );

        // assert that the subscription exists in the database
        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            array_diff_key(
                $subscription,
                ['updated_at' => true]
            )
        );

        // assert user product was created
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $subscription['paid_until'],
            ]
        );
    }

    public function test_update_not_existing_subscription()
    {
        $randomId = rand();

        $results = $this->call('PATCH', '/subscription/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());

        // assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                'title' => 'Not found.',
                'detail' => 'Update failed, subscription not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_update_subscription()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $discount = $this->faker->discount([
            'product_id' => $product['id']
        ]);

        $paymentMethod = $this->fakePaymentMethod();

        $order = $this->fakeOrder();

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null
        ]);

        $newPrice = $this->faker->numberBetween();

        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscription['id'],
            [
                'data' => [
                    'type' => 'subscription',
                    'attributes' => ['total_price' => $newPrice]
                ],
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $subscription['id'],
                    'attributes' => array_merge(
                        array_diff_key(
                            $subscription,
                            [
                                'id' => true,
                                'product_id' => true,
                                'payment_method_id' => true,
                                'user_id' => true,
                                'order_id' => true,
                                'customer_id' => true,
                                'updated_at' => true
                            ]
                        ),
                        [
                            'total_price' => $newPrice,
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ],
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ],
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => $order['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $product['id'],
                    ]
                ]
            ],
            $results->decodeResponseJson()
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            array_merge(
                $subscription,
                [
                    'total_price' => $newPrice,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        // assert user product
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => $subscription['paid_until'],
            ]
        );
    }

    public function test_cancel_subscription()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $discount = $this->faker->discount([
            'product_id' => $product['id']
        ]);

        $paymentMethod = $this->fakePaymentMethod();

        $order = $this->fakeOrder();

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'expiration_date' => $subscription['paid_until']
        ]);

        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscription['id'],
            [
                'data' => [
                    'type' => 'subscription',
                    'attributes' => ['is_active' => false]
                ],
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $subscription['id'],
                    'attributes' => array_merge(
                        array_diff_key(
                            $subscription,
                            [
                                'id' => true,
                                'product_id' => true,
                                'payment_method_id' => true,
                                'user_id' => true,
                                'order_id' => true,
                                'customer_id' => true,
                                'updated_at' => true
                            ]
                        ),
                        [
                            'is_active' => false,
                            'canceled_on' => Carbon::now()->toDateTimeString(),
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ],
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ],
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => $order['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $product['id'],
                    ]
                ]
            ],
            $results->decodeResponseJson()
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            array_merge(
                $subscription,
                [
                    'is_active' => false,
                    'canceled_on' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        // assert user product was removed
        $this->assertDatabaseMissing(
            ConfigService::$tableUserProduct,
            $userProduct
        );
    }

    public function test_update_subscription_validation()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $discount = $this->faker->discount([
            'product_id' => $product['id']
        ]);

        $paymentMethod = $this->fakePaymentMethod();

        $order = $this->fakeOrder();

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null
        ]);

        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscription['id'],
            [
                'data' => [
                    'type' => 'subscription',
                    'attributes' => [
                        'total_cycles_due' => -2,
                        'interval_type' => $this->faker->word,
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => rand()
                            ]
                        ],
                    ]
                ],
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        // assert the validation errors
        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.interval_type',
                    'detail' => 'The selected interval_type is invalid.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.total_cycles_due',
                    'detail' => 'The total_cycles_due must be at least 0.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.relationships.paymentMethod.data.id',
                    'detail' => 'The selected paymentMethod is invalid.',
                    'title' => 'Validation failed.'
                ]
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_update_subscription_date()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $discount = $this->faker->discount([
            'product_id' => $product['id']
        ]);

        $paymentMethod = $this->fakePaymentMethod();

        $order = $this->fakeOrder();

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'expiration_date' => $subscription['paid_until']
        ]);

        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscription['id'],
            [
                'data' => [
                    'type' => 'subscription',
                    'attributes' => [
                        'paid_until' => Carbon::now()->toDateTimeString(),
                    ]
                ],
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $subscription['id'],
                    'attributes' => array_merge(
                        array_diff_key(
                            $subscription,
                            [
                                'id' => true,
                                'product_id' => true,
                                'payment_method_id' => true,
                                'user_id' => true,
                                'order_id' => true,
                                'customer_id' => true,
                                'updated_at' => true
                            ]
                        ),
                        [
                            'paid_until' => Carbon::now()->toDateTimeString(),
                            'updated_at' => Carbon::now()->toDateTimeString(),
                        ]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ],
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ],
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => $order['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $product['id'],
                    ]
                ]
            ],
            $results->decodeResponseJson()
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            array_merge(
                $subscription,
                [
                    'paid_until' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        // assert user product was updated
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            array_merge(
                $userProduct,
                [
                    'expiration_date' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]
            )
        );
    }

    public function test_renew_subscription_credit_card()
    {
        $userId = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('can')->willReturn(true);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn(new Charge());

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $creditCard = $this->fakeCreditCard();

        $paymentMethod = $this->fakePaymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id' => $creditCard['id'],
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'paid_until' => Carbon::now()
                        ->subDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => ConfigService::$intervalTypeYearly,
        ]);

        $results = $this->call(
            'POST',
            '/subscription-renew/' . $subscription['id']
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'id' => $subscription['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_renew_subscription_paypal()
    {
        $userId = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('can')->willReturn(true);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($this->faker->word);

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $paypalBillingAgreement = $this->fakePaypalBillingAgreement();

        $paymentMethod = $this->fakePaymentMethod([
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'method_id' => $paypalBillingAgreement['id'],
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'paid_until' => Carbon::now()
                        ->subDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => ConfigService::$intervalTypeYearly,
        ]);

        $results = $this->call(
            'POST',
            '/subscription-renew/' . $subscription['id']
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'id' => $subscription['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_renew_subscription_payment_failed_not_disabled()
    {
        $userId = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('can')->willReturn(true);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $exceptionMessage = 'Charge failed';

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willThrowException(
                new PaymentFailedException($exceptionMessage)
            );

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $creditCard = $this->fakeCreditCard();

        $paymentMethod = $this->fakePaymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id' => $creditCard['id'],
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'paid_until' => Carbon::now()
                        ->addDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => ConfigService::$intervalTypeYearly,
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'quantity' => 1,
            'expiration_date' => $subscription['paid_until']
        ]);

        ConfigService::$failedPaymentsBeforeDeactivation = 3;

        $results = $this->call(
            'POST',
            '/subscription-renew/' . $subscription['id']
        );

        // assert response status code
        $this->assertEquals(402, $results->getStatusCode());

        // assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                'title' => 'Subscription renew failed.',
                'detail' => 'Payment failed: ' . $exceptionMessage,
            ],
            $results->decodeResponseJson('errors')
        );

        // assert user product data was not updated
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            $userProduct
        );

        // assert subscription data was not updated
        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            $subscription
        );
    }

    public function test_renew_subscription_payment_failed_disabled()
    {
        $userId = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('can')->willReturn(true);

        $exceptionMessage = 'Charge failed';

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willThrowException(
                new \Exception($exceptionMessage)
            );

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription,
            'subscription_interval_type' => ConfigService::$intervalTypeYearly,
            'subscription_interval_count' => 1,
        ]);

        $paypalBillingAgreement = $this->fakePaypalBillingAgreement();

        $paymentMethod = $this->fakePaymentMethod([
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'method_id' => $paypalBillingAgreement['id'],
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'paid_until' => Carbon::now()
                        ->addDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => ConfigService::$intervalTypeYearly,
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'quantity' => 1,
            'expiration_date' => $subscription['paid_until']
        ]);

        ConfigService::$failedPaymentsBeforeDeactivation = 1;

        $results = $this->call(
            'POST',
            '/subscription-renew/' . $subscription['id']
        );

        // assert response status code
        $this->assertEquals(402, $results->getStatusCode());

        // assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                'title' => 'Subscription renew failed.',
                'detail' => 'Payment failed: ' . $exceptionMessage,
            ],
            $results->decodeResponseJson('errors')
        );

        // assert user product data was removed
        $this->assertDatabaseMissing(
            ConfigService::$tableUserProduct,
            $userProduct
        );

        // assert subscription was set as inactive
        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            array_merge(
                $subscription,
                [
                    'is_active' => 0,
                    'note' => 'De-activated due to payments failing.',
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]
            )
        );
    }

    public function test_pull_subscription_from_specific_brand()
    {

        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $page = 1;
        $limit = 10;
        $nrSubscriptions = $this->faker->numberBetween(15, 25);

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $subscriptionBrands = [$this->faker->word, $this->faker->word];

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $paymentMethod = $this->fakePaymentMethod();
            $order = $this->fakeOrder();

            if ($i < $limit) {
                // specific brand
                $subscription = $this->fakeSubscription([
                    'brand' => $this->faker->randomElement($subscriptionBrands),
                    'product_id' => $product['id'],
                    'payment_method_id' => $paymentMethod['id'],
                    'user_id' => $userId,
                    'order_id' => $order['id'],
                    'updated_at' => null
                ]);

                $subscriptions[] = [
                    'type' => 'subscription',
                    'id' => $subscription['id'],
                    'attributes' => array_diff_key(
                        $subscription,
                        [
                            'id' => true,
                            'product_id' => true,
                            'user_id' => true,
                            'customer_id' => true,
                            'payment_method_id' => true,
                            'order_id' => true
                        ]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ],
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'order' => [
                            'data' => [
                                'type' => 'order',
                                'id' => $order['id']
                            ]
                        ],
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ];
            } else {
                // default brand
                $subscription = $this->fakeSubscription([
                    'product_id' => $product['id'],
                    'payment_method_id' => $paymentMethod['id'],
                    'user_id' => $userId,
                    'order_id' => $order['id'],
                    'updated_at' => null
                ]);
            }
        }

        $results = $this->call(
            'GET',
            '/subscriptions',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'brands' => $subscriptionBrands
            ]
        );

        $this->assertEquals(
            $subscriptions,
            $results->decodeResponseJson('data')
        );
    }
}
