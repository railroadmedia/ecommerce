<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingOptionControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_store()
    {
        $shippingOption = $this->faker->shippingOption([
            'updated_at' => null
        ]);

        $results = $this->call(
            'PUT',
            '/shipping-option/',
            [
                'data' => [
                    'type' => 'shippingOption',
                    'attributes' => $shippingOption
                ],
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'type' => 'shippingOption',
                'attributes' => array_diff_key(
                    $shippingOption,
                    ['updated_at' => true]
                ),
                'relationships' => [
                    'shippingCostsWeightRange' => [
                        'data' => []
                    ]
                ]
            ],
            $results->decodeResponseJson('data')
        );

        $this->assertDatabaseHas(
            'ecommerce_shipping_options',
            array_diff_key(
                $shippingOption,
                ['updated_at' => true]
            )
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
                    'source' => 'data.attributes.country',
                    'detail' => 'The country field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.priority',
                    'detail' => 'The priority field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.active',
                    'detail' => 'The active field is required.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_update_negative_priority()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->fakeShippingOption();

        $results = $this->call(
            'PATCH',
            '/shipping-option/' . $shippingOption['id'],
            [
                'data' => [
                    'type' => 'shippingOption',
                    'attributes' => [
                        'priority' => -1,
                    ]
                ],
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.priority',
                    'detail' => 'The priority must be at least 0.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
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
                'title' => 'Not found.',
                'detail' => 'Update failed, shipping option not found with id: ' . $randomId,
            ]
            ,
            $results->decodeResponseJson('errors')
        );
    }

    public function test_update()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->fakeShippingOption([
            'updated_at' => null
        ]);

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'updated_at' => null
        ]);

        $updates = [
            'active' => !$shippingOption['active'],
            'priority' => $shippingOption['priority'] * 3
        ];

        $results = $this->call(
            'PATCH',
            '/shipping-option/' . $shippingOption['id'],
            [
                'data' => [
                    'type' => 'shippingOption',
                    'attributes' => $updates
                ],
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertEquals(
            [
                'type' => 'shippingOption',
                'id' => $shippingOption['id'],
                'attributes' => array_merge(
                    array_diff_key(
                        $shippingOption,
                        ['id' => true]
                    ),
                    $updates,
                    ['updated_at' => Carbon::now()->toDateTimeString()]
                ),
                'relationships' => [
                    'shippingCostsWeightRange' => [
                        'data' => [
                            [
                                'type' => 'shippingCostsWeightRange',
                                'id' => $shippingCost['id']
                            ]
                        ]
                    ]
                ]
            ],
            $results->decodeResponseJson('data')
        );

        $this->assertDatabaseHas(
            'ecommerce_shipping_options',
            array_merge(
                $shippingOption,
                $updates,
                ['updated_at' => Carbon::now()->toDateTimeString()]
            )
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
                'title' => 'Not found.',
                'detail' => 'Delete failed, shipping option not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_delete()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $shippingOption = $this->fakeShippingOption([
            'updated_at' => null
        ]);

        $shippingCost = $this->fakeShippingCost([
            'shipping_option_id' => $shippingOption['id'],
            'updated_at' => null
        ]);

        $results = $this->call(
            'DELETE',
            '/shipping-option/' . $shippingOption['id']
        );

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertDatabaseMissing(
            'ecommerce_shipping_options',
            $shippingOption
        );
    }

    public function test_pull_shipping_options()
    {
        $page = 1;
        $limit = 10;
        $totalNumberOfShippingOptions = $this->faker->numberBetween(15, 25);
        $shippingOptions = [];
        $included = [];

        for ($i = 0; $i < $totalNumberOfShippingOptions; $i++) {

            $shippingOption = $this->fakeShippingOption([
                'updated_at' => null
            ]);

            $shippingCostOne = $this->fakeShippingCost([
                'shipping_option_id' => $shippingOption['id'],
                'updated_at' => null
            ]);

            $shippingCostTwo = $this->fakeShippingCost([
                'shipping_option_id' => $shippingOption['id'],
                'updated_at' => null
            ]);

            if ($i < $limit) {
                $shippingOptions[] = [
                    'type' => 'shippingOption',
                    'id' => $shippingOption['id'],
                    'attributes' => array_diff_key(
                        $shippingOption,
                        ['id' => true]
                    ),
                    'relationships' => [
                        'shippingCostsWeightRange' => [
                            'data' => [
                                [
                                    'type' => 'shippingCostsWeightRange',
                                    'id' => $shippingCostOne['id']
                                ],
                                [
                                    'type' => 'shippingCostsWeightRange',
                                    'id' => $shippingCostTwo['id']
                                ]
                            ]
                        ]
                    ]
                ];

                $included[] = [
                    'type' => 'shippingCostsWeightRange',
                    'id' => $shippingCostOne['id'],
                    'attributes' => array_diff_key(
                        $shippingCostOne,
                        [
                            'id' => true,
                            'shipping_option_id' => true
                        ]
                    ),
                ];

                $included[] = [
                    'type' => 'shippingCostsWeightRange',
                    'id' => $shippingCostTwo['id'],
                    'attributes' => array_diff_key(
                        $shippingCostTwo,
                        [
                            'id' => true,
                            'shipping_option_id' => true
                        ]
                    ),
                ];
            }
        }

        $results = $this->call(
            'GET',
            '/shipping-options',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => 'id',
                'order_by_direction' => 'asc'
            ]
        );

        // assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        $resultsDecoded = $results->decodeResponseJson();

        $this->assertEquals(
            $shippingOptions,
            $resultsDecoded['data']
        );

        $this->assertEquals(
            $included,
            $resultsDecoded['included']
        );
    }
}
