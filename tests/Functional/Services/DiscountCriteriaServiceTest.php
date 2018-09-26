<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Services\DiscountCriteriaService;

class DiscountCriteriaServiceTest extends EcommerceTestCase
{
    /**
     * @var DiscountCriteriaService
     */
    protected $classBeingTested;

    /**
     * @var UserProductRepository
     */
    protected $userProductRepository;


    public function setUp()
    {
        parent::setUp();

        $this->classBeingTested = $this->app->make(DiscountCriteriaService::class);
        $this->userProductRepository = $this->app->make(UserProductRepository::class);
    }

    public function test_product_own_criteria_not_met()
    {
        $user = $this->createAndLogInNewUser();
        $metCriteria =
            $this->classBeingTested->productOwnRequirement(['product_id' => rand(), 'min' => 1, 'max' => rand()]);

        $this->assertFalse($metCriteria);
    }

    public function test_product_own_criteria()
    {
        $user = $this->createAndLogInNewUser();
        $userProduct = $this->userProductRepository->create(
            $this->faker->userProduct(['user_id' => $user, 'product_id' => rand()])
        );

        $metCriteria = $this->classBeingTested->productOwnRequirement(
            ['product_id' => $userProduct['product_id'], 'min' => 1, 'max' => $userProduct['quantity']]
        );

        $this->assertTrue($metCriteria);
    }

    public function test_product_own_quantity_not_met()
    {
        $user = $this->createAndLogInNewUser();
        $userProduct = $this->userProductRepository->create(
            $this->faker->userProduct(['user_id' => $user, 'product_id' => rand()])
        );

        $metCriteria = $this->classBeingTested->productOwnRequirement(
            [
                'product_id' => $userProduct['product_id'],
                'min' => ($userProduct['quantity'] + 1),
                'max' => ($userProduct['quantity'] + 10),
            ]
        );

        $this->assertFalse($metCriteria);
    }
}
