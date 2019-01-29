<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class DiscountCriteriaJsonControllerTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_store_validation()
    {
        $results = $this->call('PUT', '/discount-criteria/' . rand());

        // assert the response status code
        $this->assertEquals(422, $results->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.name',
                    'detail' => 'The name field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'data.attributes.type',
                    'detail' => 'The type field is required.',
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
                ]
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_store_invalid_discount()
    {
        $product = $this->fakeProduct();

        $discountCriteria = $this->faker->discountCriteria([
            'product_id' => $product['id']
        ]);

        $randomId = rand();

        $results = $this->call(
            'PUT',
            '/discount-criteria/' . $randomId,
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => $discountCriteria
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals(
            [
                'title'  => 'Not found.',
                'detail' => 'Create discount criteria failed, discount not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_store()
    {
        $productDiscount = $this->fakeProduct();
        $productDiscountCriteria = $this->fakeProduct();

        $discount = $this->fakeDiscount([
            'product_id' => $productDiscount['id']
        ]);

        $discountCriteria = $this->faker->discountCriteria([
            'product_id' => $productDiscountCriteria['id']
        ]);

        $results = $this->call(
            'PUT',
            '/discount-criteria/' . $discount['id'],
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => $discountCriteria,
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $productDiscountCriteria['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(200, $results->getStatusCode());

        // assert that the new created discount criteria it's returned in response in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => array_diff_key(
                        $discountCriteria,
                        [
                            'product_id' => true,
                            'discount_id' => true,
                        ]
                    ),
                    'relationships' => [
                        'discount' => [
                            'data' => [
                                'type' => 'discount',
                                'id' => $discount['id']
                            ]
                        ],
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $productDiscountCriteria['id']
                            ]
                        ]
                    ]
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert that the discount criteria exists in the database
        $this->assertDatabaseHas(
            ConfigService::$tableDiscountCriteria,
            array_merge(
                $discountCriteria,
                [
                    'discount_id' => $discount['id'],
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]
            )
        );
    }

    public function test_update_inexistent_discount_criteria()
    {
        $randomId = rand();

        $discountCriteria = $this->faker->discountCriteria();

        $results = $this->call(
            'PATCH',
            '/discount-criteria/' . $randomId,
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => $discountCriteria,
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => rand()
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals(
            [
                'title'  => 'Not found.',
                'detail' => 'Update discount criteria failed, discount criteria not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_update()
    {
        $productDiscount = $this->fakeProduct();
        $productDiscountCriteria = $this->fakeProduct();

        $discount = $this->fakeDiscount([
            'product_id' => $productDiscount['id']
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'product_id' => $productDiscountCriteria['id'],
            'discount_id' => $discount['id']
        ]);

        $newDiscountCriteria = $this->faker->discountCriteria([
            'product_id' => $productDiscountCriteria['id'],
            'discount_id' => $discount['id']
        ]);

        $results = $this->call(
            'PATCH',
            '/discount-criteria/' . $discountCriteria['id'],
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => $newDiscountCriteria,
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $productDiscountCriteria['id']
                            ]
                        ],
                        'discount' => [
                            'data' => [
                                'type' => 'discount',
                                'id' => $discount['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(200, $results->getStatusCode());

        // assert the new discount criteria data it's returned in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => array_diff_key(
                        $newDiscountCriteria,
                        [
                            'product_id' => true,
                            'discount_id' => true,
                        ]
                    ),
                    'relationships' => [
                        'discount' => [
                            'data' => [
                                'type' => 'discount',
                                'id' => $discount['id']
                            ]
                        ],
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $productDiscountCriteria['id']
                            ]
                        ]
                    ]
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert that the discount criteria data has been updated in the database
        $this->assertDatabaseHas(
            ConfigService::$tableDiscountCriteria,
            array_merge(
                $newDiscountCriteria,
                [
                    'id' => $discountCriteria['id'],
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]
            )
        );
    }

    public function test_delete_inexistent_discount_criteria()
    {
        $randomId = $this->faker->numberBetween();

        $results = $this->call('DELETE', '/discount-criteria/' . $randomId);

        // assert the response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals(
            [
                'title'  => 'Not found.',
                'detail' => 'Delete discount criteria failed, discount criteria not found with id: ' . $randomId,
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_delete()
    {
        $productDiscount = $this->fakeProduct();
        $productDiscountCriteria = $this->fakeProduct();

        $discount = $this->fakeDiscount([
            'product_id' => $productDiscount['id']
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'product_id' => $productDiscountCriteria['id'],
            'discount_id' => $discount['id']
        ]);

        $results = $this->call('DELETE', '/discount-criteria/' . $discountCriteria['id']);

        // assert the response status code
        $this->assertEquals(204, $results->getStatusCode());

        // assert that the discount criteria not exists in the database
        $this->assertDatabaseMissing(
            ConfigService::$tableDiscountCriteria,
            ['id' => $discountCriteria['id']]
        );
    }
}
