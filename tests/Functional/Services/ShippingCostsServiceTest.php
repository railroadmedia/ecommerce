<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\ShippingCostsFactory;
use Railroad\Ecommerce\Factories\ShippingOptionFactory;
use Railroad\Ecommerce\Services\ShippingCostsService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class ShippingCostsServiceTest extends EcommerceTestCase
{

    /**
     * @var ShippingCostsService
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
        $this->classBeingTested = $this->app->make(ShippingCostsService::class);
        $this->shippingOptionFactory = $this->app->make(ShippingOptionFactory::class);
        $this->shippingCostFactory = $this->app->make(ShippingCostsFactory::class);
    }

    public function test_store()
    {
        $shippingOption = $this->shippingOptionFactory->store();
        $shippingCosts = [
            'shipping_option_id' => $shippingOption['id'],
            'min' => rand(0, 5),
            'max' => rand(6, 10),
            'price' => rand(1, 1000)
        ];

        $results = $this->classBeingTested->store($shippingCosts['shipping_option_id'], $shippingCosts['min'], $shippingCosts['max'], $shippingCosts['price']);

        $this->assertEquals(array_merge(['id' => 1,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null], $shippingCosts), $results);
    }

    public function test_store_not_existing_shipping_option()
    {
        $shippingCosts = [
            'min' => rand(0, 5),
            'max' => rand(6, 10),
            'price' => rand(1, 1000)
        ];

        $results = $this->classBeingTested->store(rand(), $shippingCosts['min'], $shippingCosts['max'], $shippingCosts['price']);

        $this->assertNull($results);
    }

    public function test_update_not_existing_shipping_costs()
    {
        $results = $this->classBeingTested->update(rand(), []);

        $this->assertNull($results);
    }

    public function test_update()
    {
        $shippingOption = $this->shippingOptionFactory->store();

        $shippingCost = $this->shippingCostFactory->store($shippingOption['id']);

        $newPrice = rand();

        $results = $this->classBeingTested->update($shippingCost['id'], [
            'price' => $newPrice
        ]);

        $shippingCost['price'] = $newPrice;
        $shippingCost['updated_on'] = Carbon::now()->toDateTimeString();
        $this->assertEquals(
            $shippingCost,
            $results);
    }

    public function test_get_by_id_not_exist()
    {
        $this->assertNull($this->classBeingTested->getById(rand()));
    }

    public function test_get_by_id()
    {
        $shippingOption = $this->shippingOptionFactory->store();

        $shippingCost = $this->shippingCostFactory->store($shippingOption['id']);

        $this->assertEquals($shippingCost, $this->classBeingTested->getById($shippingCost['id']));
    }

    public function test_delete_not_exist()
    {
        $this->assertNull($this->classBeingTested->delete(rand()));
    }

    public function test_delete()
    {
        $shippingOption = $this->shippingOptionFactory->store();

        $shippingCost = $this->shippingCostFactory->store($shippingOption['id']);

        $this->assertTrue($this->classBeingTested->delete($shippingCost['id']));
    }
}
