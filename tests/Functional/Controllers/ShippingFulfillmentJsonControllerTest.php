<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingFulfillmentJsonControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_index_all()
    {
        $expectedData = [];
        $expectedIncludes = [];

        for ($i = 0; $i < 10; $i++) {
            $address = $this->fakeAddress(
                [
                    'type' => Address::SHIPPING_ADDRESS_TYPE
                ]
            );

            $order = $this->fakeOrder(
                [
                    'shipping_address_id' => $address['id']
                ]
            );

            $product = $this->fakeProduct();

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                ]
            );

            $orderItemFulfillment = $this->fakeOrderItemFulfillment(
                [
                    'order_id' => $order['id'],
                    'order_item_id' => $orderItem['id'],
                    'status' => config('ecommerce.fulfillment_status_pending'),
                    'updated_at' => null,
                    'created_at' => Carbon::now()
                        ->subHours($i),
                ]
            );

            $expectedData[] = [
                'type' => 'fulfillment',
                'id' => $orderItemFulfillment['id'],
                'attributes' => array_diff_key(
                    $orderItemFulfillment,
                    [
                        'id' => true,
                        'order_id' => true,
                        'order_item_id' => true,
                    ]
                ),
                'relationships' => [
                    'order' => [
                        'data' => [
                            'type' => 'order',
                            'id' => $order['id'],
                        ]
                    ],
                    'orderItem' => [
                        'data' => [
                            'type' => 'orderItem',
                            'id' => $orderItem['id'],
                        ]
                    ]
                ]
            ];

            $expectedIncludes[] = [
                'type' => 'product',
                'id' => $product['id'],
                'attributes' => []
            ];

            $expectedIncludes[] = [
                'type' => 'orderItem',
                'id' => $orderItem['id'],
                'attributes' => []
            ];
        }

        $response = $this->call('GET', '/fulfillment');

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            $expectedData,
            $decodedResponse['data']
        );
    }

    public function test_index_between_dates()
    {
        $expectedData = [];
        $expectedIncludes = [];

        for ($i = 0; $i < 10; $i++) {
            $address = $this->fakeAddress(
                [
                    'type' => Address::SHIPPING_ADDRESS_TYPE
                ]
            );

            $order = $this->fakeOrder(
                [
                    'shipping_address_id' => $address['id']
                ]
            );

            $product = $this->fakeProduct();

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                ]
            );
            $orderItemFulfillment = $this->fakeOrderItemFulfillment(
                [
                    'order_id' => $order['id'],
                    'order_item_id' => $orderItem['id'],
                    'status' => config('ecommerce.fulfillment_status_pending'),
                    'updated_at' => null,
                    'created_at' => Carbon::now()
                        ->subDays($i),
                ]
            );

            if ($i > 2) {
                continue;
            }

            $expectedData[] = [
                'type' => 'fulfillment',
                'id' => $orderItemFulfillment['id'],
                'attributes' => array_diff_key(
                    $orderItemFulfillment,
                    [
                        'id' => true,
                        'order_id' => true,
                        'order_item_id' => true,
                    ]
                ),
                'relationships' => [
                    'order' => [
                        'data' => [
                            'type' => 'order',
                            'id' => $order['id'],
                        ]
                    ],
                    'orderItem' => [
                        'data' => [
                            'type' => 'orderItem',
                            'id' => $orderItem['id'],
                        ]
                    ]
                ]
            ];
        }

        $response = $this->call(
            'GET',
            '/fulfillment',
            [
                'small_date_time' => Carbon::now()
                    ->subDays(3),
                'big_date_time' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            $expectedData,
            $decodedResponse['data']
        );
    }

    public function test_index_between_dates_csv()
    {
        $user = $this->fakeUser();

        $expectedData = [];
        $expectedIncludes = [];

        for ($i = 0; $i < 10; $i++) {
            $address = $this->fakeAddress(
                [
                    'type' => Address::SHIPPING_ADDRESS_TYPE
                ]
            );

            $order = $this->fakeOrder(
                [
                    'shipping_address_id' => $address['id'],
                    'user_id' => $user['id'],
                ]
            );

            $product = $this->fakeProduct();

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                ]
            );

            $orderItemFulfillment = $this->fakeOrderItemFulfillment(
                [
                    'order_id' => $order['id'],
                    'order_item_id' => $orderItem['id'],
                    'status' => $this->faker->randomElement(
                        [
                            config('ecommerce.fulfillment_status_pending'),
                            config('ecommerce.fulfillment_status_fulfilled')
                        ]
                    ),
                    'fulfilled_on' => $this->faker->randomElement(
                        [
                            Carbon::now()->toDateTimeString(),
                            null,
                        ]
                    ),
                    'updated_at' => null,
                    'created_at' => Carbon::now()
                        ->subDays($i),
                ]
            );

            if ($i > 2) {
                continue;
            }

            $expectedData[] = [
                'type' => 'fulfillment',
                'id' => $orderItemFulfillment['id'],
                'attributes' => array_diff_key(
                    $orderItemFulfillment,
                    [
                        'id' => true,
                        'order_id' => true,
                        'order_item_id' => true,
                    ]
                ),
                'relationships' => [
                    'order' => [
                        'data' => [
                            'type' => 'order',
                            'id' => $order['id'],
                        ]
                    ],
                    'orderItem' => [
                        'data' => [
                            'type' => 'orderItem',
                            'id' => $orderItem['id'],
                        ]
                    ]
                ]
            ];

            $expectedIncludes[] = [
                'type' => 'order',
                'id' => $order['id'],
                'attributes' => []
            ];

            $expectedIncludes[] = [
                'type' => 'orderItem',
                'id' => $orderItem['id'],
                'attributes' => []
            ];
        }

        $response = $this->call(
            'GET',
            '/fulfillment',
            [
                'small_date_time' => Carbon::now()
                    ->subDays(3),
                'big_date_time' => Carbon::now()
                    ->toDateTimeString(),
                'csv' => true,
            ]
        );

        ob_start();
        $response->send();
        $text = ob_get_clean();

        $texts = explode("\n", $text);

        $rows= [];

        foreach ($texts as $textRowString) {
            $rows[] = str_getcsv($textRowString, ",", '"');
        }

        // todo: data matching tests
        $this->assertEquals(5, count($rows));
    }

    public function test_index_filtered_fulfillments()
    {
        $expectedData = [];
        $expectedIncludes = [];

        for ($i = 0; $i < 10; $i++) {
            $address = $this->fakeAddress(
                [
                    'type' => Address::SHIPPING_ADDRESS_TYPE
                ]
            );

            $order = $this->fakeOrder(
                [
                    'shipping_address_id' => $address['id']
                ]
            );

            $product = $this->fakeProduct();

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                ]
            );

            $orderItemFulfillment = $this->fakeOrderItemFulfillment(
                [
                    'order_id' => $order['id'],
                    'order_item_id' => $orderItem['id'],
                    'status' => $this->faker->randomElement(
                        [
                            config('ecommerce.fulfillment_status_pending'),
                            config('ecommerce.fulfillment_status_fulfilled')
                        ]
                    ),
                    'updated_at' => null
                ]
            );

            if ($orderItemFulfillment['status'] === config('ecommerce.fulfillment_status_pending')) {
                continue;
            }

            $expectedData[] = [
                'type' => 'fulfillment',
                'id' => $orderItemFulfillment['id'],
                'attributes' => array_diff_key(
                    $orderItemFulfillment,
                    [
                        'id' => true,
                        'order_id' => true,
                        'order_item_id' => true,
                    ]
                ),
                'relationships' => [
                    'order' => [
                        'data' => [
                            'type' => 'order',
                            'id' => $order['id'],
                        ]
                    ],
                    'orderItem' => [
                        'data' => [
                            'type' => 'orderItem',
                            'id' => $orderItem['id'],
                        ]
                    ]
                ]
            ];
        }

        $response = $this->call(
            'GET',
            '/fulfillment',
            [
                'status' => [config('ecommerce.fulfillment_status_fulfilled')]
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        $this->assertEquals(
            $expectedData,
            $decodedResponse['data']
        );
    }

    public function test_fulfilled_order()
    {
        $address = $this->fakeAddress(
            [
                'type' => Address::SHIPPING_ADDRESS_TYPE
            ]
        );

        $order = $this->fakeOrder(
            [
                'shipping_address_id' => $address['id']
            ]
        );

        $orderItemOne = $this->fakeOrderItem(
            [
                'order_id' => $order['id']
            ]
        );

        $orderItemFulfillmentOne = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemOne['id'],
                'status' => config('ecommerce.fulfillment_status_pending'),
                'updated_at' => null
            ]
        );

        $orderItemTwo = $this->fakeOrderItem(
            [
                'order_id' => $order['id']
            ]
        );

        $orderItemFulfillmentTwo = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemTwo['id'],
                'status' => config('ecommerce.fulfillment_status_pending'),
                'updated_at' => null
            ]
        );

        $shippingCompany = $this->faker->company;
        $trackingNumber = $this->faker->randomNumber();

        $results = $this->call(
            'PATCH',
            '/fulfillment',
            [
                'order_id' => $order['id'],
                'shipping_company' => $shippingCompany,
                'tracking_number' => $trackingNumber
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillmentOne['id'],
                'order_id' => $order['id'],
                'status' => config('ecommerce.fulfillment_status_fulfilled'),
                'company' => $shippingCompany,
                'tracking_number' => $trackingNumber,
                'fulfilled_on' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillmentTwo['id'],
                'order_id' => $order['id'],
                'status' => config('ecommerce.fulfillment_status_fulfilled'),
                'company' => $shippingCompany,
                'tracking_number' => $trackingNumber,
                'fulfilled_on' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_fulfilled_order_item()
    {
        $address = $this->fakeAddress(
            [
                'type' => Address::SHIPPING_ADDRESS_TYPE
            ]
        );

        $order = $this->fakeOrder(
            [
                'shipping_address_id' => $address['id']
            ]
        );

        $orderItemOne = $this->fakeOrderItem(
            [
                'order_id' => $order['id']
            ]
        );

        $orderItemFulfillmentOne = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemOne['id'],
                'status' => config('ecommerce.fulfillment_status_pending'),
                'updated_at' => null
            ]
        );

        $orderItemTwo = $this->fakeOrderItem(
            [
                'order_id' => $order['id']
            ]
        );

        $orderItemFulfillmentTwo = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemTwo['id'],
                'status' => config('ecommerce.fulfillment_status_pending'),
                'updated_at' => null
            ]
        );

        $shippingCompany = $this->faker->company;
        $trackingNumber = $this->faker->randomNumber();

        $results = $this->call(
            'PATCH',
            '/fulfillment',
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemOne['id'],
                'shipping_company' => $shippingCompany,
                'tracking_number' => $trackingNumber
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillmentOne['id'],
                'order_id' => $order['id'],
                'status' => config('ecommerce.fulfillment_status_fulfilled'),
                'company' => $shippingCompany,
                'tracking_number' => $trackingNumber,
                'fulfilled_on' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillmentTwo['id'],
                'order_id' => $order['id'],
                'status' => config('ecommerce.fulfillment_status_pending'),
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null
            ]
        );
    }

    public function test_delete_order_fulfillments()
    {
        $address = $this->fakeAddress(
            [
                'type' => Address::SHIPPING_ADDRESS_TYPE
            ]
        );

        $order = $this->fakeOrder(
            [
                'shipping_address_id' => $address['id']
            ]
        );

        $orderItemOne = $this->fakeOrderItem(
            [
                'order_id' => $order['id']
            ]
        );

        $orderItemFulfillmentOne = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemOne['id'],
                'status' => config('ecommerce.fulfillment_status_pending'),
                'updated_at' => null
            ]
        );

        $orderItemTwo = $this->fakeOrderItem(
            [
                'order_id' => $order['id']
            ]
        );

        $orderItemFulfillmentTwo = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemTwo['id'],
                'status' => config('ecommerce.fulfillment_status_pending'),
                'updated_at' => null
            ]
        );

        $results = $this->call(
            'DELETE',
            '/fulfillment',
            [
                'order_id' => $order['id'],
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillmentOne['id'],
                'order_id' => $order['id'],
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillmentTwo['id'],
                'order_id' => $order['id'],
            ]
        );
    }

    public function test_delete_order_item_fulfillment()
    {
        $address = $this->fakeAddress(
            [
                'type' => Address::SHIPPING_ADDRESS_TYPE
            ]
        );

        $order = $this->fakeOrder(
            [
                'shipping_address_id' => $address['id']
            ]
        );

        $orderItemOne = $this->fakeOrderItem(
            [
                'order_id' => $order['id']
            ]
        );

        $orderItemFulfillmentOne = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemOne['id'],
                'status' => config('ecommerce.fulfillment_status_pending'),
                'updated_at' => null
            ]
        );

        $orderItemTwo = $this->fakeOrderItem(
            [
                'order_id' => $order['id']
            ]
        );

        $orderItemFulfillmentTwo = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemTwo['id'],
                'status' => config('ecommerce.fulfillment_status_pending'),
                'updated_at' => null
            ]
        );

        $results = $this->call(
            'DELETE',
            '/fulfillment',
            [
                'order_id' => $order['id'],
                'order_item_id' => $orderItemOne['id'],
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillmentOne['id'],
                'order_id' => $order['id'],
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_item_fulfillment',
            [
                'id' => $orderItemFulfillmentTwo['id'],
                'order_id' => $order['id'],
            ]
        );
    }

    public function test_fulfillment_not_exist()
    {
        $results = $this->call(
            'DELETE',
            '/fulfillment',
            [
                'order_id' => rand()
            ]
        );

        $this->assertEquals(422, $results->status());
    }
}
