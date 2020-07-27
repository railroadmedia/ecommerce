<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Permissions\Exceptions\NotAllowedException;

class UserProductJsonControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_pull()
    {
        $userId = $this->createAndLogInNewUser();

        $productOne = $this->fakeProduct();

        $userProductOne = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
            ]
        );

        // add soft deleted, not returned in response
        $productTwo = $this->fakeProduct();

        $userProductTwo = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'deleted_at' => Carbon::now(),
            ]
        );

        $response = $this->call(
            'GET',
            '/user-product',
            []
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'type' => 'userProduct',
                        'id' => $userProductOne['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $userProductOne,
                                [
                                    'id' => true,
                                    'user_id' => true,
                                    'product_id' => true,
                                ]
                            )
                        ),
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

    }

    public function test_pull_soft_deleted_included()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $productOne = $this->fakeProduct();

        $userProductOne = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $productOne['id'],
            ]
        );

        // add soft deleted returned in response
        $productTwo = $this->fakeProduct();

        $userProductTwo = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $productTwo['id'],
                'deleted_at' => Carbon::now(),
            ]
        );

        $response = $this->call(
            'GET',
            '/user-product',
            [
                'view_deleted' => true,
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'type' => 'userProduct',
                        'id' => $userProductOne['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $userProductOne,
                                [
                                    'id' => true,
                                    'user_id' => true,
                                    'product_id' => true,
                                ]
                            )
                        ),
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'userProduct',
                        'id' => $userProductTwo['id'],
                        'attributes' => array_merge(
                            array_diff_key(
                                $userProductTwo,
                                [
                                    'id' => true,
                                    'user_id' => true,
                                    'product_id' => true,
                                ]
                            )
                        ),
                        'relationships' => [
                            'user' => [
                                'data' => [
                                    'type' => 'user',
                                    'id' => $userId
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

    }

    public function test_store_validation_fails()
    {
        $results = $this->call('PUT', '/user-product', []);

        //assert the response status code
        $this->assertEquals(422, $results->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals(
            [
                [
                    'source' => 'data.attributes.quantity',
                    'detail' => 'The quantity field is required.',
                    'title' => 'Validation failed.',
                ],
                [
                    'source' => 'data.relationships.user.data.id',
                    'detail' => 'The user id field is required.',
                    'title' => 'Validation failed.',
                ],
                [
                    'source' => 'data.relationships.product.data.id',
                    'detail' => 'The product field is required.',
                    'title' => 'Validation failed.',
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_store_permissions_fails()
    {
        $this->permissionServiceMock->method('canOrThrow')
            ->willThrowException(new NotAllowedException('This action is unauthorized.'));

        // current logged in user, not admin
        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct();

        $userProduct1 = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
            ]
        );

        $response = $this->call(
            'PUT',
            '/user-product',
            [
                'data' => [
                    'type' => 'userProduct',
                    'attributes' => [
                        'quantity' => $userProduct1['quantity'],
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'product' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $product['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert the response status code
        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'This action is unauthorized.',
            ],
            $response->decodeResponseJson('error')
        );
    }

    public function test_store_user_product()
    {
        $userId = $this->createAndLogInNewUser();

        $product = $this->fakeProduct();

        $userProduct1 = $this->faker->userProduct(
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
            ]
        );

        $response = $this->call(
            'PUT',
            '/user-product',
            [
                'data' => [
                    'type' => 'userProduct',
                    'attributes' => [
                        'quantity' => $userProduct1['quantity'],
                        'start_date' => $userProduct1['start_date'],
                        'expiration_date' => $userProduct1['expiration_date'],
                    ],
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'product' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $product['id']
                            ]
                        ]
                    ]
                ],
            ]
        );
        // assert the response status code
        $this->assertEquals(201, $response->getStatusCode());

        // assert that the new created address it's returned in response in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'userProduct',
                    'id' => 1,
                    'attributes' => array_diff_key(
                        $userProduct1,
                        [
                            'id' => true,
                            'user_id' => true,
                            'product_id' => true,
                        ]
                    ),
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
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
                        'type' => 'user',
                        'id' => $userId
                    ],
                    [
                        'type' => 'product',
                        'id' => $product['id']
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        //assert that the address exists in the database
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            $userProduct1 + [
                'user_id' => $userId
            ]
        );
    }

    public function test_update_not_found()
    {
        //take a fake address id
        $randomId = rand();
        $results = $this->call(
            'PATCH',
            '/address/' . $randomId,
            [
                'user_id' => $this->createAndLogInNewUser(),
            ]
        );

        //assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        //assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                [
                    'title' => 'Not found.',
                    'detail' => 'Update failed, address not found with id: ' . $randomId,
                ]
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_user_update_his_address_response()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $userId = $this->createAndLogInNewUser();

        $product1 = $this->fakeProduct();
        $newProduct = $this->fakeProduct();
        $newExpirationDate = Carbon::now()->addMonth(1)->toDateTimeString();

        $userProduct1 = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $product1['id'],
            ]
        );

        $response = $this->call(
            'PATCH',
            '/user-product/' . $userProduct1['id'],
            [
                'data' => [
                    'id' => $userProduct1['id'],
                    'type' => 'userProduct',
                    'attributes' => [
                        'expiration_date' => $newExpirationDate,
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $newProduct['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        $userProduct1['expiration_date'] = $newExpirationDate;

        //assert the response status code
        $this->assertEquals(200, $response->getStatusCode());

        //assert that the updated address  it's returned in JSON format
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'userProduct',
                    'id' => 1,
                    'attributes' => array_diff_key(
                        $userProduct1,
                        [
                            'id' => true,
                            'user_id' => true,
                            'product_id' => true,
                        ]
                    ),
                    'relationships' => [
                        'user' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $userId
                            ]
                        ],
                        'product' => [
                            'data' => [
                                'type' => 'product',
                                'id' => $newProduct['id']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $userId
                    ],
                    [
                        'type' => 'product',
                        'id' => $newProduct['id']
                    ]
                ]
            ],
            $response->decodeResponseJson()
        );

        $userProduct1['product_id'] = $newProduct['id'];

        //assert that the address exists in the database
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            $userProduct1
        );
    }

    public function test_update_response_unauthorized_user()
    {
        // current logged in user
        $userId = $this->createAndLogInNewUser();

        $product1 = $this->fakeProduct();
        $newProduct = $this->fakeProduct();
        $newExpirationDate = Carbon::now()->addMonth(1)->toDateTimeString();

        $userProduct1 = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $product1['id'],
            ]
        );

        $response = $this->call(
            'PATCH',
            '/user-product/' . $userProduct1['id'],
            [
                'data' => [
                    'id' => $userProduct1['id'],
                    'type' => 'userProduct',
                    'attributes' => [
                        'expiration_date' => $newExpirationDate,
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => [
                                'type' => 'user',
                                'id' => $newProduct['id']
                            ]
                        ]
                    ]
                ],
            ]
        );

        // assert that the logged in user can not update other user's address if it's not admin
        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'This action is unauthorized.',
            ],
            $response->decodeResponseJson('error')
        );
    }

    public function test_delete_unauthorized_user()
    {
        $this->permissionServiceMock->method('canOrThrow')->willThrowException(new NotAllowedException('This action is unauthorized.'));

        $userId = $this->createAndLogInNewUser();

        $product1 = $this->fakeProduct();

        $userProduct1 = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $product1['id'],
            ]
        );

        $response = $this->call('DELETE', '/user-product/' . $userProduct1['id']);

        // assert that the logged in user have not access if it's unauthorized
        $this->assertEquals(403, $response->getStatusCode());

        $this->assertEquals(
            [
                'title' => 'Not allowed.',
                'detail' => 'This action is unauthorized.'
            ],
            $response->decodeResponseJson('error')
        );
    }
}
