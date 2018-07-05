<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Controllers\OrderJsonController;
use PHPUnit\Framework\TestCase;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    protected $productRepository;

    public function setUp()
    {
        parent::setUp();
        $this->orderRepository     = $this->app->make(OrderRepository::class);
        $this->orderItemRepository = $this->app->make(OrderItemRepository::class);
        $this->productRepository   = $this->app->make(ProductRepository::class);
    }

    public function test_delete()
    {
        $order   = $this->orderRepository->create($this->faker->order());
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

        //assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        //assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                "title"  => "Not found.",
                "detail" => "Delete failed, order not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson('meta')['errors']);
    }

    public function test_pull_orders()
    {
        $page     = 1;
        $limit    = 10;
        $nrOrders = 10;
        $product  = $this->productRepository->create($this->faker->product([
            'type' => ConfigService::$typeSubscription
        ]));

        for($i = 0; $i < $nrOrders; $i++)
        {
            $order     = $this->orderRepository->create($this->faker->order());
            $orderItem = $this->orderItemRepository->create($this->faker->orderItem([
                'product_id' => $product['id'],
                'order_id'   => $order['id']
            ]));

            $orders[] = $order->getArrayCopy();
        }

        $results = $this->call('GET', '/orders',
            [
                'page'  => $page,
                'limit' => $limit,
            ]);

        $this->assertArraySubset($orders, $results->decodeResponseJson('data'));
    }

    public function test_update_not_existing_order()
    {
        $results = $this->call('PATCH', '/order/' . rand());

        $this->assertEquals(404, $results->getStatusCode());
    }

    public function test_update_order()
    {
        $order   = $this->orderRepository->create($this->faker->order());
        $newDue  = $this->faker->numberBetween();
        $results = $this->call('PATCH', '/order/' . $order['id'],
            [
                'due' => $newDue
            ]);

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals(
            array_merge($order->getArrayCopy(), [
                    'updated_on' => Carbon::now()->toDateTimeString(),
                    'due'        => $newDue
                ]
            ), $results->decodeResponseJson('data')[0]);
        $this->assertDatabaseHas(
            ConfigService::$tableOrder,
            array_merge($order->getArrayCopy(), [
                    'updated_on' => Carbon::now()->toDateTimeString(),
                    'due'        => $newDue
                ]
            )
        );
    }

    public function test_update_order_validation()
    {
        $order = $this->orderRepository->create($this->faker->order());
        $results = $this->call('PATCH', '/order/'.$order['id'],
            [
                'due' => -110
            ]);
        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(1, count($results->decodeResponseJson('meta')['errors']));
    }

    public function test_pull_orders_between_start_date_and_end_date()
    {
        $page     = 1;
        $limit    = 10;
        $nrOrders = 7;
        $product  = $this->productRepository->create($this->faker->product([
            'type' => ConfigService::$typeSubscription
        ]));

        $order     = $this->orderRepository->create($this->faker->order([
            'created_on' => Carbon::now()->subMonth(1)->toDateTimeString()
        ]));
        $orderItem = $this->orderItemRepository->create($this->faker->orderItem([
            'product_id' => $product['id'],
            'order_id'   => $order['id']
        ]));
        $oldOrder     = $this->orderRepository->create($this->faker->order([
            'created_on' => Carbon::now()->subDay(1)->toDateTimeString()
        ]));
        $orderItem = $this->orderItemRepository->create($this->faker->orderItem([
            'product_id' => $product['id'],
            'order_id'   => $oldOrder['id']
        ]));

        for($i = 0; $i < $nrOrders; $i++)
        {
            $order     = $this->orderRepository->create($this->faker->order());
            $orderItem = $this->orderItemRepository->create($this->faker->orderItem([
                'product_id' => $product['id'],
                'order_id'   => $order['id']
            ]));

            $orders[] = $order;
        }
        $orders[] = $oldOrder;
        $results = $this->call('GET', '/orders',
            [
                'page'  => $page,
                'limit' => $limit,
                'start-date' => Carbon::now()->subDay(2)->toDateTimeString(),
                'end-date' => Carbon::now()->toDateTimeString()
            ]);

        $this->assertArraySubset($orders, $results->decodeResponseJson('results'));
    }
}
