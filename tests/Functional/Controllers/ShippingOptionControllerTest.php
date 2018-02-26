<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Controllers\ShippingOptionController;
use PHPUnit\Framework\TestCase;
use Railroad\Ecommerce\Factories\ShippingOptionFactory;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingOptionControllerTest extends EcommerceTestCase
{

    /**
     * @var ShippingOptionFactory
     */
    protected $shippingOptionFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->shippingOptionFactory = $this->app->make(ShippingOptionFactory::class);
    }

    public function test_store()
    {
        $shippingOption = [
            'country' => $this->faker->country,
            'priority' => 1,
            'active' => 1
        ];
        $results = $this->call('PUT', '/shipping-option/', $shippingOption);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals(array_merge(
            [
                'id' => 1,
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
            , $shippingOption), $results->decodeResponseJson()['results']);
    }

    public function test_store_validation_errors()
    {
        $shippingOption = [
            'country' => $this->faker->country,
            'priority' => 1,
            'active' => 1
        ];
        $results = $this->call('PUT', '/shipping-option/');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "country",
                "detail" => "The country field is required.",
            ],
            [
                "source" => "priority",
                "detail" => "The priority field is required.",
            ],
            [
                "source" => "active",
                "detail" => "The active field is required."
            ]
        ], $results->decodeResponseJson()['errors']);
    }
}
