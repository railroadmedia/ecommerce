<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Controllers\ShippingFulfillmentJsonController;
use PHPUnit\Framework\TestCase;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingFulfillmentJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository
     */
    protected $orderItemFulfillmentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\AddressRepository
     */
    protected $addressRepository;

    public function setUp()
    {
        parent::setUp();
        $this->orderItemFulfillmentRepository = $this->app->make(OrderItemFulfillmentRepository::class);
        $this->orderRepository                = $this->app->make(OrderRepository::class);
        $this->addressRepository              = $this->app->make(AddressRepository::class);
    }

    public function test_index_no_ressults()
    {
        $results = $this->call('GET', '/fulfillment');

        $this->assertEmpty($results->decodeResponseJson('results'));
        $this->assertEquals(0, $results->decodeResponseJson('total_results'));
    }

    public function test_index_all()
    {
        for($i = 0; $i < 10; $i++)
        {
            $shippingAddress = $this->addressRepository->create($this->faker->address([
                'type' => ConfigService::$shippingAddressType
            ]));

            $order                       = $this->orderRepository->create($this->faker->order([
                'shipping_address_id' => $shippingAddress['id']
            ]));
            $shippingAddress['order_id'] = $order['id'];

            $fulfillments[$i]                     = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment([
                'order_id' => $order['id']
            ]))->getArrayCopy();
            $fulfillments[$i]['shipping_address'] = $shippingAddress->getArrayCopy();
        }
        $results = $this->call('GET', '/fulfillment');

        $this->assertEquals($fulfillments, $results->decodeResponseJson('data'));
    }

    public function test_index_filtered_fulfillments()
    {
        $expectedResults = [];
        for($i = 0; $i < 10; $i++)
        {
            $shippingAddress = $this->addressRepository->create($this->faker->address([
                'type' => ConfigService::$shippingAddressType
            ]));

            $order                       = $this->orderRepository->create($this->faker->order([
                'shipping_address_id' => $shippingAddress['id']
            ]));
            $shippingAddress['order_id'] = $order['id'];

            $fulfillments[$i]                     = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment([
                'order_id' => $order['id'],
                'status'   => $this->faker->randomElement([ConfigService::$fulfillmentStatusPending, ConfigService::$fulfillmentStatusFulfilled])
            ]))->getArrayCopy();
            $fulfillments[$i]['shipping_address'] = $shippingAddress->getArrayCopy();
            if($fulfillments[$i]['status'] === ConfigService::$fulfillmentStatusFulfilled)
            {
                $expectedResults[] = $fulfillments[$i];
            }
        }

        $results = $this->call('GET', '/fulfillment', [
            'status' => [ConfigService::$fulfillmentStatusFulfilled]
        ]);

        $this->assertEquals($expectedResults, $results->decodeResponseJson('data'));
    }

    public function test_fulfilled_order()
    {
        $fulfillment     = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment());
        $fulfillment2    = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment([
            'order_id' => $fulfillment['order_id']
        ]));
        $shippingCompany = $this->faker->company;
        $trackingNumber  = $this->faker->randomNumber();
        $results         = $this->call('PATCH', '/fulfillment', [
            'order_id'         => $fulfillment['order_id'],
            'shipping_company' => $shippingCompany,
            'tracking_number'  => $trackingNumber
        ]);

        $this->assertEquals(201, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id'              => $fulfillment['id'],
                'order_id'        => $fulfillment['order_id'],
                'status'          => ConfigService::$fulfillmentStatusFulfilled,
                'company'         => $shippingCompany,
                'tracking_number' => $trackingNumber,
                'fulfilled_on'    => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id'              => $fulfillment2['id'],
                'order_id'        => $fulfillment['order_id'],
                'status'          => ConfigService::$fulfillmentStatusFulfilled,
                'company'         => $shippingCompany,
                'tracking_number' => $trackingNumber,
                'fulfilled_on'    => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_fulfilled_order_item()
    {
        $fulfillment     = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment());
        $fulfillment2    = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment([
            'order_id' => $fulfillment['order_id']
        ]));
        $shippingCompany = $this->faker->company;
        $trackingNumber  = $this->faker->randomNumber();
        $results         = $this->call('PATCH', '/fulfillment', [
            'order_id'         => $fulfillment['order_id'],
            'order_item_id'    => $fulfillment['order_item_id'],
            'shipping_company' => $shippingCompany,
            'tracking_number'  => $trackingNumber
        ]);

        $this->assertEquals(201, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id'              => $fulfillment['id'],
                'order_id'        => $fulfillment['order_id'],
                'status'          => ConfigService::$fulfillmentStatusFulfilled,
                'company'         => $shippingCompany,
                'tracking_number' => $trackingNumber,
                'fulfilled_on'    => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id'              => $fulfillment2['id'],
                'order_id'        => $fulfillment['order_id'],
                'status'          => ConfigService::$fulfillmentStatusPending,
                'company'         => null,
                'tracking_number' => null,
                'fulfilled_on'    => null
            ]
        );
    }

    public function test_delete_order_fulfillments()
    {
        $fulfillment  = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment());
        $fulfillment2 = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment([
            'order_id' => $fulfillment['order_id']
        ]));

        $results = $this->call('DELETE', '/fulfillment', [
            'order_id' => $fulfillment['order_id']
        ]);

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertDatabaseMissing(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id'       => $fulfillment['id'],
                'order_id' => $fulfillment['order_id'],
            ]
        );

        $this->assertDatabaseMissing(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id'       => $fulfillment2['id'],
                'order_id' => $fulfillment['order_id'],
            ]
        );
    }

    public function test_delete_order_item_fulfillment()
    {
        $fulfillment  = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment());
        $fulfillment2 = $this->orderItemFulfillmentRepository->create($this->faker->orderItemFulfillment([
            'order_id' => $fulfillment['order_id']
        ]));

        $results = $this->call('DELETE', '/fulfillment', [
            'order_id'      => $fulfillment['order_id'],
            'order_item_id' => $fulfillment['order_item_id']
        ]);

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertDatabaseMissing(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id'       => $fulfillment['id'],
                'order_id' => $fulfillment['order_id'],
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableOrderItemFulfillment,
            [
                'id'       => $fulfillment2['id'],
                'order_id' => $fulfillment['order_id'],
            ]
        );
    }

    public function test_fulfillment_not_exist()
    {
        $results = $this->call('DELETE', '/fulfillment', [
            'order_id'      => rand()
        ]);

        $this->assertEquals(422, $results->status());
    }
}
