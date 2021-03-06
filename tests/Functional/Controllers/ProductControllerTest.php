<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Railroad\Ecommerce\Factories\ProductFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ProductControllerTest extends EcommerceTestCase
{
    /**
     * @var ProductFactory
     */
    protected $productFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->productFactory = $this->app->make(ProductFactory::class);
    }

    public function test_store_product()
    {
        $userId = $this->createAndLoginAdminUser();

        $product = ['name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(15.97, 15.99),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => true,
            'is_physical' => false,
            'stock' => $this->faker->numberBetween(0, 1000)];
        $results = $this->call('PUT', '/product/', $product);
        $jsonResponse = $results->decodeResponseJson();

        $product['active'] = 1;
        $product['is_physical'] = 0;

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals(array_merge(
            [
                'id' => 1,
                'brand' => ConfigService::$brand,
                'description' => null,
                'thumbnail_url' => null,
                'weight' => null,
                'subscription_interval_type' => null,
                'subscription_interval_count' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
            , $product), $jsonResponse['results']);
    }

    public function test_store_subscription()
    {
        $userId = $this->createAndLoginAdminUser();

        $subscription = [
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(15.97, 15.99),
            'type' => ProductService::TYPE_SUBSCRIPTION,
            'active' => true,
            'is_physical' => false,
            'stock' => $this->faker->numberBetween(0, 1000),
            'subscription_interval_type' => 'year',
            'subscription_interval_count' => 1];
        $results = $this->call('PUT', '/product/', $subscription);

        $jsonResponse = $results->decodeResponseJson();

        $subscription['active'] = 1;
        $subscription['is_physical'] = 0;

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals(array_merge(
            [
                'id' => 1,
                'brand' => ConfigService::$brand,
                'description' => null,
                'thumbnail_url' => null,
                'weight' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
            , $subscription), $jsonResponse['results']);
    }

    public function test_validation_on_store_product()
    {
        $userId = $this->createAndLoginAdminUser();

        $results = $this->call('PUT', '/product/');

        $this->assertEquals(422, $results->status());

        //check that all the error messages are received
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
        $userId = $this->createAndLoginAdminUser();

        $results = $this->call('PUT', '/product/', [
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(15.97, 15.99),
            'type' => ProductService::TYPE_SUBSCRIPTION,
            'active' => true,
            'is_physical' => false,
            'stock' => $this->faker->numberBetween(0, 1000)
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
        $userId = $this->createAndLoginAdminUser();

        $product = $this->productFactory->store();

        $results = $this->call('PUT', '/product/', [
            'name' => $this->faker->word,
            'sku' => $product['sku'],
            'price' => $this->faker->numberBetween(1, 15.99),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => true,
            'is_physical' => false,
            'stock' => $this->faker->numberBetween(0, 1000)
        ]);

        $this->assertEquals(422, $results->status());

        //check that the proper error messages are received
        $errors = [
            [
                'source' => "sku",
                "detail" => "The sku has already been taken."
            ]
        ];

        $this->assertEquals($errors, json_decode($results->content(), true)['errors']);
    }

    public function test_validation_weight_for_physical_products()
    {
        $userId = $this->createAndLoginAdminUser();

        $results = $this->call('PUT', '/product/', [
            'name' => $this->faker->word,
            'sku' => $this->faker->word,
            'price' => $this->faker->numberBetween(15.97, 15.99),
            'type' => ProductService::TYPE_PRODUCT,
            'active' => true,
            'is_physical' => true,
            'stock' => $this->faker->numberBetween(0, 1000)
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
        $userId = $this->createAndLoginAdminUser();

        $randomProductId = rand();
        $results = $this->call('PATCH', '/product/' . $randomProductId);

        //expecting a response with 404 status
        $this->assertEquals(404, $results->status());

        //check that the error message is received
        $errors = [
            'title' => "Not found.",
            "detail" => "Update failed, product not found with id: " . $randomProductId
        ];
        $this->assertEquals($errors, json_decode($results->content(), true)['error']);
    }

    public function test_update_product()
    {
        $userId = $this->createAndLoginAdminUser();

        $product = $this->productFactory->store();
        $newDescription = $this->faker->text;

        $results = $this->call('PATCH', '/product/' . $product['id'], [
            'description' => $newDescription
        ]);

        $jsonResponse = $results->decodeResponseJson();

        $this->assertEquals(201, $results->getStatusCode());
        $product['description'] = $newDescription;
        $product['updated_on'] = Carbon::now()->toDateTimeString();

        $this->assertEquals($product, $jsonResponse['results']);
    }

    public function test_validation_on_update_product()
    {
        $userId = $this->createAndLoginAdminUser();

        $product = $this->productFactory->store();
        $newDescription = $this->faker->text;

        $results = $this->call('PATCH', '/product/' . $product['id'], [
            'type' => ProductService::TYPE_SUBSCRIPTION
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

    public function test_delete_missing_product()
    {
        $userId = $this->createAndLoginAdminUser();

        $randomId = rand();
        $results = $this->call('DELETE', '/product/' . $randomId);

        $this->assertEquals(404, $results->status());
        $this->assertEquals('Not found.', json_decode($results->getContent())->error->title, true);
        $this->assertEquals('Delete failed, product not found with id: ' . $randomId, json_decode($results->getContent())->error->detail, true);
    }

    public function test_delete_product_when_exists_product_order()
    {
        $userId = $this->createAndLoginAdminUser();

        $product = $this->productFactory->store();

        $orderItem1 = [
            'order_id' => 1,
            'product_id' => $product['id'],
            'quantity' => 2,
            'initial_price' => 5,
            'discount' => 0,
            'tax' => 0,
            'shipping_costs' => 0,
            'total_price' => 10,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ];

        $orderItemId = $this->query()->table(ConfigService::$tableOrderItem)->insertGetId($orderItem1);

        $results = $this->call('DELETE', '/product/' . $product['id']);

        $this->assertEquals(403, $results->status());
        $this->assertEquals('Not allowed.', json_decode($results->getContent())->error->title, true);
        $this->assertEquals('Delete failed, exists orders that contain the selected product.', json_decode($results->getContent())->error->detail, true);
    }

    public function test_delete_product_when_exists_product_discounts()
    {
        $userId = $this->createAndLoginAdminUser();

        $product = $this->productFactory->store();

        $discount = [
            'name' => $this->faker->word,
            'type' => $this->faker->word,
            'product_id' => $product['id'],
            'min' => 2,
            'max' => 10,
            'discount_id' => rand(),
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ];

        $this->query()->table(ConfigService::$tableDiscountCriteria)->insertGetId($discount);
        $results = $this->call('DELETE', '/product/' . $product['id']);

        $this->assertEquals(403, $results->status());
        $this->assertEquals('Not allowed.', json_decode($results->getContent())->error->title, true);
        $this->assertEquals('Delete failed, exists discounts defined for the selected product.', json_decode($results->getContent())->error->detail, true);
    }

    public function test_delete_product()
    {
        $userId = $this->createAndLoginAdminUser();

        $product = $this->productFactory->store();

        $results = $this->call('DELETE', '/product/' . $product['id']);

        $this->assertEquals(204, $results->status());
        $this->assertDatabaseMissing(ConfigService::$tableProduct,
            [
                'id' => $product['id'],
            ]);
    }

    public function test_get_all_products_paginated_when_empty()
    {

        $results = $this->call('GET', '/product');
        $expectedResults = [
            'results' => [],
            'total_results' => 0
        ];

        $this->assertEquals(200, $results->status());
        $results->assertJson($expectedResults);
    }

    public function test_admin_get_all_paginated_products()
    {
        $userId = $this->createAndLoginAdminUser();

        $page = 2;
        $limit = 3;
        $sort = 'id';
        $nrProducts = 10;

        for($i=0; $i<$nrProducts; $i++)
        {
            $products[] = $this->productFactory->store();
        }

        $expectedContent =
            [
                'status' => 'ok',
                'code' => 200,
                'page' => $page,
                'limit' => $limit,
                'results' => array_slice($products, 3, $limit),
                'total_results' => $nrProducts
            ];

        $results = $this->call('GET', '/product',
            [
                'page' => $page,
                'limit' => $limit,
                'sort' => $sort
            ]);

        $responseContent = $results->decodeResponseJson();
        $this->assertEquals($expectedContent, $responseContent);
    }

    public function test_upload_thumb()
    {
        $userId = $this->createAndLoginAdminUser();

        $filenameAbsolute = $this->faker->image(sys_get_temp_dir());
        $filenameRelative = $this->getFilenameRelativeFromAbsolute($filenameAbsolute);

        $response = $this->call( 'PUT', '/product/upload/', [
            'target' => $filenameRelative,
            'file' => new UploadedFile($filenameAbsolute, $filenameRelative)
        ] );

        $this->assertEquals(201, $response->status());

        $this->assertEquals(
            storage_path('app').'/' . $filenameRelative,
            json_decode($response->getContent())->results
        );
    }

    public function test_user_pull_only_active_products()
    {
        $user = $this->createAndLogInNewUser();

        $page = 2;
        $limit = 3;
        $sort = 'id';
        $nrProducts = 10;

        for($i=0; $i<$nrProducts; $i++)
        {
            if($i%2==0) {
                $products[] = $this->productFactory->store(ConfigService::$brand,
                    $this->faker->word,
                    $this->faker->word,
                    $this->faker->numberBetween(1, 2000),
                    $this->faker->randomElement(
                        [
                            ProductService::TYPE_PRODUCT,
                            ProductService::TYPE_SUBSCRIPTION
                        ]
                    ),
                    true);
            }else {
                $this->productFactory->store(ConfigService::$brand,
                    $this->faker->word,
                    $this->faker->word,
                    $this->faker->numberBetween(1, 2000),
                    $this->faker->randomElement(
                        [
                            ProductService::TYPE_PRODUCT,
                            ProductService::TYPE_SUBSCRIPTION
                        ]
                    ),
                    false);
            }
        }

        $expectedContent =
            [
                'status' => 'ok',
                'code' => 200,
                'page' => $page,
                'limit' => $limit,
                'results' => array_slice($products, 3, $limit),
                'total_results' => $nrProducts/2
            ];

        $results = $this->call('GET', '/product',
            [
                'page' => $page,
                'limit' => $limit,
                'sort' => $sort
            ]);

        $responseContent = $results->decodeResponseJson();
        $this->assertEquals($expectedContent, $responseContent);


    }
    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
