<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderJsonControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();

        $cartService = $this->app->make(CartService::class);
        $cartService->clearCart();
    }

    public function test_delete()
    {
        $order = $this->fakeOrder();

        $results = $this->call('DELETE', '/order/' . $order['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertSoftDeleted(
            'ecommerce_orders',
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
                [
                    'title' => 'Not found.',
                    'detail' => 'Delete failed, order not found with id: ' . $randomId,
                ]
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
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);
        $brand = 'drumeo';

        $orderUser = $this->fakeUser();

        $order = $this->fakeOrder(
            [
                'brand' => $brand,
                'user_id' => $orderUser['id'],
                'customer_id' => null,
                'shipping_address_id' => null,
                'billing_address_id' => null,
                'deleted_at' => null
            ]
        );

        $newDue = $this->faker->numberBetween();
        $newNote = 'hello';

        $response = $this->call(
            'PATCH',
            '/order/' . $order['id'],
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $newDue,
                        'note' => $newNote,
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
                            'updated_at' => Carbon::now()
                                ->toDateTimeString(),
                            'total_due' => $newDue,
                            'note' => $newNote,
                        ]
                    ),
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $orderUser['id'],
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $orderUser['id'],
                        'attributes' => [
                            'email' => $orderUser['email']
                        ]
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            array_merge(
                $order,
                [
                    'updated_at' => Carbon::now()
                        ->toDateTimeString(),
                    'total_due' => $newDue,
                    'note' => $newNote,
                ]
            )
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $brand,
                'resource_name' => Order::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_UPDATE,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_ADMIN,
            ]
        );
    }

    public function test_update_order_validation()
    {
        $order = $this->fakeOrder(
            [
                'user_id' => null,
                'customer_id' => null,
                'shipping_address_id' => null,
                'billing_address_id' => null,
                'deleted_at' => null
            ]
        );

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

    public function test_update_order_items()
    {
        $userEmail = $this->faker->email;
        $userId = $this->createAndLogInNewUser($userEmail);
        $brand = 'drumeo';

        $productOnePrice = $this->faker->randomFloat(2, 50, 90);
        $productOneQuantity = 1;

        $productTwoPrice = $this->faker->randomFloat(2, 50, 90);
        $productTwoInitialQuantity = 3;

        $orderInitialProductDue =
            round($productOnePrice * $productOneQuantity + $productTwoPrice * $productTwoInitialQuantity, 2);
        $orderInitialTaxesDue = $this->faker->randomFloat(2, 3, 5);
        $orderInitialShippingDue = $this->faker->randomFloat(2, 3, 5);
        $orderInitialTotalDue = round($orderInitialProductDue + $orderInitialTaxesDue + $orderInitialShippingDue, 2);
        $orderInitialTotalPaid = $orderInitialTotalDue;

        $order = $this->fakeOrder(
            [
                'brand' => $brand,
                'user_id' => $userId,
                'customer_id' => null,
                'shipping_address_id' => null,
                'billing_address_id' => null,
                'deleted_at' => null,
                'total_due' => $orderInitialTotalDue,
                'product_due' => $orderInitialProductDue,
                'taxes_due' => $orderInitialTaxesDue,
                'shipping_due' => $orderInitialShippingDue,
                'finance_due' => 0,
                'total_paid' => $orderInitialTotalPaid,
            ]
        );

        $productOne = $this->fakeProduct(
            [
                'active' => 1,
                'is_physical' => false,
                'stock' => $this->faker->numberBetween(5, 100),
                'price' => $productOnePrice,
            ]
        );

        $orderItemOne = $this->fakeOrderItem(
            [
                'order_id' => $order['id'],
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'weight' => 0,
                'initial_price' => $productOne['price'],
                'total_discounted' => 0,
                'final_price' => $productOne['price']
            ]
        );

        $productTwo = $this->fakeProduct(
            [
                'active' => 1,
                'is_physical' => 1,
                'weight' => 0.20,
                'stock' => $this->faker->numberBetween(5, 100),
                'price' => $productTwoPrice,
            ]
        );

        $orderItemTwo = $this->fakeOrderItem(
            [
                'order_id' => $order['id'],
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoInitialQuantity,
                'weight' => $productTwo['weight'],
                'initial_price' => $productTwo['price'],
                'total_discounted' => 0,
                'final_price' => $productTwo['price'] * $productTwoInitialQuantity
            ]
        );

        $productTwoNewQuantity = 2;
        $orderNewProductDue =
            round($productOnePrice * $productOneQuantity + $productTwoPrice * $productTwoNewQuantity, 2);
        $orderNewTaxesDue = $this->faker->randomFloat(2, 3, 5);
        $orderNewShippingDue = $this->faker->randomFloat(2, 3, 5);
        $orderNewTotalDue = round($orderNewProductDue + $orderNewTaxesDue + $orderNewShippingDue, 2);
        $orderNewTotalPaid = $orderNewTotalDue;

        $response = $this->call(
            'PATCH',
            '/order/' . $order['id'],
            [
                'data' => [
                    'type' => 'order',
                    'attributes' => [
                        'total_due' => $orderNewTotalDue,
                        'product_due' => $orderNewProductDue,
                        'taxes_due' => $orderNewTaxesDue,
                        'shipping_due' => $orderNewShippingDue,
                        'finance_due' => 0,
                        'total_paid' => $orderNewTotalPaid,
                    ],
                    'relationships' => [
                        'orderItems' => [
                            'data' => [
                                [
                                    'type' => 'orderItem',
                                    'id' => $orderItemOne['id'],
                                ],
                                [
                                    'type' => 'orderItem',
                                    'id' => $orderItemTwo['id'],
                                ],
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'orderItem',
                        'id' => $orderItemTwo['id'],
                        'attributes' => [
                            'quantity' => $productTwoNewQuantity,
                            'final_price' => $productTwo['price'] * $productTwoNewQuantity,
                        ]
                    ]
                ]
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_orders',
            [
                'total_due' => $orderNewTotalDue,
                'product_due' => $orderNewProductDue,
                'taxes_due' => $orderNewTaxesDue,
                'shipping_due' => $orderNewShippingDue,
                'finance_due' => 0,
                'total_paid' => $orderNewTotalPaid,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_items',
            [
                'product_id' => $productOne['id'],
                'quantity' => $productOneQuantity,
                'initial_price' => $productOne['price'],
                'final_price' => $productOne['price'] * $productOneQuantity,
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_items',
            [
                'product_id' => $productTwo['id'],
                'quantity' => $productTwoNewQuantity,
                'initial_price' => $productTwo['price'],
                'final_price' => $productTwo['price'] * $productTwoNewQuantity,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $brand,
                'resource_name' => Order::class,
                'resource_id' => 1,
                'action_name' => ActionLogService::ACTION_UPDATE,
                'actor' => $userEmail,
                'actor_id' => $userId,
                'actor_role' => ActionLogService::ROLE_USER,
            ]
        );
    }

    public function test_show_decorated_order()
    {
        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'updated_at' => null
            ]
        );

        $address = $this->fakeAddress(
            [
                'type' => Address::SHIPPING_ADDRESS_TYPE,
                'user_id' => $userId
            ]
        );

        $order = $this->fakeOrder(
            [
                'deleted_at' => null,
                'updated_at' => null,
                'billing_address_id' => null,
                'user_id' => null,
                'shipping_address_id' => $address['id'],
                'user_id' => $userId
            ]
        );

        $orderItem = $this->fakeOrderItem(
            [
                'order_id' => $order['id'],
                'product_id' => $product['id'],
                'updated_at' => null
            ]
        );

        $due = $this->faker->randomFloat(2, 100, 1000);
        $paid = $this->faker->randomFloat(2, 50, 90);
        $refunded = $this->faker->randomFloat(2, 10, 30);

        $creditCard = $this->fakeCreditCard();

        $billingAddress = $this->fakeAddress(
            [
                'type' => Address::BILLING_ADDRESS_TYPE
            ]
        );

        $paymentMethod = $this->fakePaymentMethod(
            [
                'method_id' => $creditCard['id'],
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'billing_address_id' => $billingAddress['id']
            ]
        );

        $payment = $this->fakePayment(
            [
                'payment_method_id' => $paymentMethod['id'],
                'total_due' => $due,
                'total_paid' => $paid,
                'total_refunded' => $refunded,
                'deleted_at' => null,
                'updated_at' => null
            ]
        );

        $refund = $this->fakeRefund(
            [
                'payment_amount' => $due,
                'refunded_amount' => $refunded,
                'payment_id' => $payment['id'],
            ]
        );

        $orderPayment = $this->fakeOrderPayment(
            [
                'order_id' => $order['id'],
                'payment_id' => $payment['id'],
            ]
        );

        $subscription = $this->fakeSubscription(
            [
                'product_id' => $product['id'],
                'order_id' => $order['id'],
                'payment_method_id' => null,
                'updated_at' => null,
                'user_id' => $userId,
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            ]
        );

        //
        $otherOrder = $this->fakeOrder(
            [
                'shipping_address_id' => $address['id']
            ]
        );

        $otherOrderItem = $this->fakeOrderItem(
            [
                'order_id' => $otherOrder['id'],
                'product_id' => $product['id'],
            ]
        );

        $otherPayment = $this->fakePayment();

        $otherRefund = $this->fakeRefund(
            [
                'payment_id' => $otherPayment['id'],
            ]
        );

        $otherOrderPayment = $this->fakeOrderPayment(
            [
                'order_id' => $otherOrder['id'],
                'payment_id' => $payment['id'],
            ]
        );

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
        ];

        $response = $this->call(
            'GET',
            '/order/' . $order['id']
        );

        $this->assertEquals(
            $expected['data'],
            $response->decodeResponseJson()['data']
        );

        $this->assertIncludes(
            [
                [
                    'type' => 'product',
                    'id' => '1',
                    'attributes' => array_diff_key(
                        $product,
                        [
                            'id' => true,
                        ]
                    )
                ],

            ],
            $response->decodeResponseJson()['included']
        );
    }

    public function test_pull_orders()
    {
        $page = 1;
        $limit = 10;
        $nrOrders = 10;

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        $expectedData = [];

        for ($i = 0; $i < $nrOrders; $i++) {

            $user = $this->fakeUser();

            $address = $this->fakeAddress(
                [
                    'type' => Address::SHIPPING_ADDRESS_TYPE,
                    'user_id' => $user['id']
                ]
            );

            $order = $this->fakeOrder(
                [
                    'deleted_at' => null,
                    'updated_at' => null,
                    'billing_address_id' => null,
                    'user_id' => $user['id'],
                    'shipping_address_id' => $address['id']
                ]
            );

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                ]
            );

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
                'page' => $page,
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

    public function test_pull_orders_between_start_date_and_end_date()
    {
        $page = 1;
        $limit = 10;

        $user = $this->fakeUser();

        $address = $this->fakeAddress(
            [
                'type' => Address::SHIPPING_ADDRESS_TYPE,
                'user_id' => $user['id']
            ]
        );

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        $orderOutOfRange = $this->fakeOrder(
            [
                'created_at' => Carbon::now()
                    ->subMonth(1),
                'deleted_at' => null,
                'updated_at' => null,
                'billing_address_id' => null,
                'user_id' => $user['id'],
                'shipping_address_id' => $address['id']
            ]
        );

        $orderItemOutOfRange = $this->fakeOrderItem(
            [
                'order_id' => $orderOutOfRange['id'],
                'product_id' => $product['id'],
            ]
        );

        $orderInRange = $this->fakeOrder(
            [
                'created_at' => Carbon::now()
                    ->subDay(1),
                'deleted_at' => null,
                'updated_at' => null,
                'billing_address_id' => null,
                'user_id' => $user['id'],
                'shipping_address_id' => $address['id']
            ]
        );

        $orderItemInRange = $this->fakeOrderItem(
            [
                'order_id' => $orderInRange['id'],
                'product_id' => $product['id'],
            ]
        );

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
                'page' => $page,
                'limit' => $limit,
                'start-date' => Carbon::now()
                    ->subDay(2)
                    ->toDateTimeString(),
                'end-date' => Carbon::now()
                    ->toDateTimeString()
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

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        $expectedData = [];

        for ($i = 0; $i < $nrOrders; $i++) {

            $user = $this->fakeUser();

            $address = $this->fakeAddress(
                [
                    'type' => Address::SHIPPING_ADDRESS_TYPE,
                    'user_id' => $user['id']
                ]
            );

            $order = $this->fakeOrder(
                [
                    'deleted_at' => null,
                    'updated_at' => null,
                    'billing_address_id' => null,
                    'user_id' => $user['id'],
                    'shipping_address_id' => $address['id'],
                    'brand' => $this->faker->randomElement($brands)
                ]
            );

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                ]
            );

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

            $address = $this->fakeAddress(
                [
                    'type' => Address::SHIPPING_ADDRESS_TYPE
                ]
            );

            $order = $this->fakeOrder(
                [
                    'deleted_at' => null,
                    'updated_at' => null,
                    'billing_address_id' => null,
                    'user_id' => null,
                    'shipping_address_id' => $address['id'],
                    'brand' => $this->faker->word() . $this->faker->word()
                ]
            );

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                ]
            );
        }

        $response = $this->call(
            'GET',
            '/orders',
            [
                'page' => $page,
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

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        $expectedData = [];

        for ($i = 0; $i < $nrOrders; $i++) {

            $user = $this->fakeUser();

            $address = $this->fakeAddress(
                [
                    'type' => Address::SHIPPING_ADDRESS_TYPE,
                    'user_id' => $user['id']
                ]
            );

            $order = $this->fakeOrder(
                [
                    'deleted_at' => null,
                    'updated_at' => null,
                    'billing_address_id' => null,
                    'user_id' => $user['id'],
                    'shipping_address_id' => $address['id'],
                ]
            );

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                ]
            );

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

            $address = $this->fakeAddress(
                [
                    'type' => Address::SHIPPING_ADDRESS_TYPE
                ]
            );

            $order = $this->fakeOrder(
                [
                    'deleted_at' => null,
                    'updated_at' => null,
                    'billing_address_id' => null,
                    'user_id' => null,
                    'shipping_address_id' => $address['id'],
                    'brand' => $this->faker->randomElement($brands)
                ]
            );

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                ]
            );
        }

        $response = $this->call(
            'GET',
            '/orders',
            [
                'page' => $page,
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
