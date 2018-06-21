<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Railroad\Ecommerce\Controllers\OrderJsonController;
use PHPUnit\Framework\TestCase;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\OrderRepository
     */
    protected $orderRepository;

    public function setUp()
    {
        parent::setUp();
        $this->orderRepository = $this->app->make(OrderRepository::class);
    }

    public function test_delete()
    {
        $order = $this->orderRepository->create($this->faker->order());
        $results      = $this->call('DELETE', '/order/' . $order['id']);

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
}
