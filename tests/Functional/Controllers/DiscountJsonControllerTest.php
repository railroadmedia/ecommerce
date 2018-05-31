<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\DiscountCriteriaRepository;
use Railroad\Ecommerce\Repositories\DiscountRepository;
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

    public function setUp()
    {
        parent::setUp();
        $this->discountRepository         = $this->app->make(DiscountRepository::class);
        $this->discountCriteriaRepository = $this->app->make(DiscountCriteriaRepository::class);
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
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store()
    {
        $discount = $this->faker->discount();
        $results  = $this->call('PUT', '/discount', $discount);

        //assert the response status code
        $this->assertEquals(200, $results->getStatusCode());

        //assert that the new created discount it's returned in response in JSON format
        $this->assertArraySubset($discount, $results->decodeResponseJson()['results']);

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
            , $results->decodeResponseJson()['error']);
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
            , $results->decodeResponseJson()['results']);
    }

    public function test_delete()
    {
        $discount = $this->discountRepository->create($this->faker->discount());

        $results = $this->call('DELETE', '/discount/' . $discount['id']);

        //assert response status code
        $this->assertEquals(204, $results->getStatusCode());

        //assert that the discount not exists anymore in the database
        $this->assertDatabaseMissing(ConfigService::$tableDiscount, $discount);
    }
}
