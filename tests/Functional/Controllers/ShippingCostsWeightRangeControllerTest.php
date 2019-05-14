<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingCostsWeightRangeControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_store_shipping_option_invalid()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $randomShoppingOption = rand();

        $shippingCost = $this->faker->shippingCost([
            'shipping_option_id' => $randomShoppingOption
        ]);

        $results = $this->call(
            'PUT',
            '/shipping-cost/',
            [
                'data' => [
                    'type' => 'shippingCostsWeightRange',
                    'attributes' => $shippingCost
                ],
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'data.relationships.shippingOption.data.id',
                    'detail' => 'The shipping option field is required.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_store_incorrect_max_value()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->fakeShippingOption();

        $minValue = 10;

        $shippingCost = $this->faker->shippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => $minValue,
            'max' => rand(0, 9)
        ]);

        $results = $this->call(
            'PUT',
            '/shipping-cost/',
            [
                'data' => [
                    'type' => 'shippingCostsWeightRange',
                    'attributes' => array_diff_key(
                        $shippingCost,
                        ['shipping_option_id' => true]
                    ),
                    'relationships' => [
                        'shippingOption' => [
                            'data' => [
                                'type' => 'shippingOption',
                                'id' => $shippingOption['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.max',
                    'detail' => 'The max must be greater than or equal ' . $minValue . '.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
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
                    'source' => 'data.relationships.shippingOption.data.id',
                    'detail' => 'The shipping option field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.min',
                    'detail' => 'The min field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.max',
                    'detail' => 'The max field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.price',
                    'detail' => 'The price field is required.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_store()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->fakeShippingOption();

        $minValue = 1;
        $maxValue = rand(10, 19);

        $shippingCost = $this->faker->shippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'min' => $minValue,
            'max' => $maxValue
        ]);

        $results = $this->call(
            'PUT',
            '/shipping-cost/',
            [
                'data' => [
                    'type' => 'shippingCostsWeightRange',
                    'attributes' => array_diff_key(
                        $shippingCost,
                        ['shipping_option_id' => true]
                    ),
                    'relationships' => [
                        'shippingOption' => [
                            'data' => [
                                'type' => 'shippingOption',
                                'id' => $shippingOption['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'type' => 'shippingCostsWeightRange',
                'attributes' => array_diff_key(
                    $shippingCost,
                    [
                        'updated_at' => true,
                        'shipping_option_id' => true
                    ]
                )
            ],
            $results->decodeResponseJson('data')
        );

        $this->assertDatabaseHas(
            'ecommerce_shipping_costs_weight_ranges',
            $shippingCost
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
                'title' => 'Not found.',
                'detail' => 'Update failed, shipping cost weight range not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_update_incorrect_max_value()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->fakeShippingOption();

        $minValue = 10;
        $maxValue = rand(0, 9);

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id']
        ]);

        $results = $this->call(
            'PATCH',
            '/shipping-cost/' . $shippingCost['id'],
            [
                'data' => [
                    'type' => 'shippingCostsWeightRange',
                    'attributes' => [
                        'min' => $minValue,
                        'max' => $maxValue
                    ],
                    'relationships' => [
                        'shippingOption' => [
                            'data' => [
                                'type' => 'shippingOption',
                                'id' => $shippingOption['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.max',
                    'detail' => 'The max must be greater than or equal ' . $minValue . '.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );

        $this->assertDatabaseMissing(
            'ecommerce_shipping_costs_weight_ranges',
            [
                'id' => $shippingCost['id'],
                'min' => $minValue,
                'max' => $maxValue
            ]
        );
    }

    public function test_update_shipping_cost()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->fakeShippingOption();

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'price' => rand(0, 20)
        ]);

        $newPrice = rand(30, 9000);

        $results = $this->call(
            'PATCH',
            '/shipping-cost/' . $shippingCost['id'],
             [
                'data' => [
                    'id' => $shippingCost['id'],
                    'type' => 'shippingCostsWeightRange',
                    'attributes' => [
                        'price' => $newPrice,
                    ]
                ]
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'type' => 'shippingCostsWeightRange',
                'attributes' => array_merge(
                    array_diff_key(
                        $shippingCost,
                        [
                            'id' => true,
                            'updated_at' => true,
                            'shipping_option_id' => true
                        ]
                    ),
                    ['price' => $newPrice]
                )
            ],
            $results->decodeResponseJson('data')
        );

        $this->assertDatabaseHas(
            'ecommerce_shipping_costs_weight_ranges',
            array_merge(
                $shippingCost,
                ['price' => $newPrice]
            )
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
                'title' => 'Not found.',
                'detail' => 'Delete failed, shipping cost weight range not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_delete_shipping_cost()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->fakeShippingOption();

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id']
        ]);

        $results = $this->call(
            'DELETE',
            'shipping-cost/' . $shippingCost['id']
        );

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertDatabaseMissing(
            'ecommerce_shipping_costs_weight_ranges',
            [
                'id' => $shippingCost['id']
            ]
        );
    }
}
