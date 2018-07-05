<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingOptionControllerTest extends EcommerceTestCase
{
    /**
     * @var ShippingOptionRepository
     */
    protected $shippingOptionRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ShippingCostsRepository
     */
    protected $shippingCostsRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->shippingOptionRepository = $this->app->make(ShippingOptionRepository::class);
        $this->shippingCostsRepository = $this->app->make(ShippingCostsRepository::class);
    }

    public function test_store()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = [
            'country' => $this->faker->country,
            'priority' => 1,
            'active' => 1,
        ];

        $results = $this->call('PUT', '/shipping-option/', $shippingOption);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals(
            array_merge(
                [
                    'id' => 1,
                    'created_on' => Carbon::now()->toDateTimeString(),
                    'updated_on' => null,
                    'weightRanges' => []
                ],
                $shippingOption
            ),
            $results->decodeResponseJson()['data'][0]
        );
    }

    public function test_store_validation_errors()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $results = $this->call('PUT', '/shipping-option/');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    "source" => "country",
                    "detail" => "The country field is required.",
                ],
                [
                    "source" => "priority",
                    "detail" => "The priority field is required.",
                ],
                [
                    "source" => "active",
                    "detail" => "The active field is required.",
                ],
            ],
            $results->decodeResponseJson('meta')['errors']
        );
    }

    public function test_update_negative_priority()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption());

        $results = $this->call(
            'PATCH',
            '/shipping-option/' . $shippingOption['id'],
            [
                'priority' => -1,
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(
            [
                [
                    "source" => "priority",
                    "detail" => "The priority must be at least 0.",
                ],
            ],
            $results->decodeResponseJson('meta')['errors']
        );
    }

    public function test_update_not_existing_shipping_option()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $randomId = rand();
        $results = $this->call('PATCH', '/shipping-option/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());

        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Update failed, shipping option not found with id: " . $randomId,
            ]
            ,
            $results->decodeResponseJson('meta')['errors']
        );
    }

    public function test_update()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption());

        $results = $this->call(
            'PATCH',
            '/shipping-option/' . $shippingOption['id'],
            [
                'active' => 1,
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset(
            [
                'id' => $shippingOption['id'],
                'country' => $shippingOption['country'],
                'active' => 1,
                'priority' => $shippingOption['priority'],
                'created_on' => $shippingOption['created_on'],
                'weightRanges' => [],
                'updated_on' => Carbon::now()->toDateTimeString(),
            ],
            $results->decodeResponseJson('data')[0]
        );
    }

    public function test_delete_not_existing_shipping_option()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $randomId = rand();
        $results = $this->call('DELETE', 'shipping-option/' . $randomId);
        $this->assertEquals(404, $results->getStatusCode());

        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Delete failed, shipping option not found with id: " . $randomId,
            ]
            ,
            $results->decodeResponseJson('meta')['errors']
        );
    }

    public function test_delete()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption());
        $results = $this->call('DELETE', '/shipping-option/' . $shippingOption['id']);

        $this->assertEquals(204, $results->getStatusCode());
    }

    public function test_pull_shipping_options()
    {
        for($i =0; $i<3; $i++){
            $shippingOption[$i] = (array)$this->shippingOptionRepository->create($this->faker->shippingOption());
            $shippingOption[$i]['weightRanges'][] = (array)$this->shippingCostsRepository->create($this->faker->shippingCost([
                'shipping_option_id' => $shippingOption[$i]['id']
            ]));
        }

        $results = $this->call('GET','/shipping-options',
            [
                'order_by_direction' => 'asc'
            ]);
        $this->assertEquals($shippingOption, $results->decodeResponseJson('data'));
    }

    public function test_pull_shipping_options_empty()
    {
        $results = $this->call('GET','/shipping-options',
            [
                'order_by_direction' => 'asc'
            ]);
        $this->assertEmpty($results->decodeResponseJson('data'));
        $this->assertEquals(0, $results->decodeResponseJson('meta')['totalResults']);
    }
}
