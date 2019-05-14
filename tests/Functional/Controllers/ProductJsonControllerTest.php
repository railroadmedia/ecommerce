<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ProductJsonControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_store_product()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $product = $this->faker->product();

        $results = $this->call(
            'PUT',
            '/product/',
            [
                'data' => [
                    'type' => 'product',
                    'attributes' => $product
                ]
            ]
        );

        // assert response
        $this->assertEquals(200, $results->getStatusCode());

        // assert product data subset or results
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'product',
                    'attributes' => array_diff_key(
                        $product,
                        [
                            'id' => true
                        ]
                    ),
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert the product was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_products',
            $product
        );
    }

    public function test_store_subscription()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $subscription = $product = $this->faker->product(
            ['type' => Product::TYPE_SUBSCRIPTION]
        );

        $results = $this->call(
            'PUT',
            '/product/',
            [
                'data' => [
                    'type' => 'product',
                    'attributes' => $subscription
                ]
            ]
        );

        // assert results status code
        $this->assertEquals(200, $results->getStatusCode());

        // assert subscription data subset of response
        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'product',
                    'attributes' => array_diff_key(
                        $subscription,
                        [
                            'id' => true
                        ]
                    ),
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert subscription data exist in db
        $this->assertDatabaseHas(
            'ecommerce_products',
            $subscription
        );
    }

    public function test_validation_on_store_product()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $results = $this->call('PUT', '/product/');

        $this->assertEquals(422, $results->status());

        // assert that all the error messages are received
        $errors = [
            [
                'source' => 'data.attributes.name',
                'detail' => 'The name field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.sku',
                'detail' => 'The sku field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.price',
                'detail' => 'The price field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.type',
                'detail' => 'The type field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.active',
                'detail' => 'The active field is required.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.is_physical',
                'detail' => 'The is physical field is required.',
                'title' => 'Validation failed.'
            ]
        ];

        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);
    }

    public function test_validation_for_new_subscription()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $results = $this->call(
            'PUT',
            '/product/',
            [
                'data' => [
                    'type' => 'product',
                    'attributes' => [
                        'name' => $this->faker->word,
                        'sku' => $this->faker->word,
                        'price' => $this->faker->numberBetween(15.97, 15.99),
                        'type' => Product::TYPE_SUBSCRIPTION,
                        'active' => true,
                        'is_physical' => false,
                        'stock' => $this->faker->numberBetween(0, 1000)
                    ]
                ]
            ]
        );

        $this->assertEquals(422, $results->status());

        // check that the proper error messages are received
        $errors = [
            [
                'source' => 'data.attributes.subscription_interval_type',
                'detail' => 'The subscription interval type field is required when type is subscription.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.subscription_interval_count',
                'detail' => 'The subscription interval count field is required when type is subscription.',
                'title' => 'Validation failed.'
            ]
        ];

        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);
    }

    public function test_validation_sku_unique()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $product = $this->fakeProduct();

        $productWithExistingSKU = $this->faker->product([
            'sku' => $product['sku']
        ]);

        $results = $this->call(
            'PUT',
            '/product/',
            [
                'data' => [
                    'type' => 'product',
                    'attributes' => $productWithExistingSKU
                ]
            ]
        );

        // assert response status
        $this->assertEquals(422, $results->status());

        // assert that the proper error messages are received
        $errors = [
            [
                'source' => 'data.attributes.sku',
                'detail' => 'The sku has already been taken.',
                'title' => 'Validation failed.'
            ]
        ];
        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);

        // assert product with the same sku was not saved in the db
        $this->assertDatabaseMissing(
            'ecommerce_products',
            $productWithExistingSKU
        );
    }

    public function test_validation_weight_for_physical_products()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $results = $this->call(
            'PUT',
            '/product/',
            [
                'data' => [
                    'type' => 'product',
                    'attributes' => [
                        'name' => $this->faker->word,
                        'sku' => $this->faker->word,
                        'price' => $this->faker->numberBetween(15.97, 15.99),
                        'type' => Product::TYPE_PRODUCT,
                        'active' => true,
                        'is_physical' => true,
                        'stock' => $this->faker->numberBetween(0, 1000)
                    ]
                ]
            ]
        );

        $this->assertEquals(422, $results->status());

        // check that the proper error messages are received
        $errors = [
            [
                'source' => 'data.attributes.weight',
                'detail' => 'The weight field is required when is physical is 1.',
                'title' => 'Validation failed.'
            ]
        ];

        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);
    }

    public function test_update_product_inexistent()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $randomProductId = rand();
        $results = $this->call('PATCH', '/product/' . $randomProductId);

        // assert a response with 404 status
        $this->assertEquals(404, $results->status());

        // assert that the error message is received
        $errors = [
            'title'  => 'Not found.',
            'detail' => 'Update failed, product not found with id: ' . $randomProductId
        ];
        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);
    }

    public function test_update_product()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $product = $this->fakeProduct();

        $newDescription = $this->faker->text;

        $results = $this->call(
            'PATCH',
            '/product/' . $product['id'],
            [
                'data' => [
                    'id' => $product['id'],
                    'type' => 'product',
                    'attributes' => [
                        'description' => $newDescription
                    ],
                ],
            ]
        );

        // assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        // assert product with the new description subset of response
        $this->assertEquals(
            [
                'data' => [
                    'id' => $product['id'],
                    'type' => 'product',

                    // todo: this could possibly be done better
                    'attributes' => array_merge(
                        array_diff_key($product, ['id' => 1]),
                        [
                            'description' => $newDescription,
                            'updated_at' => Carbon::now()->toDateTimeString()
                        ]
                    ),
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert product updated in the db
        $this->assertDatabaseHas(
            'ecommerce_products',
            array_merge(
                $product,
                [
                    'description' => $newDescription,
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]
            )
        );
    }

    public function test_validation_on_update_product()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $product = $this->fakeProduct([
            'type' => Product::TYPE_PRODUCT,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null
        ]);

        $results = $this->call(
            'PATCH',
            '/product/' . $product['id'],
            [
                'data' => [
                    'id' => $product['id'],
                    'type' => 'product',
                    'attributes' => [
                        'type' => Product::TYPE_SUBSCRIPTION
                    ],
                ],
            ]
        );

        // assert response code
        $this->assertEquals(422, $results->status());

        // assert that the proper error messages are received
        $errors = [
            [
                'source' => 'data.attributes.subscription_interval_type',
                'detail' => 'The subscription interval type field is required when type is subscription.',
                'title' => 'Validation failed.'
            ],
            [
                'source' => 'data.attributes.subscription_interval_count',
                'detail' => 'The subscription interval count field is required when type is subscription.',
                'title' => 'Validation failed.'
            ]
        ];
        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);

        // assert product raw was not modified in db
        $this->assertDatabaseHas(
            'ecommerce_products',
            $product
        );
    }

    public function test_delete_missing_product()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $randomId = rand();
        $results  = $this->call('DELETE', '/product/' . $randomId);

        // assert that the proper error messages are received
        $errors = [
            'detail' => 'Delete failed, product not found with id: ' . $randomId,
            'title' => 'Not found.'
        ];
        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);
    }

    public function test_delete_product()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $product = $this->fakeProduct();

        $results = $this->call('DELETE', '/product/' . $product['id']);

        // assert response code
        $this->assertEquals(204, $results->status());

        // assert product was removed from db
        $this->assertDatabaseMissing('ecommerce_products', [
            'id' => $product['id'],
        ]);
    }

    public function test_admin_get_all_paginated_products()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $user = $this->createAndLogInNewUser();

        $page = 1;
        $limit = 30;
        $sort = 'id';
        $nrProducts = 10;
        $products = [];

        for ($i = 0; $i < $nrProducts; $i++) {
            $products[] = $this->fakeProduct();
        }

        $expected = [
            'data' => array_values(
                collect($products)
                    ->slice(($page - 1) * $limit, $limit)
                    ->map(function($product, $key) {
                        return [
                            'type' => 'product',
                            'id' => $product['id'],
                            'attributes' => array_merge(
                                array_diff_key( // get an array copy of product, without specified keys
                                    $product,
                                    [
                                        'id' => 1,
                                        'is_physical' => 1
                                    ]
                                ),
                                [ // fix php type juggling
                                    'is_physical' => (bool) $product['is_physical'],
                                    'updated_at' => null
                                ]
                            )
                        ];
                    })
                    ->all()
            )
        ];

        $results = $this->call(
            'GET',
            '/products',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => $sort,
                'order_by_direction' => 'asc'
            ]
        );

        $this->assertArraySubset($expected, $results->decodeResponseJson());
    }

    public function test_upload_thumb()
    {
        $userId = $this->createAndLogInNewUser();

        $filenameAbsolute = $this->faker->image(sys_get_temp_dir());
        /*
        Faker\Provider\Image::image returns false from block:

        if (!$success) {
            unlink($filepath);

            // could not contact the distant URL or HTTP error - fail silently.
            return false;
        }

        to be re-tested
        */

        $filenameRelative = $this->getFilenameRelativeFromAbsolute($filenameAbsolute);

        $response = $this->call('PUT', '/product/upload', [
            'target' => $filenameRelative,
            'file'   => new UploadedFile($filenameAbsolute, $filenameRelative)
        ]);

        $this->assertEquals(200, $response->status());

        $this->assertEquals(
            storage_path('app') . '/' . $filenameRelative,
            $response->decodeResponseJson('meta')['url']
        );
    }

    public function test_user_pull_only_active_products()
    {
        $user = $this->createAndLogInNewUser();

        $page = 2;
        $limit = 3;
        $sort = 'id';
        $nrProducts = 10;
        $products = [];
        $inactiveProducts = [];

        for ($i = 0; $i < $nrProducts; $i++) {
            if ($i % 2 == 0) {
                $product = $this->fakeProduct(['active' => true]);
                $products[] = $product;
            } else {
                $product = $this->fakeProduct(['active' => false]);
                $inactiveProducts[] = $product;
            }
        }

        $expected = [
            'data' => array_values(
                collect($products)
                    ->slice(($page - 1) * $limit, $limit)
                    ->map(function($product, $key) {
                        return [
                            'type' => 'product',
                            'id' => $product['id'],
                            'attributes' => array_merge(
                                array_diff_key( // get an array copy of product, without specified keys
                                    $product,
                                    [
                                        'id' => 1,
                                        'is_physical' => 1
                                    ]
                                ),
                                [ // fix php type juggling
                                    'is_physical' => (bool) $product['is_physical'],
                                    'updated_at' => null
                                ]
                            )
                        ];
                    })
                    ->all()
            )
        ];

        $results = $this->call(
            'GET',
            '/products',
            [
                'page' => $page,
                'limit' => $limit,
                'order_by_column' => $sort,
                'order_by_direction' => 'asc'
            ]
        );

        $this->assertArraySubset($expected, $results->decodeResponseJson());
    }

    public function test_update_product_same_SKU_pass_validation()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $product = $this->fakeProduct();

        $newDescription = $this->faker->text;

        $results = $this->call('PATCH', '/product/' . $product['id'], [
            'description' => $newDescription, 'sku' => $product['sku']
        ]);

        $results = $this->call(
            'PATCH',
            '/product/' . $product['id'],
            [
                'data' => [
                    'type' => 'product',
                    'attributes' => [
                        'description' => $newDescription,
                        'sku' => $product['sku']
                    ]
                ]
            ]
        );

        // assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        // assert product with the new description subset of response
        $this->assertEquals(
            [
                'data' => [
                    'id' => $product['id'],
                    'type' => 'product',
                    // todo: this could possibly be done better
                    'attributes' => array_merge(
                        array_diff_key($product, ['id' => 1]),
                        [
                            'description' => $newDescription,
                            'updated_at' => Carbon::now()->toDateTimeString()
                        ]
                    ),
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert product updated in the db
        $this->assertDatabaseHas(
            'ecommerce_products',
            array_merge(
                $product,
                [
                    'description' => $newDescription,
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]
            )
        );
    }

    public function test_update_product_different_SKU_unique_validation()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $product1 = $this->fakeProduct();
        $product2 = $this->fakeProduct();

        $results = $this->call(
            'PATCH',
            '/product/' . $product2['id'],
            [
                'data' => [
                    'type' => 'product',
                    'attributes' => [
                        'sku' => $product1['sku']
                    ]
                ]
            ]
        );

        // assert response status code
        $this->assertEquals(422, $results->getStatusCode());

        // assert that the proper error messages are received
        $errors = [
            [
                'source' => 'data.attributes.sku',
                'detail' => 'The sku has already been taken.',
                'title' => 'Validation failed.'
            ]
        ];
        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);

        // assert product2 was not modified in db
        $this->assertDatabaseHas(
            'ecommerce_products',
            $product2
        );
    }

    public function test_pull_product_not_exist()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $randomId  = rand();
        $results = $this->call('GET','/product/'.$randomId);

        // assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert that the proper error messages are received
        $errors = [
            'title' => 'Not found.',
            'detail' => 'Pull failed, product not found with id: '.$randomId
        ];

        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);
    }

    public function test_pull_product()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(false);

        $product = $this->fakeProduct(['active' => 1]);

        $results = $this->call('GET', '/product/'.$product['id']);

        // assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'id' => $product['id'],
                    'type' => 'product',
                    'attributes' => array_diff_key(
                        $product,
                        [
                            'id' => true
                        ]
                    ),
                ],
            ],
            $results->decodeResponseJson()
        );
    }

    public function test_admin_pull_inactive_product()
    {
        $this->permissionServiceMock->method('can')->willReturn(true);

        $product = $this->fakeProduct(['active' => 0]);

        $results = $this->call('GET', '/product/'.$product['id']);

        // assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'id' => $product['id'],
                    'type' => 'product',
                    'attributes' => array_diff_key(
                        $product,
                        [
                            'id' => true
                        ]
                    ),
                ],
            ],
            $results->decodeResponseJson()
        );
    }

    public function test_user_can_not_pull_inative_product()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(false);

        $product = $this->fakeProduct(['active' => 0]);

        $results = $this->call('GET','/product/'.$product['id']);

        // assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        // assert that the proper error messages are received
        $errors = [
            'title' => 'Not found.',
            'detail' => 'Pull failed, product not found with id: '.$product['id']
        ];

        $this->assertEquals($errors, $results->decodeResponseJson()['errors']);
    }

    public function test_pull_products_multiple_brands()
    {
        $productFirstBrand = $this->fakeProduct([
            'active' => true,
            'brand' => $this->faker->word
        ]);

        $productSecondBrand = $this->fakeProduct([
            'active' => true,
            'brand' => $this->faker->word
        ]);

        $expected = [
            'data' => collect([$productFirstBrand, $productSecondBrand])
                ->map(function($product, $key) {
                    return [
                        'type' => 'product',
                        'id' => $product['id'],
                        'attributes' => array_merge(
                            array_diff_key( // get an array copy of product, without specified keys
                                $product,
                                [
                                    'id' => 1,
                                    'is_physical' => 1
                                ]
                            ),
                            [ // fix php type juggling
                                'is_physical' => (bool) $product['is_physical'],
                                'updated_at' => null
                            ]
                        )
                    ];
                })
                ->all()
        ];

        $results = $this->call(
            'GET',
            '/products?brands[]='.$productFirstBrand['brand'].'&brands[]='.$productSecondBrand['brand']
        );

        //assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset($expected, $results->decodeResponseJson());
    }

    public function test_pull_products_brands_not_set_on_request()
    {
        $productFirstBrand = $this->fakeProduct([
            'active' => true,
            'brand' => config('ecommerce.brand')
        ]);

        $productSecondBrand = $this->fakeProduct([
            'active' => true,
            'brand' => $this->faker->word
        ]);

        $results = $this->call('GET','/products');

        //assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        // assert product with the new description subset of response
        $this->assertArraySubset(
            [
                'data' => [
                    [
                        'id' => $productFirstBrand['id'],
                        'type' => 'product',
                        // todo: this could possibly be done better
                        'attributes' => array_merge(
                            array_diff_key($productFirstBrand, ['id' => 1]),
                            [
                                'is_physical' => (bool) $productFirstBrand['is_physical'],
                                'updated_at' => null
                            ]
                        ),
                    ]
                ],
            ],
            $results->decodeResponseJson()
        );
    }

    public function test_update_product_category()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $product = $this->fakeProduct();

        $newCategory = $this->faker->text;

        $results = $this->call(
            'PATCH',
            '/product/' . $product['id'],
            [
                'data' => [
                    'id' => $product['id'],
                    'type' => 'product',
                    'attributes' => [
                        'category' => $newCategory,
                        'sku' => $product['sku']
                    ],
                ],
            ]
        );

        // assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        // assert product with the new category subset of response
        $this->assertEquals(
            [
                'data' => [
                    'id' => $product['id'],
                    'type' => 'product',

                    // todo: this could possibly be done better
                    'attributes' => array_merge(
                        array_diff_key($product, ['id' => 1]),
                        [
                            'category' => $newCategory,
                            'updated_at' => Carbon::now()->toDateTimeString()
                        ]
                    ),
                ],
            ],
            $results->decodeResponseJson()
        );

        // assert product updated in the db
        $this->assertDatabaseHas(
            'ecommerce_products',
            array_merge(
                $product,
                [
                    'category' => $newCategory,
                    'updated_at' => Carbon::now()->toDateTimeString()
                ]
            )
        );
    }
}
