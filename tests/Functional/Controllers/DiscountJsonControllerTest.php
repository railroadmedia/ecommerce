<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class DiscountJsonControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_store_validation()
    {
        $results = $this->call('PUT', '/discount', []);

        // assert the response status code
        $this->assertEquals(422, $results->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals([
            [
                'source' => 'data.attributes.name',
                'detail' => 'The name field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.description',
                'detail' => 'The description field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.type',
                'detail' => 'The type field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.amount',
                'detail' => 'The amount field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.active',
                'detail' => 'The active field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.visible',
                'detail' => 'The visible field is required.',
                'title' => 'Validation failed.'
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store()
    {
        $product = $this->fakeProduct();
        $discount = $this->faker->discount([
            'product_id' => $product['id']
        ]);

        $results  = $this->call(
            'PUT',
            '/discount',
            [
                'data' => [
                    'type' => 'discount',
                    'attributes' => $discount,
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(201, $results->getStatusCode());

        // assert that the new created discount it's returned in response in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'discount',
                    'attributes' => array_diff_key(
                        $discount,
                        ['product_id' => true]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $product['id'],
                        // full products relation is loaded upon request
                        // 'attributes' => array_diff_key(
                        //     $product,
                        //     ['id' => true]
                        // )
                    ]
                ]
            ],
            $results->decodeResponseJson()
        );

        // assert that the discount exists in the database
        $this->assertDatabaseHas(ConfigService::$tableDiscount, $discount);
    }

    public function test_update_missing_discount()
    {
        // take a fake discount id
        $randomId = rand();
        $results  = $this->call('PATCH', '/discount/' . $randomId);

        // assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                'title'  => 'Not found.',
                'detail' => 'Update failed, discount not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_update()
    {
        $product = $this->fakeProduct();

        $discount = $this->fakeDiscount([
            'product_id' => $product['id'],
            'product_category' => null,
            'updated_at' => null
        ]);

        $newName = $this->faker->word;

        $results = $this->call(
            'PATCH',
            '/discount/' . $discount['id'],
            [
                'data' => [
                    'id' => $discount['id'],
                    'type' => 'discount',
                    'attributes' => [
                        'name' => $newName
                    ],
                ],
            ]
        );

        // assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        // assert the discount it's returned in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'discount',
                    'attributes' => array_merge(
                        array_diff_key(
                            $discount,
                            [
                                'id' => true,
                                'product_id' => true,
                                'name' => true,
                            ]
                        ),
                        [
                            'name' => $newName,
                            'updated_at' => Carbon::now()->toDateTimeString()
                        ]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'product',
                        'id' => $product['id'],
                        // full products relation is loaded upon request
                        // 'attributes' => array_merge(
                        //     array_diff_key(
                        //         $product,
                        //         [
                        //             'id' => true,
                        //             'active' => true
                        //         ]
                        //     ),
                        //     [
                        //         'active' => (bool) $product['active']
                        //     ]
                        // )
                    ]
                ]
            ],
            $results->decodeResponseJson()
        );

        // assert database updates
        $this->assertDatabaseHas(
            ConfigService::$tableDiscount,
            array_merge(
                $discount,
                [
                    'name' => $newName,
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]
            )
        );
    }

    public function test_delete()
    {
        $discount = $this->fakeDiscount();

        $results = $this->call('DELETE', '/discount/' . $discount['id']);

        // assert response status code
        $this->assertEquals(204, $results->getStatusCode());

        // assert that the discount not exists anymore in the database
        $this->assertDatabaseMissing(ConfigService::$tableDiscount, $discount);
    }

    public function test_delete_not_found()
    {
        $randomId = rand();

        $results = $this->call('DELETE', '/discount/' . $randomId);

        // assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert validation error
        $this->assertEquals(
            [
                'detail' => 'Delete failed, discount not found with id: ' . $randomId,
                'title' => 'Not found.'
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_pull_discounts()
    {
        $page = 1;
        $limit = 10;
        $totalNumberOfDiscounts = $this->faker->numberBetween(15, 25);
        $discounts = [];
        $products = [];

        for ($i = 0; $i < $totalNumberOfDiscounts; $i++) {

            $product = $this->fakeProduct([
                'updated_at' => null
            ]);

            $discount = $this->fakeDiscount([
                'product_id' => $product['id'],
                'product_category' => null,
                'updated_at' => null
            ]);

            if ($i < $limit) {
                $discounts[] = [
                    'type' => 'discount',
                    'id' => $discount['id'],
                    'attributes' => array_diff_key(
                        $discount,
                        [
                            'id' => true,
                            'product_id' => true
                        ]
                    ),
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $product['id']
                            ]
                        ]
                    ]
                ];

                $products[] = [
                    'type' => 'product',
                    'id' => $product['id'],
                    'attributes' => array_merge(
                        array_diff_key(
                            $product,
                            ['id' => true]
                        ),
                        [
                            'active' => (bool) $product['active'],
                            'is_physical' => (bool) $product['is_physical']
                        ]
                    )
                ];
            }
        }

        $results = $this->call(
            'GET',
            '/discounts',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_direction' => 'asc'
            ]
        );

        // assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        $parsedResults = $results->decodeResponseJson();

        $this->assertEquals($discounts, $parsedResults['data']);
        $this->assertEquals($products, $parsedResults['included']);
    }

    public function test_pull_discount()
    {
        $product = $this->fakeProduct([
            'updated_at' => null
        ]);

        $discount = $this->fakeDiscount([
            'product_id' => $product['id'],
            'product_category' => null,
            'updated_at' => null
        ]);

        $results = $this->call('GET', '/discount/' . $discount['id']);

        // assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        $parsedResults = $results->decodeResponseJson();

        $this->assertEquals(
            [
                'type' => 'discount',
                'id' => $discount['id'],
                'attributes' => array_diff_key(
                    $discount,
                    [
                        'id' => true,
                        'product_id' => true
                    ]
                ),
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'product',
                            'id' => $product['id']
                        ]
                    ]
                ]
            ],
            $parsedResults['data']
        );

        $this->assertEquals(
            [[
                'type' => 'product',
                'id' => $product['id'],
                'attributes' => array_diff_key(
                    $product,
                    [
                        'id' => true
                    ]
                )
            ]],
            $parsedResults['included']
        );
    }

    public function test_pull_discount_not_found()
    {
        $randomDiscountId = rand();

        $results = $this->call('GET', '/discount/' . $randomDiscountId);

        // assert response status
        $this->assertEquals(404, $results->status());

        // assert error message
        $this->assertEquals(
            [
                'title'  => 'Not found.',
                'detail' => 'Pull failed, discount not found with id: ' . $randomDiscountId
            ],
            $results->decodeResponseJson()['errors']
        );
    }
}
