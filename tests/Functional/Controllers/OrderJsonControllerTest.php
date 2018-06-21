<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

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
            , $results->decodeResponseJson()['error']);
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
                'order_id' => $order['id']
            ]));

            $orders[]                   = $order;
        }

        $results = $this->call('GET', '/orders',
            [
                'page'  => $page,
                'limit' => $limit,
            ]);

         $this->assertArraySubset($orders, $results->decodeResponseJson('results'));
    }
}
