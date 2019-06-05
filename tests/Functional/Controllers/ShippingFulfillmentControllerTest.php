<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingFulfillmentControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_shipstation_mark_fulfilled_csv_no_errors()
    {
        $product1 = $this->fakeProduct();
        $product2 = $this->fakeProduct();

        $order1 = $this->fakeOrder();
        $order2 = $this->fakeOrder();

        $orderItem1 = $this->fakeOrderItem(['order_id' => $order1['id'], 'product_id' => $product1['id']]);
        $orderItem2 = $this->fakeOrderItem(['order_id' => $order2['id'], 'product_id' => $product2['id']]);

        $orderItemFulfillment1 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order1['id'],
                'order_item_id' => $orderItem1['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        $orderItemFulfillment2 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order2['id'],
                'order_item_id' => $orderItem2['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        // create the CSV
        $rows = [];

        $rows[] = [
            'Other',
            'Order - Number',
            'Order Item ID',
            'Random',
            'Shipment - Service',
            'Shipment - Tracking Number',
            'Date - Shipped Date',
        ];

        $rows[] = [
            rand(),
            $order1['id'],
            $orderItem1['id'],
            rand(),
            'USPS First Class Mail',
            '9400110200830260177302',
            '5/29/2019',
        ];

        $rows[] = [
            rand(),
            $order2['id'],
            $orderItem2['id'],
            rand(),
            'RRD ePacket USPS Service',
            'LX631000404US',
            '5/31/2019',
        ];

        $fileName = time() . '_fulfillment.csv';
        $filePath = sys_get_temp_dir() . '/' . $fileName;

        $fp = fopen($filePath, 'w');

        foreach ($rows as $columns) {
            fputcsv($fp, (array)$columns);
        }

        fclose($fp);

        $response = $this->postJson(
            '/fulfillment/mark-fulfilled-csv-upload-shipstation',
            [
                'csv_file' => new UploadedFile(
                    $filePath, $fileName, null, null, null, true
                ),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_order_item_fulfillment',
            array_merge(
                $orderItemFulfillment1,
                [
                    'status' => config('ecommerce.fulfillment_status_fulfilled'),
                    'company' => $rows[1][4],
                    'tracking_number' => $rows[1][5],
                    'fulfilled_on' => Carbon::parse($rows[1][6])
                        ->toDateTimeString(),
                ]
            )
        );

        $this->assertDatabaseHas(
            'ecommerce_order_item_fulfillment',
            array_merge(
                $orderItemFulfillment2,
                [
                    'status' => config('ecommerce.fulfillment_status_fulfilled'),
                    'company' => $rows[2][4],
                    'tracking_number' => $rows[2][5],
                    'fulfilled_on' => Carbon::parse($rows[2][6])
                        ->toDateTimeString(),
                ]
            )
        );
    }

    public function test_shipstation_mark_fulfilled_csv_validation_errors()
    {
        $product1 = $this->fakeProduct();
        $product2 = $this->fakeProduct();

        $order1 = $this->fakeOrder();
        $order2 = $this->fakeOrder();

        $orderItem1 = $this->fakeOrderItem(['order_id' => $order1['id'], 'product_id' => $product1['id']]);
        $orderItem2 = $this->fakeOrderItem(['order_id' => $order2['id'], 'product_id' => $product2['id']]);

        $orderItemFulfillment1 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order1['id'],
                'order_item_id' => $orderItem1['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        $orderItemFulfillment2 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order2['id'],
                'order_item_id' => $orderItem2['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        // create the CSV
        $rows = [];

        $rows[] = [
            'Other',
            'Order - Number',
            'Order Item ID',
            'Random',
            'Shipment - Service',
            'Shipment - Tracking Number',
            'Date - Shipped Date',
        ];

        $rows[] = [
            rand(),
            $order1['id'],
            $orderItem1['id'],
            rand(),
            'USPS First Class Mail',
            '9400110200830260177302',
            '5/29/2019',
        ];

        $rows[] = [
            rand(),
            $order2['id'],
            $orderItem2['id'],
            rand(),
            'RRD ePacket USPS Service',
            'LX631000404US',
            '5/31/2019',
        ];

        $fileName = time() . '_fulfillment.csv';
        $filePath = sys_get_temp_dir() . '/' . $fileName;

        $fp = fopen($filePath, 'w');

        foreach ($rows as $columns) {
            fputcsv($fp, (array)$columns);
        }

        fclose($fp);

        $response = $this->postJson(
            '/fulfillment/mark-fulfilled-csv-upload-shipstation',
            []
        );

        $this->assertEquals(
            [
                "errors" => [
                    [
                        'title' => 'Validation failed.',
                        'source' => 'csv_file',
                        'detail' => 'The csv file field is required.',
                    ],
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_shipstation_mark_fulfilled_csv_column_data_missing()
    {
        $product1 = $this->fakeProduct();
        $product2 = $this->fakeProduct();

        $order1 = $this->fakeOrder();
        $order2 = $this->fakeOrder();

        $orderItem1 = $this->fakeOrderItem(['order_id' => $order1['id'], 'product_id' => $product1['id']]);
        $orderItem2 = $this->fakeOrderItem(['order_id' => $order2['id'], 'product_id' => $product2['id']]);

        $orderItemFulfillment1 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order1['id'],
                'order_item_id' => $orderItem1['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        $orderItemFulfillment2 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order2['id'],
                'order_item_id' => $orderItem2['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        // create the CSV
        $rows = [];

        $rows[] = [
            'Other',
            'Order - Number',
            'Order Item ID',
            'Random',
            'Shipment - Service',
            'Shipment - Tracking Number',
            'Date - Shipped Date',
        ];

        $rows[] = [
            rand(),
            $order1['id'],
        ];

        $rows[] = [
            rand(),
            'LX631000404US',
            '5/31/2019',
        ];

        $fileName = time() . '_fulfillment.csv';
        $filePath = sys_get_temp_dir() . '/' . $fileName;

        $fp = fopen($filePath, 'w');

        foreach ($rows as $columns) {
            fputcsv($fp, (array)$columns);
        }

        fclose($fp);

        $response = $this->postJson(
            '/fulfillment/mark-fulfilled-csv-upload-shipstation',
            [
                'csv_file' => new UploadedFile(
                    $filePath, $fileName, null, null, null, true
                ),
            ]
        );

        $this->assertEquals(
            [
                "success" => true,
                "errors" => [
                    [
                        'title' => 'Data Error',
                        'source' => 'csv_file',
                        'detail' => 'Missing "Shipment - Service" name at row: 2 column: 5. Please review.',
                    ],
                    [
                        'title' => 'Data Error',
                        'source' => 'csv_file',
                        'detail' => 'Missing "Shipment - Service" name at row: 3 column: 5. Please review.',
                    ],
                ]
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_shipstation_mark_fulfilled_csv_one_error()
    {
        $product1 = $this->fakeProduct();
        $product2 = $this->fakeProduct();

        $order1 = $this->fakeOrder();
        $order2 = $this->fakeOrder();

        $orderItem1 = $this->fakeOrderItem(['order_id' => $order1['id'], 'product_id' => $product1['id']]);
        $orderItem2 = $this->fakeOrderItem(['order_id' => $order2['id'], 'product_id' => $product2['id']]);

        $orderItemFulfillment1 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order1['id'],
                'order_item_id' => $orderItem1['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        $orderItemFulfillment2 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order2['id'],
                'order_item_id' => $orderItem2['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        // create the CSV
        $rows = [];

        $rows[] = [
            'Other',
            'Order - Number',
            'Order Item ID',
            'Random',
            'Shipment - Service',
            'Shipment - Tracking Number',
            'Date - Shipped Date',
        ];

        $rows[] = [
            rand(),
            $order1['id'],
            $orderItem1['id'],
            rand(),
            'USPS First Class Mail',
            '9400110200830260177302',
            '5/29/2019',
        ];

        $rows[] = [
            rand(),
            $order2['id'],
            $orderItem2['id'],
            rand(),
            '',
            '',
            '5/31/2019',
        ];

        $fileName = time() . '_fulfillment.csv';
        $filePath = sys_get_temp_dir() . '/' . $fileName;

        $fp = fopen($filePath, 'w');

        foreach ($rows as $columns) {
            fputcsv($fp, (array)$columns);
        }

        fclose($fp);

        $response = $this->postJson(
            '/fulfillment/mark-fulfilled-csv-upload-shipstation',
            [
                'csv_file' => new UploadedFile(
                    $filePath, $fileName, null, null, null, true
                ),
            ]
        );

        $this->assertEquals(
            [
                "success" => true,
                "errors" => [
                    [
                        'title' => 'Data Error',
                        'source' => 'csv_file',
                        'detail' => 'Missing "Shipment - Service" name at row: 3 column: 5. Please review.',
                    ],
                ],
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_shipstation_mark_fulfilled_csv_columns_missing_errors()
    {
        $product1 = $this->fakeProduct();
        $product2 = $this->fakeProduct();

        $order1 = $this->fakeOrder();
        $order2 = $this->fakeOrder();

        $orderItem1 = $this->fakeOrderItem(['order_id' => $order1['id'], 'product_id' => $product1['id']]);
        $orderItem2 = $this->fakeOrderItem(['order_id' => $order2['id'], 'product_id' => $product2['id']]);

        $orderItemFulfillment1 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order1['id'],
                'order_item_id' => $orderItem1['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        $orderItemFulfillment2 = $this->fakeOrderItemFulfillment(
            [
                'order_id' => $order2['id'],
                'order_item_id' => $orderItem2['id'],
                'status' => 'pending',
                'company' => null,
                'tracking_number' => null,
                'fulfilled_on' => null,
                'note' => null,
            ]
        );

        // create the CSV
        $rows = [];

        $rows[] = [
            'Other',
            'Order Item ID',
            'Random',
            'Shipment - Tracking Number',
            'Date - Shipped Date',
        ];

        $rows[] = [
            rand(),
            rand(),
            'USPS First Class Mail',
            '9400110200830260177302',
            '5/29/2019',
        ];

        $rows[] = [
            rand(),
            rand(),
            'RRD ePacket USPS Service',
            'LX631000404US',
            '5/31/2019',
        ];

        $fileName = time() . '_fulfillment.csv';
        $filePath = sys_get_temp_dir() . '/' . $fileName;

        $fp = fopen($filePath, 'w');

        foreach ($rows as $columns) {
            fputcsv($fp, (array)$columns);
        }

        fclose($fp);

        $response = $this->postJson(
            '/fulfillment/mark-fulfilled-csv-upload-shipstation',
            [
                'csv_file' => new UploadedFile(
                    $filePath, $fileName, null, null, null, true
                ),
            ]
        );

        $this->assertEquals(
            [
                "success" => true,
                "errors" => [
                    [
                        'title' => 'Data Error',
                        'source' => 'csv_file',
                        'detail' => 'Missing "Order - Number" at row: 2 column: 1. Please review.',
                    ],
                    [
                        'title' => 'Data Error',
                        'source' => 'csv_file',
                        'detail' => 'Missing "Order - Number" at row: 3 column: 1. Please review.',
                    ],
                ]
            ],
            $response->decodeResponseJson()
        );
    }
}
