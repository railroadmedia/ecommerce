<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\ShippingCostsRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingCostsWeightRangeControllerTest extends EcommerceTestCase
{
    /**
     * @var ShippingOptionRepository
     */
    protected $shippingOptionRepository;

    /**
     * @var ShippingCostsRepository
     */
    protected $shippingCostRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->shippingOptionRepository = $this->app->make(ShippingOptionRepository::class);
        $this->shippingCostRepository = $this->app->make(ShippingCostsRepository::class);
    }

    public function test_store_shipping_option_invalid()
    {
        $this->permissionServiceMock->method('canOrThrow');
        $randomShoppingOption = rand();

        $results = $this->call(
            'PUT',
            '/shipping-cost/',
            [
                'shipping_option_id' => $randomShoppingOption,
                'min' => 0,
                'max' => 1,
                'price' => rand(),
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    "source" => "shipping_option_id",
                    "detail" => "The selected shipping option id is invalid.",
                ],
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_store_incorrect_max_value()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption());
        $minValue = 10;

        $results = $this->call(
            'PUT',
            '/shipping-cost/',
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => $minValue,
                'max' => rand(0, 9),
                'price' => rand(),
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    "source" => "max",
                    "detail" => "The max must be at least " . $minValue . ".",
                ],
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_store_missing_required_fields()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $results = $this->call('PUT', '/shipping-cost/');

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(
            [
                [
                    "source" => "shipping_option_id",
                    "detail" => "The shipping option id field is required.",
                ],
                [
                    "source" => "min",
                    "detail" => "The min field is required.",
                ],
                [
                    "source" => "max",
                    "detail" => "The max field is required.",
                ],
                [
                    "source" => "price",
                    "detail" => "The price field is required.",
                ],
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_store()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption());
        $minValue = 1;
        $maxValue = rand(10, 19);
        $price = rand(1,100);

        $results = $this->call(
            'PUT',
            '/shipping-cost/',
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => $minValue,
                'max' => $maxValue,
                'price' => $price,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertEquals(
            [
                'id' => 1,
                'shipping_option_id' => $shippingOption['id'],
                'min' => $minValue,
                'max' => $maxValue,
                'price' => $price,
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null,
            ],
            $results->decodeResponseJson()['results']
        );

    }

    public function test_update_incorrect_shipping_cost_id()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $randomId = rand();

        $results = $this->call('PATCH', '/shipping-cost/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Update failed, shipping cost weight range not found with id: " . $randomId,
            ]
            ,
            $results->decodeResponseJson()['error']
        );
    }

    public function test_update_incorrect_max_value()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption());
        $shippingCost =
            $this->shippingCostRepository->create(
                $this->faker->shippingCost(['shipping_option_id' => $shippingOption['id']])
            );
        $minValue = 10;

        $results = $this->call(
            'PATCH',
            '/shipping-cost/' . $shippingCost['id'],
            [
                'min' => $minValue,
                'max' => rand(0, 9),
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(
            [
                [
                    "source" => "max",
                    "detail" => "The max must be at least " . $minValue . ".",
                ],
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_update_shipping_cost()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption());
        $shippingCost =
            $this->shippingCostRepository->create(
                $this->faker->shippingCost(['shipping_option_id' => $shippingOption['id']])
            );

        $newPrice = rand(0, 9000);

        $results = $this->call(
            'PATCH',
            '/shipping-cost/' . $shippingCost['id'],
            [
                'price' => $newPrice,
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());

        $this->assertEquals(
            [
                'id' => $shippingCost['id'],
                'shipping_option_id' => $shippingOption['id'],
                'min' => $shippingCost['min'],
                'max' => $shippingCost['max'],
                'price' => $newPrice,
                'created_on' => $shippingCost['created_on'],
                'updated_on' => Carbon::now()->toDateTimeString(),
            ],
            $results->decodeResponseJson()['results']
        );
    }

    public function test_delete_incorrect_shipping_id()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $randomId = rand();
        $results = $this->call('DELETE', '/shipping-cost/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Delete failed, shipping cost weight range not found with id: " . $randomId,
            ],
            $results->decodeResponseJson()['error']
        );
    }

    public function test_delete_shipping_cost()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->shippingOptionRepository->create($this->faker->shippingOption());
        $shippingCost =
            $this->shippingCostRepository->create(
                $this->faker->shippingCost(['shipping_option_id' => $shippingOption['id']])
            );

        $results = $this->call('DELETE', 'shipping-cost/' . $shippingCost['id']);

        $this->assertEquals(204, $results->getStatusCode());
    }
}
