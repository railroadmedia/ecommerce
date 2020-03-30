<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionCreated;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionDeleted;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewFailed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Events\Subscriptions\UserSubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\UserSubscriptionUpdated;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Mail\SubscriptionInvoice;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;

class SubscriptionJsonControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_delete()
    {
        $em = $this->app->make(EcommerceEntityManager::class);
        $em->getMetadataFactory()
            ->getCacheDriver()
            ->deleteAll();

        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $subscription = $this->fakeSubscription();

        $this->expectsEvents([SubscriptionDeleted::class]);

        $results = $this->call('DELETE', '/subscription/' . $subscription['id']);

        $this->assertEquals(204, $results->getStatusCode());


        $this->assertSoftDeleted(
            'ecommerce_subscriptions',
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

        $this->doesntExpectEvents([SubscriptionDeleted::class]);

        $results = $this->call('DELETE', '/subscription/' . $randomId);

        // assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                [
                    'title' => 'Not found.',
                    'detail' => 'Delete failed, subscription not found with id: ' . $randomId,
                ]
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
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        // add soft deleted subscription, should not be returned in response
        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'deleted_at' => Carbon::now(),
            'cancellation_reason' => null
        ]);

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $creditCard = $this->fakeCreditCard();
            $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
            $order = $this->fakeOrder();

            $subscription = $this->fakeSubscription([
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $userId,
                'order_id' => $order['id'],
                'updated_at' => null,
                'cancellation_reason' => null
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
                'order_by_direction' => 'asc',
                'includes' => 'product',
            ]
        );

        $this->assertEquals(
            $subscriptions,
            $results->decodeResponseJson('data')
        );
    }

    public function test_pull_subscriptions_include_soft_deleted()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $page = 1;
        $limit = 10;
        $nrSubscriptions = $this->faker->numberBetween(15, 25);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        // add soft deleted subscription, should be returned in response
        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'deleted_at' => Carbon::now(),
            'cancellation_reason' => null
        ]);

        $subscriptions = [
            [
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
            ]
        ];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $creditCard = $this->fakeCreditCard();
            $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
            $order = $this->fakeOrder();

            $subscription = $this->fakeSubscription([
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $userId,
                'order_id' => $order['id'],
                'updated_at' => null,
                'cancellation_reason' => null
            ]);

            if ($i < $limit - 1) {
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
                'includes' => 'product',
                'view_deleted' => true,
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
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $creditCard = $this->fakeCreditCard();
            $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
            $order = $this->fakeOrder();

            $subscription = $this->fakeSubscription([
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $this->faker->randomElement([
                    $userId,
                    $otherUser['id']
                ]),
                'order_id' => $order['id'],
                'updated_at' => null,
                'cancellation_reason' => null
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
                    'source' => 'data.attributes.stopped',
                    'detail' => 'The stopped field is required.',
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
                ],
                [
                    "title" => "Validation failed.",
                    "source" => "data.attributes.renewal_attempt",
                    "detail" => "The renewal_attempt field is required.",
                ],
                [
                    "title" => "Validation failed.",
                    "source" => "data.relationships.user.data.id",
                    "detail" => "The user id field is required.",
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
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
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

        $this->expectsEvents([SubscriptionCreated::class]);

        $results = $this->call(
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
            'ecommerce_subscriptions',
            array_diff_key(
                $subscription,
                ['updated_at' => true]
            )
        );

        // assert user product was created
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::parse($subscription['paid_until'])
                                        ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                                        ->toDateTimeString(),
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
                [
                    'title' => 'Not found.',
                    'detail' => 'Update failed, subscription not found with id: ' . $randomId,
                ]
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_update_subscription()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
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

        $this->expectsEvents([SubscriptionUpdated::class, UserSubscriptionUpdated::class]);

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
            'ecommerce_subscriptions',
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
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::parse($subscription['paid_until'])
                                                ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                                                ->toDateTimeString(),
            ]
        );
    }

    public function test_de_activate_subscription()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
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
            'updated_at' => null,
            'canceled_on' => null,
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'expiration_date' => Carbon::parse($subscription['paid_until'])
                                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                                    ->toDateTimeString(),
            'quantity' => 1
        ]);

        $this->expectsEvents([SubscriptionUpdated::class, UserSubscriptionUpdated::class]);

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
                            'canceled_on' => null,
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
            'ecommerce_subscriptions',
            array_merge(
                $subscription,
                [
                    'is_active' => false,
                    'canceled_on' => null,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        // assert user product was set
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            array_merge(
                $userProduct,
                [
                    'expiration_date' => Carbon::parse($subscription['paid_until'])
                                            ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                                            ->toDateTimeString(),
                ]
            )
        );
    }

    public function test_cancel_subscription()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $paymentMethod = $this->fakePaymentMethod();

        $order = $this->fakeOrder();

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'canceled_on' => null,
            'is_active' => true,
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'expiration_date' => Carbon::parse($subscription['paid_until'])
                                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                                    ->toDateTimeString(),
            'quantity' => 1
        ]);

        $this->expectsEvents([SubscriptionUpdated::class, UserSubscriptionUpdated::class]);

        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscription['id'],
            [
                'data' => [
                    'type' => 'subscription',
                    'attributes' => ['canceled_on' => Carbon::now()->toDateTimeString()]
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
            'ecommerce_subscriptions',
            array_merge(
                $subscription,
                [
                    'canceled_on' => Carbon::now()->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        // assert user product was set
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            array_merge(
                $userProduct,
                [
                    'expiration_date' => Carbon::parse($subscription['paid_until'])
                                            ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                                            ->toDateTimeString(),
                ]
            )
        );
    }

    public function test_update_suspended_subscription()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $paymentMethod = $this->fakePaymentMethod();

        $orderOne = $this->fakeOrder();
        $orderTwo = $this->fakeOrder();

        // 1st subscription is not active
        $subscriptionOne = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $orderOne['id'],
            'updated_at' => null,
            'canceled_on' => null,
            'is_active' => false,
            'paid_until' => Carbon::now()
                                    ->subMonths(5)
        ]);

        // 2nd subscription is active
        $subscriptionTwo = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $orderTwo['id'],
            'updated_at' => null,
            'canceled_on' => null,
            'is_active' => true,
            'paid_until' => Carbon::now()
                                    ->addDays(5)
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'expiration_date' => Carbon::parse($subscriptionTwo['paid_until'])
                                            ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                                            ->toDateTimeString(),
            'quantity' => 1
        ]);

        // events not fired
        $this->doesntExpectEvents([SubscriptionUpdated::class, UserSubscriptionUpdated::class]);

        $note = $this->faker->word;

        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscriptionOne['id'],
            [
                'data' => [
                    'type' => 'subscription',
                    'attributes' => ['note' => $note]
                ],
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'subscription',
                    'id' => $subscriptionOne['id'],
                    'attributes' => array_merge(
                        array_diff_key(
                            $subscriptionOne,
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
                            'note' => $note,
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
                                'id' => $orderOne['id']
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
            'ecommerce_subscriptions',
            array_merge(
                $subscriptionOne,
                [
                    'note' => $note,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]
            )
        );

        // assert user product was not updated
        $this->assertDatabaseHas('ecommerce_user_products', $userProduct);
    }

    public function test_update_subscription_validation()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
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
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
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

        $this->expectsEvents([SubscriptionUpdated::class, UserSubscriptionUpdated::class]);

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
                            'paid_until' => Carbon::now()
                                                ->toDateTimeString(),
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
            'ecommerce_subscriptions',
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
            'ecommerce_user_products',
            array_merge(
                $userProduct,
                [
                    'expiration_date' => Carbon::now()
                                            ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                                            ->toDateTimeString(),
                    'updated_at' => Carbon::now()->toDateTimeString(),
                ]
            )
        );
    }

    public function test_renew_mobile_subscription_exception()
    {
        Mail::fake();

        $userId = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('can')->willReturn(true);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn(new Charge());

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
            'price' => 128.95,
        ]);

        $creditCard = $this->fakeCreditCard([
            'payment_gateway_name' => 'brand',
        ]);

        $currency = $this->getCurrency();

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

        $expectedTaxRateProduct =
            config('ecommerce.product_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];
        $expectedTaxRateShipping =
            config('ecommerce.shipping_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];

        $expectedSubscriptionTaxes = round($expectedTaxRateProduct * $product['price'], 2);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'type' => $this->faker->randomElement(
                [Subscription::TYPE_APPLE_SUBSCRIPTION, Subscription::TYPE_GOOGLE_SUBSCRIPTION]
            ),
            'user_id' => $userId,
            'paid_until' => Carbon::now()
                        ->subDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
            'total_price' => round($product['price'] + $expectedSubscriptionTaxes, 2),
            'tax' => $expectedSubscriptionTaxes
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'quantity' => 1,
            'expiration_date' => Carbon::now()
                ->subDay(1)
                ->toDateTimeString(),
        ]);

        $response = $this->call(
            'POST',
            '/subscription-renew/' . $subscription['id']
        );

        // assert the response status code
        $this->assertEquals(402, $response->getStatusCode());

        // assert the validation errors
        $this->assertEquals(
            [
                'title' => 'Subscription renew failed.',
                'detail' => 'Subscription made by mobile application may not be renewed by web application',
            ],
            $response->decodeResponseJson('errors')
        );

        // Assert a message was not sent to the given users...
        Mail::assertNotSent(
            SubscriptionInvoice::class,
            function ($mail) {
                $mail->build();

                return $mail->hasTo(auth()->user()['email']) &&
                    $mail->hasFrom(config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_sender')) &&
                    $mail->subject(
                        config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_email_subject')
                    );
            }
        );

        // assert a mailable was not sent
        Mail::assertNotSent(SubscriptionInvoice::class, 1);

        // assert user product expiration date and subscription paid until dates remained the same
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                    ->subDay(1)
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscription['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()
                    ->subDay(1)
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_renew_subscription_credit_card()
    {
        Mail::fake();

        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);

        $this->permissionServiceMock->method('can')->willReturn(true);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn(new Charge());

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
            'price' => 128.95,
        ]);

        $creditCard = $this->fakeCreditCard([
            'payment_gateway_name' => 'brand',
        ]);

        $currency = $this->getCurrency();

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

        $expectedTaxRateProduct =
            config('ecommerce.product_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];
        $expectedTaxRateShipping =
            config('ecommerce.shipping_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];

        $expectedSubscriptionTaxes = round($expectedTaxRateProduct * $product['price'], 2);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'paid_until' => Carbon::now()
                        ->subDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
            'total_price' => round($product['price'] + $expectedSubscriptionTaxes, 2),
            'tax' => $expectedSubscriptionTaxes
        ]);

        $results = $this->call(
            'POST',
            '/subscription-renew/' . $subscription['id']
        );

        // Assert a message was sent to the given users...
        Mail::assertSent(
            SubscriptionInvoice::class,
            function ($mail) {
                $mail->build();

                return $mail->hasTo(auth()->user()['email']) &&
                    $mail->hasFrom(config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_sender')) &&
                    $mail->subject(
                        config('ecommerce.invoice_email_details.brand.subscription_renewal_invoice.invoice_email_subject')
                    );
            }
        );

        // assert a mailable was sent
        Mail::assertSent(SubscriptionInvoice::class, 1);

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscription['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $address['country'],
                'region' => $address['region'],
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => $expectedSubscriptionTaxes,
                'shipping_taxes_paid' => 0,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $subscription['brand'],
                'resource_name' => Subscription::class,
                'resource_id' => $subscription['id'],
                'action_name' => Subscription::ACTION_RENEW,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_USER,
            ]
        );
    }

    public function test_renew_subscription_paypal()
    {
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);
        $brand = 'drumeo';

        $this->permissionServiceMock->method('can')->willReturn(true);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($this->faker->word);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
            'price' => 128.95,
        ]);

        $paypalBillingAgreement = $this->fakePaypalBillingAgreement();

        $currency = $this->getCurrency();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
            'country' => 'Canada',
            'region' => 'alberta',
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'paypal_billing_agreement_id' => $paypalBillingAgreement['id'],
            'billing_address_id' => $address['id'],
            'currency' => $currency
        ]);

        $expectedTaxRateProduct =
            config('ecommerce.product_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];
        $expectedTaxRateShipping =
            config('ecommerce.shipping_tax_rate')[strtolower($address['country'])][strtolower($address['region'])];

        $expectedSubscriptionTaxes = round($expectedTaxRateProduct * $product['price'], 2);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'paid_until' => Carbon::now()
                        ->subDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
            'total_price' => round($product['price'] + $expectedSubscriptionTaxes, 2),
            'tax' => $expectedSubscriptionTaxes,
            'brand' => $brand,
        ]);

        $this->expectsEvents(
            [
                SubscriptionRenewed::class,
                SubscriptionUpdated::class,
                UserSubscriptionRenewed::class
            ]
        );

        $results = $this->call(
            'POST',
            '/subscription-renew/' . $subscription['id']
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscription['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $address['country'],
                'region' => $address['region'],
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => $expectedSubscriptionTaxes,
                'shipping_taxes_paid' => 0,
            ]
        );
    }

    public function test_renew_subscription_different_payment_method()
    {
         $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);

        $this->permissionServiceMock->method('can')->willReturn(true);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn(new Charge());

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
            'price' => 128.95,
        ]);

        $creditCard = $this->fakeCreditCard([
            'payment_gateway_name' => 'brand',
        ]);

        $currency = $this->getCurrency();

        $addressOne = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
            'country' => 'Canada',
            'region' => 'alberta',
        ]);

        $paymentMethodOne = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $addressOne['id'],
            'currency' => $currency
        ]);

        $initialTaxRateProduct =
            config('ecommerce.product_tax_rate')[strtolower($addressOne['country'])][strtolower($addressOne['region'])];

        $initialSubscriptionTaxes = round($initialTaxRateProduct * $product['price'], 2);

        $addressTwo = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
            'country' => 'Canada',
            'region' => 'ontario',
        ]);

        $paymentMethodTwo = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $addressTwo['id'],
            'currency' => $currency
        ]);

        $expectedTaxRateProduct =
            config('ecommerce.product_tax_rate')[strtolower($addressTwo['country'])][strtolower($addressTwo['region'])];
        $expectedTaxRateShipping =
            config('ecommerce.shipping_tax_rate')[strtolower($addressTwo['country'])][strtolower($addressTwo['region'])];

        $expectedSubscriptionTaxes = round($expectedTaxRateProduct * $product['price'], 2);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedPaymentTotalDue = $currencyService->convertFromBase(round($product['price'] + $expectedSubscriptionTaxes, 2), $currency);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethodTwo['id'], // *current* payment method is different from the one used to calculate subscription tax
            'user_id' => $userId,
            'paid_until' => Carbon::now()
                        ->subDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
            'total_price' => round($product['price'] + $initialSubscriptionTaxes, 2),
            'tax' => $initialSubscriptionTaxes
        ]);

        $results = $this->call(
            'POST',
            '/subscription-renew/' . $subscription['id']
        );

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscription['id'],
                'is_active' => 1,
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        // assert payment has the tax calculated using *current* paymentMethodTwo address
        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_paid' => $expectedPaymentTotalDue,
                'payment_method_id' => $paymentMethodTwo['id']
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $addressTwo['country'],
                'region' => $addressTwo['region'],
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => $expectedSubscriptionTaxes,
                'shipping_taxes_paid' => 0,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $subscription['brand'],
                'resource_name' => Subscription::class,
                'resource_id' => $subscription['id'],
                'action_name' => Subscription::ACTION_RENEW,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_USER,
            ]
        );
    }

    public function test_renew_subscription_payment_failed_disabled()
    {
        $brand = 'drumeo';
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);

        $this->permissionServiceMock->method('can')->willReturn(true);

        $exceptionMessage = 'Charge failed';

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willThrowException(
                new \Exception($exceptionMessage)
            );

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
            'price' => 128.95,
        ]);

        $paypalBillingAgreement = $this->fakePaypalBillingAgreement(
            [
                'payment_gateway_name' => $brand,
            ]
        );

        $currency = $this->getCurrency();

        $address = $this->fakeAddress([
            'type' => Address::BILLING_ADDRESS_TYPE,
            'country' => 'Canada',
            'region' => 'alberta',
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'paypal_billing_agreement_id' => $paypalBillingAgreement['id'],
            'billing_address_id' => $address['id'],
            'currency' => $currency
        ]);

        $taxRate =
            config('ecommerce.product_tax_rate')[strtolower($address['country'])][strtolower(
                $address['region']
            )];

        $tax = round($taxRate * $product['price'], 2);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'paid_until' => Carbon::now()
                        ->addDay(1)
                        ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
            'total_price' => round($product['price'] + $tax, 2),
            'tax' => $tax,
            'brand' => $brand,
            'renewal_attempt' => 0,
        ]);

        $userProduct = $this->fakeUserProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'quantity' => 1,
            'expiration_date' => $subscription['paid_until']
        ]);

        config()->set('ecommerce.paypal.failed_payments_before_de_activation', 1);

        $this->expectsEvents([SubscriptionRenewFailed::class]);
        $this->doesntExpectEvents([SubscriptionRenewed::class]);

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

        // assert user product was set
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            array_merge(
                $userProduct,
                [
                    'expiration_date' => Carbon::parse($subscription['paid_until'])
                                            ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                                            ->toDateTimeString(),
                ]
            )
        );

        // assert subscription was set as inactive
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            array_merge(
                $subscription,
                [
                    'is_active' => 0,
                    'note' => RenewalService::DEACTIVATION_MESSAGE,
                    'updated_at' => Carbon::now()->toDateTimeString(),
                    'renewal_attempt' => 1,
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
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $subscriptionBrands = [$this->faker->word, $this->faker->word];

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $creditCard = $this->fakeCreditCard();
            $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
            $order = $this->fakeOrder();

            if ($i < $limit) {
                // specific brand
                $subscription = $this->fakeSubscription([
                    'brand' => $this->faker->randomElement($subscriptionBrands),
                    'product_id' => $product['id'],
                    'payment_method_id' => $paymentMethod['id'],
                    'user_id' => $userId,
                    'order_id' => $order['id'],
                    'updated_at' => null,
                    'cancellation_reason' => null
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

    public function test_pull_failed_subscriptions_validation()
    {
        $response = $this->call('GET', '/failed-subscriptions', []);

        // assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert the validation errors
        $this->assertEquals(
            [
                [
                    'source' => 'type',
                    'detail' => 'The type field is required.',
                    'title' => 'Validation failed.'
                ],
            ],
            $response->decodeResponseJson('errors')
        );
    }

    public function test_pull_failed_subscriptions()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $page = 1;
        $limit = 10;
        $nrSubscriptions = $this->faker->numberBetween(15, 25);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // an active subscription that should not be returned in response
        $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_SUBSCRIPTION,
            'canceled_on' => null,
            'is_active' => true,
            'paid_until' => Carbon::now()->addDays(5),
            'cancellation_reason' => null
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // an inactive payment plan that should not be returned in response
        $pastDate = Carbon::now()->subDays(rand(1, 28));

        $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_PAYMENT_PLAN,
            'canceled_on' => $pastDate,
            'is_active' => false,
            'paid_until' => $pastDate,
            'cancellation_reason' => null
        ]);

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $creditCard = $this->fakeCreditCard();
            $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
            $order = $this->fakeOrder();

            $subscription = [
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $userId,
                'order_id' => $order['id'],
                'updated_at' => null,
                'type' => Subscription::TYPE_SUBSCRIPTION,
                'cancellation_reason' => null
            ];

            if ($this->faker->randomElement([true, false])) {
                $subscription['canceled_on'] = Carbon::now()->subDays(rand(1, 29));
                $subscription['is_active'] = false;
            } else {
                $paidUntilSubDays = rand(1, 29);
                $subscription['canceled_on'] = null;
                $subscription['is_active'] = false;
                $subscription['start_date'] = Carbon::now()->subDays($paidUntilSubDays + 2);
                $subscription['paid_until'] = Carbon::now()->subDays($paidUntilSubDays);
            }

            $subscription = $this->fakeSubscription($subscription);

            if (count($subscriptions) < $limit) {
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

        $response = $this->call(
            'GET',
            '/failed-subscriptions',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'type' => Subscription::TYPE_SUBSCRIPTION,
                'big_date_time' => Carbon::now()->toDateTimeString(),
                'small_date_time' => Carbon::now()->subDays(30)->toDateTimeString(),
            ]
        );

        $this->assertEquals(
            $subscriptions,
            $response->decodeResponseJson('data')
        );
    }

    public function test_pull_failed_payment_plans()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $page = 1;
        $limit = 10;
        $nrSubscriptions = $this->faker->numberBetween(15, 25);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // an active payment plan that should not be returned in response
        $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_PAYMENT_PLAN,
            'canceled_on' => null,
            'is_active' => true,
            'paid_until' => Carbon::now()->addDays(5),
            'cancellation_reason' => null
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // an inactive subscription that should not be returned in response
        $pastDate = Carbon::now()->subDays(rand(1, 28));

        $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_SUBSCRIPTION,
            'canceled_on' => $pastDate,
            'is_active' => false,
            'paid_until' => $pastDate,
            'cancellation_reason' => null
        ]);

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $creditCard = $this->fakeCreditCard();
            $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
            $order = $this->fakeOrder();

            $subscription = [
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $userId,
                'order_id' => $order['id'],
                'updated_at' => null,
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'cancellation_reason' => null
            ];

            if ($this->faker->randomElement([true, false])) {
                $subscription['canceled_on'] = Carbon::now()->subDays(rand(1, 29));
                $subscription['is_active'] = false;
            } else {
                $paidUntilSubDays = rand(1, 29);
                $subscription['canceled_on'] = null;
                $subscription['is_active'] = false;
                $subscription['start_date'] = Carbon::now()->subDays($paidUntilSubDays + 2);
                $subscription['paid_until'] = Carbon::now()->subDays($paidUntilSubDays);
            }

            $subscription = $this->fakeSubscription($subscription);

            if (count($subscriptions) < $limit) {
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

        $response = $this->call(
            'GET',
            '/failed-subscriptions',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'big_date_time' => Carbon::now()->toDateTimeString(),
                'small_date_time' => Carbon::now()->subDays(30)->toDateTimeString(),
            ]
        );

        $this->assertEquals(
            $subscriptions,
            $response->decodeResponseJson('data')
        );
    }

    public function test_pull_failed_billing_subscriptions()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $page = 1;
        $limit = 10;
        $nrSubscriptions = $this->faker->numberBetween(15, 25);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // failed billing subscription with payment date outside of request interval
        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null,
            'status' => 'failed',
            'total_paid' => 0,
            'created_at' => Carbon::now()->subDays(33)
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_SUBSCRIPTION,
            'canceled_on' => null,
            'is_active' => false,
            'start_date' => Carbon::now()->subDays(35),
            'paid_until' => Carbon::now()->subDays(33),
            'failed_payment_id' => $payment['id'],
        ]);

        $subscriptionOnePayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscription['id'],
            'payment_id' => $payment['id'],
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // failed billing payment plan with payment date outside of request interval
        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null,
            'status' => 'failed',
            'total_paid' => 0,
            'created_at' => Carbon::now()->subDays(34)
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_PAYMENT_PLAN,
            'canceled_on' => null,
            'is_active' => false,
            'start_date' => Carbon::now()->subDays(36),
            'paid_until' => Carbon::now()->subDays(34),
            'failed_payment_id' => $payment['id'],
        ]);

        $subscriptionOnePayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscription['id'],
            'payment_id' => $payment['id'],
        ]);

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $creditCard = $this->fakeCreditCard();
            $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
            $order = $this->fakeOrder();

            $paidUntilSubDays = rand(1, 29);

            $subscriptionData = [
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $userId,
                'order_id' => $order['id'],
                'updated_at' => null,
                'type' => Subscription::TYPE_SUBSCRIPTION,
                'canceled_on' => null,
                'is_active' => false,
                'start_date' => Carbon::now()->subDays($paidUntilSubDays + 2),
                'paid_until' => Carbon::now()->subDays($paidUntilSubDays),
                'cancellation_reason' => null
            ];

            $paymentData = [
                'payment_method_id' => $paymentMethod['id'],
                'total_refunded' => null,
                'deleted_at' => null,
                'created_at' => Carbon::now()->subDays($paidUntilSubDays)
            ];

            if ($this->faker->randomElement([true, false])) {
                $paymentData['status'] = 'failed';
                $paymentData['total_paid'] = 0;

            } else {
                $paymentData['status'] = 'succeeded';
                $paymentData['total_paid'] = $this->faker->numberBetween(0, 1000);
            }

            $payment = $this->fakePayment($paymentData);

            if ($paymentData['status'] == 'failed') {
                $subscriptionData['failed_payment_id'] = $payment['id'];
            }

            $subscription = $this->fakeSubscription($subscriptionData);

            if ($paymentData['status'] == 'failed' && count($subscriptions) < $limit) {
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
                            'order_id' => true,
                            'failed_payment_id' => true,
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
                        ],
                        'failedPayment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ];
            }

            $subscriptionOnePayment = $this->fakeSubscriptionPayment([
                'subscription_id' => $subscription['id'],
                'payment_id' => $payment['id'],
            ]);
        }

        $response = $this->call(
            'GET',
            '/failed-billing',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'type' => Subscription::TYPE_SUBSCRIPTION,
                'big_date_time' => Carbon::now()->toDateTimeString(),
                'small_date_time' => Carbon::now()->subDays(30)->toDateTimeString(),
            ]
        );

        $this->assertEquals(
            $subscriptions,
            $response->decodeResponseJson('data')
        );
    }

    public function test_pull_failed_billing_paymentplans()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $page = 1;
        $limit = 10;
        $nrSubscriptions = $this->faker->numberBetween(15, 25);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // failed billing subscription with payment date outside of request interval
        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null,
            'status' => 'failed',
            'total_paid' => 0,
            'created_at' => Carbon::now()->subDays(33)
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_SUBSCRIPTION,
            'canceled_on' => null,
            'is_active' => false,
            'start_date' => Carbon::now()->subDays(35),
            'paid_until' => Carbon::now()->subDays(33),
            'failed_payment_id' => $payment['id'],
        ]);

        $subscriptionOnePayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscription['id'],
            'payment_id' => $payment['id'],
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // failed billing payment plan with payment date outside of request interval
        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null,
            'status' => 'failed',
            'total_paid' => 0,
            'created_at' => Carbon::now()->subDays(34)
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_PAYMENT_PLAN,
            'canceled_on' => null,
            'is_active' => false,
            'start_date' => Carbon::now()->subDays(36),
            'paid_until' => Carbon::now()->subDays(34),
            'failed_payment_id' => $payment['id'],
        ]);

        $subscriptionOnePayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscription['id'],
            'payment_id' => $payment['id'],
        ]);

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $creditCard = $this->fakeCreditCard();
            $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
            $order = $this->fakeOrder();

            $paidUntilSubDays = rand(1, 29);

            $subscriptionData = [
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $userId,
                'order_id' => $order['id'],
                'updated_at' => null,
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'canceled_on' => null,
                'is_active' => false,
                'start_date' => Carbon::now()->subDays($paidUntilSubDays + 2),
                'paid_until' => Carbon::now()->subDays($paidUntilSubDays),
                'cancellation_reason' => null
            ];

            $paymentData = [
                'payment_method_id' => $paymentMethod['id'],
                'total_refunded' => null,
                'deleted_at' => null,
                'created_at' => Carbon::now()->subDays($paidUntilSubDays)
            ];

            if ($this->faker->randomElement([true, false])) {
                $paymentData['status'] = 'failed';
                $paymentData['total_paid'] = 0;

            } else {
                $paymentData['status'] = 'succeeded';
                $paymentData['total_paid'] = $this->faker->numberBetween(0, 1000);
            }

            $payment = $this->fakePayment($paymentData);

            if ($paymentData['status'] == 'failed') {
                $subscriptionData['failed_payment_id'] = $payment['id'];
            }

            $subscription = $this->fakeSubscription($subscriptionData);

            if ($paymentData['status'] == 'failed' && count($subscriptions) < $limit) {
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
                            'order_id' => true,
                            'failed_payment_id' => true,
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
                        ],
                        'failedPayment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ];
            }

            $subscriptionOnePayment = $this->fakeSubscriptionPayment([
                'subscription_id' => $subscription['id'],
                'payment_id' => $payment['id'],
            ]);
        }

        $response = $this->call(
            'GET',
            '/failed-billing',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'type' => Subscription::TYPE_PAYMENT_PLAN,
                'big_date_time' => Carbon::now()->toDateTimeString(),
                'small_date_time' => Carbon::now()->subDays(30)->toDateTimeString(),
            ]
        );

        $this->assertEquals(
            $subscriptions,
            $response->decodeResponseJson('data')
        );
    }

    public function test_pull_failed_billing_subscriptions_csv()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $email = $this->faker->email;
        $userId = $this->createAndLogInNewUser($email);

        $page = 1;
        $limit = 2;
        $nrSubscriptions = $this->faker->numberBetween(15, 25);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // failed billing subscription with payment date outside of request interval
        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null,
            'status' => 'failed',
            'total_paid' => 0,
            'created_at' => Carbon::now()->subDays(33)
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_SUBSCRIPTION,
            'canceled_on' => null,
            'is_active' => false,
            'start_date' => Carbon::now()->subDays(35),
            'paid_until' => Carbon::now()->subDays(33),
            'failed_payment_id' => $payment['id'],
        ]);

        $subscriptionOnePayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscription['id'],
            'payment_id' => $payment['id'],
        ]);

        $creditCard = $this->fakeCreditCard();
        $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
        $order = $this->fakeOrder();

        // failed billing payment plan with payment date outside of request interval
        $payment = $this->fakePayment([
            'payment_method_id' => $paymentMethod['id'],
            'total_refunded' => null,
            'deleted_at' => null,
            'status' => 'failed',
            'total_paid' => 0,
            'created_at' => Carbon::now()->subDays(34)
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => $paymentMethod['id'],
            'user_id' => $userId,
            'order_id' => $order['id'],
            'updated_at' => null,
            'type' => Subscription::TYPE_PAYMENT_PLAN,
            'canceled_on' => null,
            'is_active' => false,
            'start_date' => Carbon::now()->subDays(36),
            'paid_until' => Carbon::now()->subDays(34),
            'failed_payment_id' => $payment['id'],
        ]);

        $subscriptionOnePayment = $this->fakeSubscriptionPayment([
            'subscription_id' => $subscription['id'],
            'payment_id' => $payment['id'],
        ]);

        $subscriptions = [];

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $product = $this->fakeProduct([
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]);
            $creditCard = $this->fakeCreditCard();
            $paymentMethod = $this->fakePaymentMethod(['credit_card_id' => $creditCard['id']]);
            $order = $this->fakeOrder();

            $paidUntilSubDays = rand(1, 29);

            $subscriptionData = [
                'product_id' => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $userId,
                'order_id' => $order['id'],
                'updated_at' => null,
                'type' => Subscription::TYPE_SUBSCRIPTION,
                'canceled_on' => null,
                'is_active' => false,
                'start_date' => Carbon::now()->subDays($paidUntilSubDays + 2),
                'paid_until' => Carbon::now()->subDays($paidUntilSubDays)
            ];

            $paymentData = [
                'payment_method_id' => $paymentMethod['id'],
                'total_refunded' => null,
                'deleted_at' => null,
                'created_at' => Carbon::now()->subDays($paidUntilSubDays)
            ];

            if (count($subscriptions) < $limit) {
                $paymentData['status'] = 'failed';
                $paymentData['total_paid'] = 0;
                $paymentData['message'] = 'Invalid card';

            } else {
                $paymentData['status'] = 'succeeded';
                $paymentData['total_paid'] = $this->faker->numberBetween(0, 1000);
            }

            $payment = $this->fakePayment($paymentData);

            if ($paymentData['status'] == 'failed') {
                $subscriptionData['failed_payment_id'] = $payment['id'];
            }

            $subscription = $this->fakeSubscription($subscriptionData);

            if ($paymentData['status'] == 'failed' && count($subscriptions) < $limit) {
                $subscriptions[] = [
                    $subscription['id'],
                    $subscription['total_price'],
                    $email,
                    $order['id'],
                    $product['id'],
                    $product['name'],
                    $product['sku'],
                    $payment['id'],
                    $payment['status'],
                    $payment['message'],
                    $payment['created_at']
                ];
            }

            $subscriptionOnePayment = $this->fakeSubscriptionPayment([
                'subscription_id' => $subscription['id'],
                'payment_id' => $payment['id'],
            ]);
        }

        $fp = fopen('php://temp', 'r+');

        fputcsv(
            $fp,
            [
                'Subscription ID',
                'Subscription Total Price',
                'Order ID',
                'Email',
                'Product ID',
                'Product Name',
                'Product SKU',
                'Payment ID',
                'Payment Status',
                'Payment Message',
                'Payment Date',
            ]
        );

        foreach ($subscriptions as $subscription) {
            fputcsv($fp, $subscription);
        }

        rewind($fp);

        $data = fread($fp, 1048576);

        fclose($fp);

        $response = $this->call(
            'GET',
            '/failed-billing',
            [
                'page' => $page,
                'limit' => 10,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc',
                'type' => Subscription::TYPE_SUBSCRIPTION,
                'big_date_time' => Carbon::now()->toDateTimeString(),
                'small_date_time' => Carbon::now()->subDays(30)->toDateTimeString(),
                'csv' => true,
            ]
        );

        ob_start();
        $response->send();
        $text = ob_get_clean();

        $this->assertEquals($data, $text);
    }
}
