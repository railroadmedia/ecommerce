<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\ShippingCostsFactory;
use Railroad\Ecommerce\Factories\ShippingOptionFactory;
use Railroad\Ecommerce\Services\ShippingOptionService;
use PHPUnit\Framework\TestCase;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingOptionServiceTest extends EcommerceTestCase
{

    /**
     * @var ShippingOptionService
     */
    protected $classBeingTested;

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
        $this->classBeingTested = $this->app->make(ShippingOptionService::class);
        $this->shippingOptionFactory = $this->app->make(ShippingOptionFactory::class);
        $this->shippingCostFactory = $this->app->make(ShippingCostsFactory::class);
    }

    public function test_store()
    {
        $shippingOption =
            [
                'country' => $this->faker->country,
                'priority' => 1,
                'active' => 1,
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ];
        $results = $this->classBeingTested->store($shippingOption['country'], $shippingOption['priority'], $shippingOption['active']);

        $this->assertEquals(array_merge(['id' => 1], $shippingOption), $results);
    }

    public function test_update()
    {
        $shippingOption = $this->shippingOptionFactory->store();
        $results = $this->classBeingTested->update($shippingOption['id'], [
            'active' => 0
        ]);

        $shippingOption['active'] = 0;
        $shippingOption['updated_on'] = Carbon::now()->toDateTimeString();

        $this->assertEquals($shippingOption, $results);
    }

    public function test_update_not_existing_shipping_option()
    {
        $results = $this->classBeingTested->update(rand(), []);

        $this->assertNull($results);
    }

    public function test_delete_shipping_option_and_costs()
    {
        $shippingOption = $this->shippingOptionFactory->store();
        $shippingCost = $this->shippingCostFactory->store($shippingOption['id']);

        $results = $this->classBeingTested->delete($shippingOption['id']);

        $this->assertTrue($results);
    }

    public function test_delete()
    {
        $shippingOption = $this->shippingOptionFactory->store();
        $results = $this->classBeingTested->delete($shippingOption['id']);

        $this->assertTrue($results);
    }

    public function test_delete_not_existing_shipping_option()
    {
        $results = $this->classBeingTested->delete(rand());

        $this->assertNull($results);
    }
}
