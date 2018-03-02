<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Controllers\ShippingCostsWeightRangeController;
use PHPUnit\Framework\TestCase;
use Railroad\Ecommerce\Factories\ShippingCostsFactory;
use Railroad\Ecommerce\Factories\ShippingOptionFactory;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingCostsWeightRangeControllerTest extends EcommerceTestCase
{
    /**
     * @var ShippingOptionFactory
     */
    protected $shippingOptionFactory;

    /**
     * @var ShippingCostsFactory
     */
    protected $shippingCostFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->shippingOptionFactory = $this->app->make(ShippingOptionFactory::class);
        $this->shippingCostFactory = $this->app->make(ShippingCostsFactory::class);
    }

    public function test_store_shipping_option_invalid()
    {
        $randomShoppingOption = rand();
        $results = $this->call('PUT', '/shipping-cost/',
            [
                'shipping_option_id' => $randomShoppingOption,
                'min' => 0,
                'max' => 1,
                'price' => rand()
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "shipping_option_id",
                "detail" => "The selected shipping option id is invalid.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store_incorrect_max_value()
    {
        $shippingOption = $this->shippingOptionFactory->store();
        $minValue = 10;
        $results = $this->call('PUT', '/shipping-cost/',
            [
                'shipping_option_id' => $shippingOption['id'],
                'min' => $minValue,
                'max' => rand(0, 9),
                'price' => rand()
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "max",
                "detail" => "The max must be at least " . $minValue . ".",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store_missing_required_fields()
    {
        $results = $this->call('PUT', '/shipping-cost/');

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
            [
                "source" => "shipping_option_id",
                "detail" => "The shipping option id field is required.",
            ],
            [
                "source" => "min",
                "detail" => "The min field is required.",
            ],
            [
                "source" => "max",
                "detail" => "The max field is required.",
            ],
            [
                "source" => "price",
                "detail" => "The price field is required."
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_update_incorrect_shipping_cost_id()
    {
        $randomId = rand();
        $results = $this->call('PATCH', '/shipping-cost/' . $randomId);
        $this->assertEquals(404, $results->getStatusCode());

        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Update failed, shipping cost weight range not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_update_incorrect_max_value()
    {
        $shippingOption = $this->shippingOptionFactory->store();
        $shippingCost = $this->shippingCostFactory->store($shippingOption['id']);
        $minValue = 10;
        $results = $this->call('PATCH', '/shipping-cost/' . $shippingCost['id'],
            [
                'min' => $minValue,
                'max' => rand(0, 9)
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "max",
                "detail" => "The max must be at least " . $minValue . ".",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_update_shipping_cost()
    {
        $shippingOption = $this->shippingOptionFactory->store();
        $shippingCost = $this->shippingCostFactory->store($shippingOption['id']);

        $newPrice = rand(0,9000);

        $results = $this->call('PATCH', '/shipping-cost/' . $shippingCost['id'],
            [
                'price' => $newPrice
            ]);

        $this->assertEquals(201, $results->getStatusCode());

        $this->assertEquals([
            'id' => $shippingCost['id'],
            'shipping_option_id' => $shippingOption['id'],
            'min' => $shippingCost['min'],
            'max' => $shippingCost['max'],
            'price' => $newPrice,
            'created_on' => $shippingCost['created_on'],
            'updated_on' => Carbon::now()->toDateTimeString()
        ], $results->decodeResponseJson()['results']);
    }

    public function test_delete_incorrect_shipping_id()
    {
        $randomId = rand();
        $results = $this->call('DELETE', '/shipping-cost/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());

        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Delete failed, shipping cost weight range not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_delete_shipping_cost()
    {
        $shippingOption = $this->shippingOptionFactory->store();
        $shippingCost = $this->shippingCostFactory->store($shippingOption['id']);

        $results = $this->call('DELETE', 'shipping-cost/'.$shippingCost['id']);

        $this->assertEquals(204, $results->getStatusCode());
    }
}
