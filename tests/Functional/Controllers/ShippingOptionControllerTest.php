<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingOptionControllerTest extends EcommerceTestCase
{
    /**
     * @var ShippingOptionRepository
     */
    protected $shippingOptionRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->shippingOptionRepository = $this->app->make(ShippingOptionRepository::class);
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
                ],
                $shippingOption
            ),
            $results->decodeResponseJson()['results']
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
            $results->decodeResponseJson()['errors']
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
            $results->decodeResponseJson()['errors']
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
            $results->decodeResponseJson()['error']
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
        $this->assertEquals(
            [
                'id' => $shippingOption['id'],
                'country' => $shippingOption['country'],
                'active' => 1,
                'priority' => $shippingOption['priority'],
                'created_on' => $shippingOption['created_on'],
                'updated_on' => null,
            ],
            $results->decodeResponseJson()['results']
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
            $results->decodeResponseJson()['error']
        );
    }

    public function test_delete()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption());
        $results = $this->call('DELETE', '/shipping-option/' . $shippingOption['id']);

        $this->assertEquals(204, $results->getStatusCode());
    }
}
