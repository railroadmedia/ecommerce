<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
// use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
// use Railroad\Ecommerce\Repositories\DiscountRepository;
// use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class DiscountJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountRepository
     */
    // protected $discountRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountCriteriaRepository
     */
    // protected $discountCriteriaRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    // protected $productRepository;

    public function setUp()
    {
        parent::setUp();
        // $this->discountRepository         = $this->app->make(DiscountRepository::class);
        // $this->discountCriteriaRepository = $this->app->make(DiscountCriteriaRepository::class);
        // $this->productRepository          = $this->app->make(ProductRepository::class);
    }

    // public function test_store_validation()
    // {
    //     $results = $this->call('PUT', '/discount', []);

    //     //assert the response status code
    //     $this->assertEquals(422, $results->getStatusCode());

    //     //assert that all the validation errors are returned
    //     $this->assertEquals([
    //         [
    //             "source" => "name",
    //             "detail" => "The name field is required.",
    //         ],
    //         [
    //             "source" => "description",
    //             "detail" => "The description field is required.",
    //         ],
    //         [
    //             "source" => "type",
    //             "detail" => "The type field is required.",
    //         ],
    //         [
    //             "source" => "amount",
    //             "detail" => "The amount field is required."
    //         ],
    //         [
    //             "source" => "active",
    //             "detail" => "The active field is required."
    //         ],
    //         [
    //             "source" => "visible",
    //             "detail" => "The visible field is required."
    //         ]
    //     ], $results->decodeResponseJson('meta')['errors']);
    // }

    // public function test_store()
    // {
    //     $discount = $this->faker->discount();
    //     $results  = $this->call('PUT', '/discount', $discount);

    //     //assert the response status code
    //     $this->assertEquals(200, $results->getStatusCode());

    //     //assert that the new created discount it's returned in response in JSON format
    //     $this->assertArraySubset($discount, $results->decodeResponseJson()['data'][0]);

    //     //assert that the discount exists in the database
    //     $this->assertDatabaseHas(ConfigService::$tableDiscount, $discount);
    // }

    // public function test_update_missing_discount()
    // {
    //     //take a fake discount id
    //     $randomId = rand();
    //     $results  = $this->call('PATCH', '/discount/' . $randomId);

    //     //assert response status code
    //     $this->assertEquals(404, $results->getStatusCode());

    //     //assert the error message that it's returned in JSON format
    //     $this->assertEquals(
    //         [
    //             "title"  => "Not found.",
    //             "detail" => "Update failed, discount not found with id: " . $randomId,
    //         ]
    //         , $results->decodeResponseJson('meta')['errors']);
    // }

    // public function test_update()
    // {
    //     $discount = $this->discountRepository->create($this->faker->discount());

    //     $newName = $this->faker->word;

    //     $results = $this->call('PATCH', '/discount/' . $discount['id'],
    //         [
    //             'name' => $newName
    //         ]);

    //     //assert response status code
    //     $this->assertEquals(201, $results->getStatusCode());

    //     //assert the discount it's returned in JSON format
    //     $this->assertArraySubset(
    //         [
    //             'name'        => $newName,
    //             'description' => $discount['description'],
    //             'type'        => $discount['type'],
    //             'amount'      => $discount['amount'],
    //             'active'      => $discount['active'],
    //             'updated_on'  => Carbon::now()->toDateTimeString()
    //         ]
    //         , $results->decodeResponseJson()['data'][0]);
    // }

    // public function test_delete()
    // {
    //     $discount = $this->discountRepository->create($this->faker->discount());

    //     $results = $this->call('DELETE', '/discount/' . $discount['id']);

    //     //assert response status code
    //     $this->assertEquals(204, $results->getStatusCode());

    //     //assert that the discount not exists anymore in the database
    //     $this->assertDatabaseMissing(ConfigService::$tableDiscount, $discount->getArrayCopy());
    // }

    // public function test_pull_discounts_empty() // deprecated
    // {
    //     $results = $this->call('GET', '/discounts');

    //     $this->assertEmpty($results->decodeResponseJson('results'));
    //     $this->assertEquals(0, $results->decodeResponseJson('total_results'));
    // }

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
                'page'               => $page,
                'limit'              => $limit,
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
