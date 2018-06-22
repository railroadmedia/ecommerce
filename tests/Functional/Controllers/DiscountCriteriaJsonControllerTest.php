<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class DiscountCriteriaJsonControllerTest extends EcommerceTestCase
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
        $results = $this->call('PUT', '/discount-criteria/' . rand());

        //assert the response status code
        $this->assertEquals(422, $results->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals([
            [
                "source" => "name",
                "detail" => "The name field is required.",
            ],
            [
                "source" => "type",
                "detail" => "The type field is required.",
            ],
            [
                "source" => "product_id",
                "detail" => "The product id field is required."
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store_invalid_discount()
    {
        $product          = $this->productRepository->create($this->faker->product());
        $discountCriteria = $this->faker->discountCriteria([
            'product_id' => $product['id']
        ]);
        $randomId         = $this->faker->numberBetween();

        $results = $this->call('PUT', '/discount-criteria/' . $randomId, $discountCriteria);

        //assert the response status code
        $this->assertEquals(404, $results->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals(
            [
                "title"  => "Not found.",
                "detail" => "Create discount criteria failed, discount not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_store()
    {
        $product          = $this->productRepository->create($this->faker->product());
        $discount         = $this->discountRepository->create($this->faker->discount());
        $discountCriteria = $this->faker->discountCriteria([
            'product_id' => $product['id']
        ]);

        $results = $this->call('PUT', '/discount-criteria/' . $discount['id'], $discountCriteria);

        //assert the response status code
        $this->assertEquals(200, $results->getStatusCode());

        $discountCriteria['discount_id'] = $discount['id'];

        //assert that the new created discount criteria it's returned in response in JSON format
        $this->assertArraySubset($discountCriteria, $results->decodeResponseJson()['results']);

        //assert that the discount criteria exists in the database
        $this->assertDatabaseHas(ConfigService::$tableDiscountCriteria, $discountCriteria);
    }

    public function test_update_inexistent_discount_criteria()
    {
        $randomId = $this->faker->numberBetween();

        $results = $this->call('PATCH', '/discount-criteria/' . $randomId);

        //assert the response status code
        $this->assertEquals(404, $results->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals(
            [
                "title"  => "Not found.",
                "detail" => "Update discount criteria failed, discount criteria not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_update()
    {
        $product          = $this->productRepository->create($this->faker->product());
        $discount         = $this->discountRepository->create($this->faker->discount());
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'product_id'  => $product['id'],
            'discount_id' => $discount['id']
        ]));
        $newName          = $this->faker->word;

        $results = $this->call('PATCH', '/discount-criteria/' . $discountCriteria['id'], [
            'name' => $newName
        ]);

        //assert response status code
        $this->assertEquals(201, $results->getStatusCode());

        //assert the discount criteria it's returned in JSON format
        $this->assertArraySubset(
            [
                'name'  => $newName,
                'type' => $discountCriteria['type'],
                'product_id' => $discountCriteria['product_id'],
                'min' => $discountCriteria['min'],
                'max' => $discountCriteria['max'],
                'discount_id' => $discountCriteria['discount_id'],
                'updated_on' => Carbon::now()->toDateTimeString()
            ]
            , $results->decodeResponseJson()['results']);
    }

    public function test_delete_inexistent_discount_criteria()
    {
        $randomId = $this->faker->numberBetween();

        $results = $this->call('DELETE', '/discount-criteria/' . $randomId);

        //assert the response status code
        $this->assertEquals(404, $results->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals(
            [
                "title"  => "Not found.",
                "detail" => "Delete discount criteria failed, discount criteria not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_delete()
    {
        $product          = $this->productRepository->create($this->faker->product());
        $discount         = $this->discountRepository->create($this->faker->discount());
        $discountCriteria = $this->discountCriteriaRepository->create($this->faker->discountCriteria([
            'product_id'  => $product['id'],
            'discount_id' => $discount['id']
        ]));

        $results = $this->call('DELETE', '/discount-criteria/' . $discountCriteria['id']);

        //assert the response status code
        $this->assertEquals(204, $results->getStatusCode());

        //assert that the discount criteria not exists in the database
        $this->assertDatabaseMissing(ConfigService::$tableDiscountCriteria,[
            'id' => $discountCriteria['id']
        ]);
    }
}
