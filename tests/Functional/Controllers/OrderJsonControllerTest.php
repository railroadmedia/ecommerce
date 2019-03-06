<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Controllers\OrderJsonController;
use PHPUnit\Framework\TestCase;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderJsonControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_delete()
    {
        $order = $this->fakeOrder();

        $results = $this->call('DELETE', '/order/' . $order['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertSoftDeleted(
            ConfigService::$tableOrder,
            [
                'id' => $order['id']
            ]
        );
    }

    public function test_delete_not_existing_order()
    {
        $randomId = $this->faker->randomNumber();

        $results = $this->call('DELETE', '/order/' . $randomId);

        // assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                'title' => 'Not found.',
                'detail' => 'Delete failed, order not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_update_not_existing_order()
    {
        $results = $this->call('PATCH', '/order/' . rand());

        $this->assertEquals(404, $results->getStatusCode());
    }

    public function test_update_order()
    {
        $order = $this->fakeOrder([
            'user_id' => null,
            'customer_id' => null,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'deleted_on' => null
        ]);

        $newDue = $this->faker->numberBetween();

        $response = $this->call(
            'PATCH',
            '/order/' . $order['id'],
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $newDue
                    ]
                ]
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            [
                'data' => [
                    'type' => 'order',
                    'id' => $order['id'],
                    'attributes' => array_merge(
                        array_diff_key(
                            $order,
                            [
                                'id' => true,
                                'user_id' => true,
                                'customer_id' => true,
                                'shipping_address_id' => true,
                                'billing_address_id' => true,
                                'total_due' => true
                            ]
                        ),
                        [
                            'updated_at' => Carbon::now()->toDateTimeString(),
                            'total_due' => $newDue
                        ]
                    )
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            array_merge(
                $order,
                [
                    'updated_at' => Carbon::now()->toDateTimeString(),
                    'total_due' => $newDue
                ]
            )
        );
    }

    public function test_update_order_validation()
    {
        $order = $this->fakeOrder([
            'user_id' => null,
            'customer_id' => null,
            'shipping_address_id' => null,
            'billing_address_id' => null,
            'deleted_on' => null
        ]);

        $response = $this->call(
            'PATCH',
            '/order/' . $order['id'],
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => -110
                    ]
                ]
            ]
        );

        $this->assertEquals(422, $response->getStatusCode());

        $this->assertEquals(
            [
                [
                    'title' => 'Validation failed.',
                    'source' => 'data.attributes.total_due',
                    'detail' => 'The total due must be at least 0.'
                ]
            ],
            $response->decodeResponseJson('errors')
        );
    }

    public function test_show_decorated_order()
    {
        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $address = $this->fakeAddress([
            'type' => ConfigService::$shippingAddressType,
            'user_id' => $userId
        ]);

        $order = $this->fakeOrder([
            'deleted_on' => null,
            'updated_at' => null,
            'billing_address_id' => null,
            'user_id' => null,
            'shipping_address_id' => $address['id'],
            'user_id' => $userId
        ]);

        $orderItem = $this->fakeOrderItem([
            'order_id' => $order['id'],
            'product_id' => $product['id'],
            'updated_at' => null
        ]);

        $due = $this->faker->randomFloat(2, 100, 1000);
        $paid = $this->faker->randomFloat(2, 50, 90);
        $refunded = $this->faker->randomFloat(2, 10, 30);

        $payment = $this->fakePayment([
            'total_due' => $due,
            'total_paid' => $paid,
            'total_refunded' => $refunded,
            'deleted_on' => null,
            'updated_at' => null
        ]);

        $refund = $this->fakeRefund([
            'payment_amount' => $due,
            'refunded_amount' => $refunded,
            'payment_id' => $payment['id'],
        ]);

        $orderPayment = $this->fakeOrderPayment([
            'order_id' => $order['id'],
            'payment_id' => $payment['id'],
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'order_id' => $order['id'],
            'payment_method_id' => null,
            'updated_at' => null,
            'user_id' => $userId
        ]);

        //
        $otherOrder = $this->fakeOrder([
            'shipping_address_id' => $address['id']
        ]);

        $otherOrderItem = $this->fakeOrderItem([
            'order_id' => $otherOrder['id'],
            'product_id' => $product['id'],
        ]);

        $otherPayment = $this->fakePayment();

        $otherRefund = $this->fakeRefund([
            'payment_id' => $otherPayment['id'],
        ]);

        $otherOrderPayment = $this->fakeOrderPayment([
            'order_id' => $otherOrder['id'],
            'payment_id' => $payment['id'],
        ]);

        $expected = [
            'data' => [
                'type' => 'order',
                'id' => $order['id'],
                'attributes' => array_diff_key(
                    $order,
                    [
                        'id' => true,
                        'user_id' => true,
                        'customer_id' => true,
                        'shipping_address_id' => true,
                        'billing_address_id' => true
                    ]
                ),
                'relationships' => [
                    'payments' => [
                        'data' => [
                            [
                                'type' => 'payment',
                                'id' => $payment['id'],
                            ]
                        ]
                    ],
                    'refunds' => [
                        'data' => [
                            [
                                'type' => 'refund',
                                'id' => $refund['id'],
                            ]
                        ]
                    ],
                    'subscriptions' => [
                        'data' => [
                            [
                                'type' => 'subscription',
                                'id' => $subscription['id'],
                            ]
                        ]
                    ],
                    'paymentPlans' => [
                        'data' => []
                    ],
                    'orderItem' => [
                        'data' => [
                            [
                                'type' => 'orderItem',
                                'id' => $orderItem['id'],
                            ]
                        ]
                    ],
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => $userId,
                        ]
                    ],
                    'shippingAddress' => [
                        'data' => [
                            'type' => 'address',
                            'id' => $address['id'],
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'payment',
                    'id' => $payment['id'],
                    'attributes' => array_diff_key(
                        $payment,
                        [
                            'id' => true,
                            'payment_method_id' => true
                        ]
                    )
                ],
                [
                    'type' => 'user',
                    'id' => $userId,
                    'attributes' => []
                ],
                [
                    'type' => 'orderItem',
                    'id' => $orderItem['id'],
                    'attributes' => array_diff_key(
                        $orderItem,
                        [
                            'id' => true,
                            'order_id' => true,
                            'product_id' => true
                        ]
                    )
                ],
                [
                    'type' => 'address',
                    'id' => $address['id'],
                    'attributes' => array_diff_key(
                        $address,
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
                ],
                [
                    'type' => 'product',
                    'id' => $product['id'],
                    'attributes' => []
                ],
                [
                    'type' => 'refund',
                    'id' => $refund['id'],
                    'attributes' => array_diff_key(
                        $refund,
                        [
                            'id' => true,
                            'payment_id' => true
                        ]
                    ),
                    'relationships' => [
                        'payment' => [
                            'data' => [
                                'type' => 'payment',
                                'id' => $payment['id']
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'subscription',
                    'id' => $subscription['id'],
                    'attributes' => array_diff_key(
                        $subscription,
                        [
                            'id' => true,
                            'user_id' => true,
                            'customer_id' => true,
                            'order_id' => true,
                            'product_id' => true,
                            'payment_method_id' => true
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
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->call(
            'GET',
            '/order/' . $order['id']
        );

        $this->assertEquals(
            $expected,
            $response->decodeResponseJson()
        );
    }

    public function test_pull_orders()
    {
        $page = 1;
        $limit = 10;
        $nrOrders = 10;

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $expectedData = [];

        for ($i = 0; $i < $nrOrders; $i++) {

            $user = $this->fakeUser();

            $address = $this->fakeAddress([
                'type' => ConfigService::$shippingAddressType,
                'user_id' => $user['id']
            ]);

            $order = $this->fakeOrder([
                'deleted_on' => null,
                'updated_at' => null,
                'billing_address_id' => null,
                'user_id' => $user['id'],
                'shipping_address_id' => $address['id']
            ]);

            $orderItem = $this->fakeOrderItem([
                'order_id' => $order['id'],
                'product_id' => $product['id'],
            ]);

            $expectedData[] = [
                'type' => 'order',
                'id' => $order['id'],
                'attributes' => array_diff_key(
                    $order,
                    [
                        'id' => true,
                        'user_id' => true,
                        'customer_id' => true,
                        'shipping_address_id' => true,
                        'billing_address_id' => true
                    ]
                ),
                'relationships' => [
                    'orderItem' => [
                        'data' => [
                            [
                                'type' => 'orderItem',
                                'id' => $orderItem['id'],
                            ]
                        ]
                    ],
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => $user['id'],
                        ]
                    ],
                    'shippingAddress' => [
                        'data' => [
                            'type' => 'address',
                            'id' => $address['id'],
                        ]
                    ]
                ]
            ];
        }

        $response = $this->call(
            'GET',
            '/orders',
            [
                'page'  => $page,
                'limit' => $limit,
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            $expectedData,
            $decodedResponse['data']
        );
    }

    public function test_pull_orders_between_start_date_and_end_date()
    {
        $page = 1;
        $limit = 10;

        $user = $this->fakeUser();

        $address = $this->fakeAddress([
            'type' => ConfigService::$shippingAddressType,
            'user_id' => $user['id']
        ]);

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $orderOutOfRange = $this->fakeOrder([
            'created_at' => Carbon::now()->subMonth(1),
            'deleted_on' => null,
            'updated_at' => null,
            'billing_address_id' => null,
            'user_id' => $user['id'],
            'shipping_address_id' => $address['id']
        ]);

        $orderItemOutOfRange = $this->fakeOrderItem([
            'order_id' => $orderOutOfRange['id'],
            'product_id' => $product['id'],
        ]);

        $orderInRange = $this->fakeOrder([
            'created_at' => Carbon::now()->subDay(1),
            'deleted_on' => null,
            'updated_at' => null,
            'billing_address_id' => null,
            'user_id' => $user['id'],
            'shipping_address_id' => $address['id']
        ]);

        $orderItemInRange = $this->fakeOrderItem([
            'order_id' => $orderInRange['id'],
            'product_id' => $product['id'],
        ]);

        $expectedData[] = [
            'type' => 'order',
            'id' => $orderInRange['id'],
            'attributes' => array_diff_key(
                $orderInRange,
                [
                    'id' => true,
                    'user_id' => true,
                    'customer_id' => true,
                    'shipping_address_id' => true,
                    'billing_address_id' => true
                ]
            ),
            'relationships' => [
                'orderItem' => [
                    'data' => [
                        [
                            'type' => 'orderItem',
                            'id' => $orderItemInRange['id'],
                        ]
                    ]
                ],
                'user' => [
                    'data' => [
                        'type' => 'user',
                        'id' => $user['id'],
                    ]
                ],
                'shippingAddress' => [
                    'data' => [
                        'type' => 'address',
                        'id' => $address['id'],
                    ]
                ]
            ]
        ];

        $response = $this->call(
            'GET',
            '/orders',
            [
                'page'  => $page,
                'limit' => $limit,
                'start-date' => Carbon::now()->subDay(2)->toDateTimeString(),
                'end-date' => Carbon::now()->toDateTimeString()
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            $expectedData,
            $decodedResponse['data']
        );
    }

    public function test_pull_orders_multiple_brands()
    {
        $page = 1;
        $limit = 10;
        $nrOrders = 10;
        $brands = [$this->faker->word, $this->faker->word];

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $expectedData = [];

        for ($i = 0; $i < $nrOrders; $i++) {

            $user = $this->fakeUser();

            $address = $this->fakeAddress([
                'type' => ConfigService::$shippingAddressType,
                'user_id' => $user['id']
            ]);

            $order = $this->fakeOrder([
                'deleted_on' => null,
                'updated_at' => null,
                'billing_address_id' => null,
                'user_id' => $user['id'],
                'shipping_address_id' => $address['id'],
                'brand' => $this->faker->randomElement($brands)
            ]);

            $orderItem = $this->fakeOrderItem([
                'order_id' => $order['id'],
                'product_id' => $product['id'],
            ]);

            $expectedData[] = [
                'type' => 'order',
                'id' => $order['id'],
                'attributes' => array_diff_key(
                    $order,
                    [
                        'id' => true,
                        'user_id' => true,
                        'customer_id' => true,
                        'shipping_address_id' => true,
                        'billing_address_id' => true
                    ]
                ),
                'relationships' => [
                    'orderItem' => [
                        'data' => [
                            [
                                'type' => 'orderItem',
                                'id' => $orderItem['id'],
                            ]
                        ]
                    ],
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => $user['id'],
                        ]
                    ],
                    'shippingAddress' => [
                        'data' => [
                            'type' => 'address',
                            'id' => $address['id'],
                        ]
                    ]
                ]
            ];
        }

        for ($i = 0; $i < 3; $i++) {

            $address = $this->fakeAddress([
                'type' => ConfigService::$shippingAddressType
            ]);

            $order = $this->fakeOrder([
                'deleted_on' => null,
                'updated_at' => null,
                'billing_address_id' => null,
                'user_id' => null,
                'shipping_address_id' => $address['id'],
                'brand' => $this->faker->word().$this->faker->word()
            ]);

            $orderItem = $this->fakeOrderItem([
                'order_id' => $order['id'],
                'product_id' => $product['id'],
            ]);
        }

        $response = $this->call(
            'GET',
            '/orders',
            [
                'page'  => $page,
                'limit' => $limit,
                'brands' => $brands,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc'
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            $expectedData,
            $decodedResponse['data']
        );
    }

    public function test_pull_orders_default_brand()
    {
        $page = 1;
        $limit = 10;
        $nrOrders = 10;
        $brands = [$this->faker->word, $this->faker->word];

        $product = $this->fakeProduct([
            'type' => ConfigService::$typeSubscription
        ]);

        $expectedData = [];

        for ($i = 0; $i < $nrOrders; $i++) {

            $user = $this->fakeUser();

            $address = $this->fakeAddress([
                'type' => ConfigService::$shippingAddressType,
                'user_id' => $user['id']
            ]);

            $order = $this->fakeOrder([
                'deleted_on' => null,
                'updated_at' => null,
                'billing_address_id' => null,
                'user_id' => $user['id'],
                'shipping_address_id' => $address['id'],
            ]);

            $orderItem = $this->fakeOrderItem([
                'order_id' => $order['id'],
                'product_id' => $product['id'],
            ]);

            $expectedData[] = [
                'type' => 'order',
                'id' => $order['id'],
                'attributes' => array_diff_key(
                    $order,
                    [
                        'id' => true,
                        'user_id' => true,
                        'customer_id' => true,
                        'shipping_address_id' => true,
                        'billing_address_id' => true
                    ]
                ),
                'relationships' => [
                    'orderItem' => [
                        'data' => [
                            [
                                'type' => 'orderItem',
                                'id' => $orderItem['id'],
                            ]
                        ]
                    ],
                    'user' => [
                        'data' => [
                            'type' => 'user',
                            'id' => $user['id'],
                        ]
                    ],
                    'shippingAddress' => [
                        'data' => [
                            'type' => 'address',
                            'id' => $address['id'],
                        ]
                    ]
                ]
            ];
        }

        for ($i = 0; $i < 3; $i++) {

            $address = $this->fakeAddress([
                'type' => ConfigService::$shippingAddressType
            ]);

            $order = $this->fakeOrder([
                'deleted_on' => null,
                'updated_at' => null,
                'billing_address_id' => null,
                'user_id' => null,
                'shipping_address_id' => $address['id'],
                'brand' => $this->faker->randomElement($brands)
            ]);

            $orderItem = $this->fakeOrderItem([
                'order_id' => $order['id'],
                'product_id' => $product['id'],
            ]);
        }

        $response = $this->call(
            'GET',
            '/orders',
            [
                'page'  => $page,
                'limit' => $limit,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc'
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            $expectedData,
            $decodedResponse['data']
        );
    }
}
