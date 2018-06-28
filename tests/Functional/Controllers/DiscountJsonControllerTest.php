<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class DiscountJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountRepository
     */
    protected $discountRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\DiscountCriteriaRepository
     */
    protected $discountCriteriaRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    protected $productRepository;

    public function setUp()
    {
        parent::setUp();
        $this->discountRepository         = $this->app->make(DiscountRepository::class);
        $this->discountCriteriaRepository = $this->app->make(DiscountCriteriaRepository::class);
        $this->productRepository          = $this->app->make(ProductRepository::class);
    }

    public function test_store_validation()
    {
        $results = $this->call('PUT', '/discount', []);

        //assert the response status code
        $this->assertEquals(422, $results->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals([
            [
                "source" => "name",
                "detail" => "The name field is required.",
            ],
            [
                "source" => "description",
                "detail" => "The description field is required.",
            ],
            [
                "source" => "type",
                "detail" => "The type field is required.",
            ],
            [
                "source" => "amount",
                "detail" => "The amount field is required."
            ],
            [
                "source" => "active",
                "detail" => "The active field is required."
            ]
        ], $results->decodeResponseJson('meta')['errors']);
    }

    public function test_store()
    {
        $discount = $this->faker->discount();
        $results  = $this->call('PUT', '/discount', $discount);

        //assert the response status code
        $this->assertEquals(200, $results->getStatusCode());

        //assert that the new created discount it's returned in response in JSON format
        $this->assertArraySubset($discount, $results->decodeResponseJson()['data'][0]);

        //assert that the discount exists in the database
        $this->assertDatabaseHas(ConfigService::$tableDiscount, $discount);
    }

    public function test_update_missing_discount()
    {
        //take a fake discount id
        $randomId = rand();
        $results  = $this->call('PATCH', '/discount/' . $randomId);

        //assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        //assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                "title"  => "Not found.",
                "detail" => "Update failed, discount not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson('meta')['errors']);
    }

    public function test_update()
    {
        $discount = $this->discountRepository->create($this->faker->discount());

        $newName = $this->faker->word;

        $results = $this->call('PATCH', '/discount/' . $discount['id'],
            [
                'name' => $newName
            ]);

        //assert response status code
        $this->assertEquals(201, $results->getStatusCode());

        //assert the discount it's returned in JSON format
        $this->assertArraySubset(
            [
                'name'        => $newName,
                'description' => $discount['description'],
                'type'        => $discount['type'],
                'amount'      => $discount['amount'],
                'active'      => $discount['active'],
                'updated_on'  => Carbon::now()->toDateTimeString()
            ]
            , $results->decodeResponseJson()['data'][0]);
    }

    public function test_delete()
    {
        $discount = $this->discountRepository->create($this->faker->discount());

        $results = $this->call('DELETE', '/discount/' . $discount['id']);

        //assert response status code
        $this->assertEquals(204, $results->getStatusCode());

        //assert that the discount not exists anymore in the database
        $this->assertDatabaseMissing(ConfigService::$tableDiscount, $discount->getArrayCopy());
    }

    public function test_pull_discounts_empty()
    {
        $results = $this->call('GET', '/discounts');

        $this->assertEmpty($results->decodeResponseJson('results'));
        $this->assertEquals(0, $results->decodeResponseJson('total_results'));
    }

    public function test_pull_discounts()
    {
        $page                   = 1;
        $limit                  = 10;
        $totalNumberOfDiscounts = $this->faker->numberBetween(2, 25);
        $discounts              = [];

        for($i = 0; $i < $totalNumberOfDiscounts; $i++)
        {
            $product  = $this->productRepository->create($this->faker->product());
            $discount = $this->discountRepository->create($this->faker->discount());
            $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
                'product_id' => $product['id'],
                'discount_id' => $discount['id']
            ]));
            if($i < $limit)
            {
                $discounts[$i]             = (array)$discount;
                $discounts[$i]['criteria'][] = (array)$discountCriteria;
            }
        }

        $results = $this->call('GET', '/discounts', [
            'page'               => $page,
            'limit'              => $limit,
            'order_by_direction' => 'asc'
        ]);

        $this->assertEquals($discounts, $results->decodeResponseJson('data'));
        $this->assertEquals($totalNumberOfDiscounts, $results->decodeResponseJson('meta')['totalResults']);
    }
}
