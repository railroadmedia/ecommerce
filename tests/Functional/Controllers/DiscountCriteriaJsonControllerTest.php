<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class DiscountCriteriaJsonControllerTest extends EcommerceTestCase
{
    public function setUp(): void
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
                ],
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_store_invalid_discount()
    {
        $product = $this->fakeProduct();

        $discountCriteria = $this->faker->discountCriteria([
            'products_relation_type' => $this->faker->randomElement(
                [
                    DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY,
                    DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
                ]
            )
        ]);

        $randomId = rand();

        $results = $this->call(
            'PUT',
            '/discount-criteria/' . $randomId,
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => $discountCriteria,
                    'relationships' => [
                        'products' => [
                            'data' => [
                                [
                                    'type' => 'product',
                                    'id' => $product['id'],
                                ]
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
                [
                    'title' => 'Not found.',
                    'detail' => 'Create discount criteria failed, discount not found with id: ' . $randomId,
                ]
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
            'products_relation_type' => $this->faker->randomElement(
                [
                    DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY,
                    DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL,
                ]
            )
        ]);

        $results = $this->call(
            'PUT',
            '/discount-criteria/' . $discount['id'],
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => $discountCriteria,
                    'relationships' => [
                        'products' => [
                            'data' => [
                                [
                                    'type' => 'product',
                                    'id' => $productDiscountCriteria['id']
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(201, $results->getStatusCode());

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
                        'products' => [
                            'data' => [
                                [
                                    'type' => 'product',
                                    'id' => $productDiscountCriteria['id']
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert that the discount criteria exists in the database
        $this->assertDatabaseHas(
            'ecommerce_discount_criteria',
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
                [
                    'title' => 'Not found.',
                    'detail' => 'Update discount criteria failed, discount criteria not found with id: ' . $randomId,
                ]
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_update_replace_associated_product()
    {
        $productDiscount = $this->fakeProduct();
        $initialProductDiscountCriteria = $this->fakeProduct();
        $productDiscountCriteria = $this->fakeProduct();

        $discount = $this->fakeDiscount([
            'product_id' => $productDiscount['id']
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id']
        ]);

        // in the initial state the discount criteria is associated with this product
        $initialDiscountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'product_id' => $initialProductDiscountCriteria['id'],
            'discount_criteria_id' => $discountCriteria['id'],
        ]);

        $newDiscountCriteria = $this->faker->discountCriteria([
            'discount_id' => $discount['id'],
        ]);

        // the discount criteria update action will remove initial association to $initialProductDiscountCriteria
        // and will add new association to $productDiscountCriteria
        $results = $this->call(
            'PATCH',
            '/discount-criteria/' . $discountCriteria['id'],
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => $newDiscountCriteria,
                    'relationships' => [
                        'products' => [
                            'data' => [
                                [
                                    'type' => 'product',
                                    'id' => $productDiscountCriteria['id']
                                ],
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
                        'products' => [
                            'data' => [
                                [
                                    'type' => 'product',
                                    'id' => $productDiscountCriteria['id']
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert that the discount criteria data has been updated in the database
        $this->assertDatabaseHas(
            'ecommerce_discount_criteria',
            array_merge(
                $newDiscountCriteria,
                [
                    'id' => $discountCriteria['id'],
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]
            )
        );

        // assert that the discount criteria has been associated with new $productDiscountCriteria
        $this->assertDatabaseHas(
            'ecommerce_discount_criterias_products',
            [
                'discount_criteria_id' => $discountCriteria['id'],
                'product_id' => $productDiscountCriteria['id'],
            ]
        );

        // assert that the discount criteria is not associated with $initialProductDiscountCriteria
        $this->assertDatabaseMissing(
            'ecommerce_discount_criterias_products',
            [
                'discount_criteria_id' => $discountCriteria['id'],
                'product_id' => $initialProductDiscountCriteria['id'],
            ]
        );
    }

    public function test_update_add_associated_product()
    {
        $productDiscount = $this->fakeProduct();
        $productDiscountCriteriaInitial = $this->fakeProduct();
        $productDiscountCriteriaNew = $this->fakeProduct();

        $discount = $this->fakeDiscount([
            'product_id' => $productDiscount['id']
        ]);

        $discountCriteria = $this->fakeDiscountCriteria([
            'discount_id' => $discount['id']
        ]);

        $initialDiscountCriteriaProduct = $this->fakeDiscountCriteriaProduct([
            'product_id' => $productDiscountCriteriaInitial['id'],
            'discount_criteria_id' => $discountCriteria['id'],
        ]);

        $newDiscountCriteria = $this->faker->discountCriteria([
            'discount_id' => $discount['id'],
        ]);

        $results = $this->call(
            'PATCH',
            '/discount-criteria/' . $discountCriteria['id'],
            [
                'data' => [
                    'type' => 'discountCriteria',
                    'attributes' => $newDiscountCriteria,
                    'relationships' => [
                        'products' => [
                            'data' => [
                                [
                                    'type' => 'product',
                                    'id' => $productDiscountCriteriaInitial['id']
                                ],
                                [
                                    'type' => 'product',
                                    'id' => $productDiscountCriteriaNew['id']
                                ]
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
                        'products' => [
                            'data' => [
                                [
                                    'type' => 'product',
                                    'id' => $productDiscountCriteriaInitial['id']
                                ],
                                [
                                    'type' => 'product',
                                    'id' => $productDiscountCriteriaNew['id']
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert that the discount criteria data has been updated in the database
        $this->assertDatabaseHas(
            'ecommerce_discount_criteria',
            array_merge(
                $newDiscountCriteria,
                [
                    'id' => $discountCriteria['id'],
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]
            )
        );

        // assert that the discount criteria has been associated with new $productDiscountCriteriaNew
        $this->assertDatabaseHas(
            'ecommerce_discount_criterias_products',
            [
                'discount_criteria_id' => $discountCriteria['id'],
                'product_id' => $productDiscountCriteriaNew['id'],
            ]
        );

        // assert that the discount criteria is associated with $productDiscountCriteriaInitial
        $this->assertDatabaseHas(
            'ecommerce_discount_criterias_products',
            [
                'discount_criteria_id' => $discountCriteria['id'],
                'product_id' => $productDiscountCriteriaInitial['id'],
            ]
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
                [
                    'title' => 'Not found.',
                    'detail' => 'Delete discount criteria failed, discount criteria not found with id: ' . $randomId,
                ]
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
            'discount_id' => $discount['id']
        ]);

        $results = $this->call('DELETE', '/discount-criteria/' . $discountCriteria['id']);

        // assert the response status code
        $this->assertEquals(204, $results->getStatusCode());

        // assert that the discount criteria not exists in the database
        $this->assertDatabaseMissing(
            'ecommerce_discount_criteria',
            ['id' => $discountCriteria['id']]
        );
    }
}
