<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ProductControllerTest extends EcommerceTestCase
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountCriteriaRepository
     */
    protected $discountCriteriaRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountRepository
     */
    protected $discountRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderItemRepository
     */
    protected $orderItemRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->productRepository          = $this->app->make(ProductRepository::class);
        $this->discountRepository         = $this->app->make(DiscountRepository::class);
        $this->discountCriteriaRepository = $this->app->make(DiscountCriteriaRepository::class);
        $this->orderItemRepository        = $this->app->make(OrderItemRepository::class);
    }

    public function test_store_product()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $product = $this->faker->product();

        $results = $this->call('PUT', '/product/', $product);

        //assert response
        $this->assertEquals(200, $results->getStatusCode());

        //assert product data subset or results
        $this->assertArraySubset($product, $results->decodeResponseJson('data')[0]);

        //assert the product was saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tableProduct,
            $product
        );
    }

    public function test_store_subscription()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $subscription = $this->faker->product(['type' => ConfigService::$typeSubscription]);
        $results      = $this->call('PUT', '/product/', $subscription);

        $jsonResponse = $results->decodeResponseJson();

        //assert results status code
        $this->assertEquals(200, $results->getStatusCode());

        //assert subscription data subset of response
        $this->assertArraySubset($subscription, $jsonResponse['data'][0]);

        //assert subscription data exist in db
        $this->assertDatabaseHas(
            ConfigService::$tableProduct,
            $subscription
        );
    }

    public function test_validation_on_store_product()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $results = $this->call('PUT', '/product/');

        $this->assertEquals(422, $results->status());

        //assert that all the error messages are received
        $errors = [
            [
                'source' => "name",
                "detail" => "The name field is required."
            ],
            [
                'source' => "sku",
                "detail" => "The sku field is required."
            ],
            [
                'source' => "price",
                "detail" => "The price field is required."
            ],
            [
                'source' => "type",
                "detail" => "The type field is required."
            ],
            [
                'source' => "active",
                "detail" => "The active field is required."
            ],
            [
                'source' => "is_physical",
                "detail" => "The is physical field is required."
            ],
            [
                'source' => "stock",
                "detail" => "The stock field is required."
            ]
        ];

        $this->assertEquals($errors, json_decode($results->content(), true)['errors']);
    }

    public function test_validation_for_new_subscription()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $results = $this->call('PUT', '/product/', [
            'name'        => $this->faker->word,
            'sku'         => $this->faker->word,
            'price'       => $this->faker->numberBetween(15.97, 15.99),
            'type'        => ConfigService::$typeSubscription,
            'active'      => true,
            'is_physical' => false,
            'stock'       => $this->faker->numberBetween(0, 1000)
        ]);

        $this->assertEquals(422, $results->status());

        //check that the proper error messages are received
        $errors = [
            [
                'source' => "subscription_interval_type",
                "detail" => "The subscription interval type field is required when type is subscription."
            ],
            [
                'source' => "subscription_interval_count",
                "detail" => "The subscription interval count field is required when type is subscription."
            ]
        ];

        $this->assertEquals($errors, json_decode($results->content(), true)['errors']);
    }

    public function test_validation_sku_unique()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $product = $this->productRepository->create($this->faker->product());

        $productWithExistingSKU = $this->faker->product([
            'sku' => $product['sku']
        ]);

        $results = $this->call('PUT', '/product/', $productWithExistingSKU);

        //assert response status
        $this->assertEquals(422, $results->status());

        //assert that the proper error messages are received
        $errors = [
            [
                'source' => "sku",
                "detail" => "The sku has already been taken."
            ]
        ];
        $this->assertEquals($errors, json_decode($results->content(), true)['errors']);

        //assert product with the same sku was not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tableProduct,
            $productWithExistingSKU
        );
    }

    public function test_validation_weight_for_physical_products()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $results = $this->call('PUT', '/product/', [
            'name'        => $this->faker->word,
            'sku'         => $this->faker->word,
            'price'       => $this->faker->numberBetween(15.97, 15.99),
            'type'        => ConfigService::$typeProduct,
            'active'      => true,
            'is_physical' => true,
            'stock'       => $this->faker->numberBetween(0, 1000)
        ]);

        $this->assertEquals(422, $results->status());

        //check that the proper error messages are received
        $errors = [
            [
                'source' => "weight",
                "detail" => "The weight field is required when is physical is 1."
            ]
        ];

        $this->assertEquals($errors, json_decode($results->content(), true)['errors']);
    }

    public function test_update_product_inexistent()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $randomProductId = rand();
        $results         = $this->call('PATCH', '/product/' . $randomProductId);

        //assert a response with 404 status
        $this->assertEquals(404, $results->status());

        //assert that the error message is received
        $errors = [
            'title'  => "Not found.",
            "detail" => "Update failed, product not found with id: " . $randomProductId
        ];
        $this->assertEquals($errors, json_decode($results->content(), true)['error']);
    }

    public function test_update_product()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $product = $this->productRepository->create($this->faker->product());

        $newDescription = $this->faker->text;

        $results = $this->call('PATCH', '/product/' . $product['id'], [
            'description' => $newDescription
        ]);

        $jsonResponse = $results->decodeResponseJson();

        //assert response status code
        $this->assertEquals(201, $results->getStatusCode());

        unset($product['order']);
        unset($product['discounts']);

        //assert product with the new description subset of response
        $product['description'] = $newDescription;
        $product['updated_on']  = Carbon::now()->toDateTimeString();
        $this->assertArraySubset($product, $jsonResponse['data'][0]);

        //assert product updated in the db
        $this->assertDatabaseHas(
            ConfigService::$tableProduct,
            iterator_to_array($product)
        );
    }

    public function test_validation_on_update_product()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);
        $product = $this->productRepository->create($this->faker->product());

        $results = $this->call('PATCH', '/product/' . $product['id'], [
            'type' => ConfigService::$typeSubscription
        ]);

        //assert response code
        $this->assertEquals(422, $results->status());

        //assert that the proper error messages are received
        $errors = [
            [
                'source' => "subscription_interval_type",
                "detail" => "The subscription interval type field is required when type is subscription."
            ],
            [
                'source' => "subscription_interval_count",
                "detail" => "The subscription interval count field is required when type is subscription."
            ]
        ];
        $this->assertEquals($errors, json_decode($results->content(), true)['errors']);

        unset($product['order']);
        unset($product['discounts']);

        //assert product raw was not modified in db
        $this->assertDatabaseHas(
            ConfigService::$tableProduct,
            iterator_to_array($product)
        );
    }

    public function test_delete_missing_product()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $randomId = rand();
        $results  = $this->call('DELETE', '/product/' . $randomId);

        $this->assertEquals(404, $results->status());
        $this->assertEquals('Not found.', json_decode($results->getContent())->error->title, true);
        $this->assertEquals('Delete failed, product not found with id: ' . $randomId, json_decode($results->getContent())->error->detail, true);
    }

    public function test_delete_product_when_exists_product_order()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);
        $userId = $this->createAndLogInNewUser();

        $product   = $this->productRepository->create($this->faker->product());
        $orderItem = $this->orderItemRepository->create($this->faker->orderItem([
            'product_id' => $product['id']
        ]));

        $results   = $this->call('DELETE', '/product/' . $product['id']);

        $this->assertEquals(403, $results->status());
        $this->assertEquals('Not allowed.', json_decode($results->getContent())->error->title, true);
        $this->assertEquals('Delete failed, exists orders that contain the selected product.', json_decode($results->getContent())->error->detail, true);
    }

    public function test_delete_product_when_exists_product_discounts()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);
        $userId = $this->createAndLogInNewUser();

        $product          = $this->productRepository->create($this->faker->product());
        $discount         = $this->discountRepository->create($this->faker->discount([
            'active' => 1
        ]));
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'product_id'  => $product['id'],
            'discount_id' => $discount['id']
        ]));
        $results          = $this->call('DELETE', '/product/' . $product['id']);

        $this->assertEquals(403, $results->status());
        $this->assertEquals('Not allowed.', json_decode($results->getContent())->error->title, true);
        $this->assertEquals('Delete failed, exists discounts defined for the selected product.', json_decode($results->getContent())->error->detail, true);
    }

    public function test_delete_product()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);
        $product = $this->productRepository->create($this->faker->product());

        $results = $this->call('DELETE', '/product/' . $product['id']);

        $this->assertEquals(204, $results->status());
        $this->assertDatabaseMissing(ConfigService::$tableProduct,
            [
                'id' => $product['id'],
            ]);
    }

    public function test_get_all_products_paginated_when_empty()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);
        $results         = $this->call('GET', '/product');
        $expectedResults = [
            'data'       => [],
            'meta' => [
                'totalResults' => 0,
                'page' => 1,
                'limit' => 10
            ]
        ];

        $this->assertEquals(200, $results->status());
        $results->assertJson($expectedResults);
    }

    public function test_admin_get_all_paginated_products()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $page       = 1;
        $limit      = 30;
        $sort       = 'id';
        $nrProducts = 10;

        for($i = 0; $i < $nrProducts; $i++)
        {
            $product    = $this->productRepository->create($this->faker->product());
            $products[] = iterator_to_array($product);
        }

        $results = $this->call('GET', '/product',
            [
                'page'               => $page,
                'limit'              => $limit,
                'order_by_column'    => $sort,
                'order_by_direction' => 'asc'
            ]);

        $this->assertEquals($products, $results->decodeResponseJson('data'));
    }

    public function test_upload_thumb()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('is')->willReturn(true);

        $filenameAbsolute = $this->faker->image(sys_get_temp_dir());
        $filenameRelative = $this->getFilenameRelativeFromAbsolute($filenameAbsolute);

        $response = $this->call('PUT', '/product/upload/', [
            'target' => $filenameRelative,
            'file'   => new UploadedFile($filenameAbsolute, $filenameRelative)
        ]);

        $this->assertEquals(201, $response->status());

        $this->assertEquals(
            storage_path('app') . '/' . $filenameRelative,
            $response->decodeResponseJson('results')
        );
    }

    public function test_user_pull_only_active_products()
    {
        $user = $this->createAndLogInNewUser();

        $page       = 2;
        $limit      = 3;
        $sort       = 'id';
        $nrProducts = 10;

        for($i = 0; $i < $nrProducts; $i++)
        {
            if($i % 2 == 0)
            {
                $product    = $this->productRepository->create($this->faker->product(['active' => true]));
                $products[] = iterator_to_array($product);
            }
            else
            {
                $product            = $this->productRepository->create($this->faker->product(['active' => false]));
                $inactiveProducts[] = $product;
            }
        }

        $expectedContent =
            [
                'data'       => array_slice($products, 3, $limit),
                'meta' => [
                    'totalResults' => $nrProducts/2,
                    'page' => $page,
                    'limit' => $limit
                ]
            ];

        $results = $this->call('GET', '/product',
            [
                'page'               => $page,
                'limit'              => $limit,
                'order_by_column'    => $sort,
                'order_by_direction' => 'asc'
            ]);

        $responseContent = $results->decodeResponseJson();
        $this->assertEquals($expectedContent, $responseContent);
    }
}
