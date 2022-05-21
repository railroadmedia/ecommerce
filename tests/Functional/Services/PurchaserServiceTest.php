<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Services\PurchaserService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PurchaserServiceTest extends EcommerceTestCase
{
    /**
     * @var PurchaserService
     */
    protected $purchaserService;

    /**
     * @var MockObject
     */
    protected $authManagerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authManagerMock =
            $this->getMockBuilder(AuthManager::class)
                ->disableOriginalConstructor()
                ->setMethods(['loginUsingId'])
                ->getMock();

        // for some reason this is auto loaded using the factory name
        $this->app->instance(Factory::class, $this->authManagerMock);

        $this->purchaserService = app()->make(PurchaserService::class);
    }

    public function test_purchaser_new_user()
    {
        $email = $this->faker->email;
        $password = rand();

        $purchaser = new Purchaser();

        $purchaser->setEmail($email);
        $purchaser->setRawPassword($password);
        $purchaser->setBrand(rand());
        $purchaser->setType(Purchaser::USER_TYPE);

        $this->authManagerMock->method('loginUsingId')->willReturn(true);

        $this->purchaserService->persist($purchaser);

        $this->assertEquals($purchaser->getId(), 1);

        $this->assertDatabaseHas('users', ['email' => $email]);
    }

    public function test_purchaser_new_user_without_login()
    {
        $email = $this->faker->email;
        $password = rand();

        $purchaser = new Purchaser();

        $purchaser->setEmail($email);
        $purchaser->setRawPassword($password);
        $purchaser->setBrand(rand());
        $purchaser->setType(Purchaser::USER_TYPE);

        $this->authManagerMock->expects($this->never())->method('loginUsingId');

        $this->purchaserService->persist($purchaser, false);

        $this->assertEquals($purchaser->getId(), 1);

        $this->assertDatabaseHas('users', ['email' => $email]);
    }

    public function test_new_customer()
    {
        $email = $this->faker->email;
        $purchaser = new Purchaser();

        $purchaser->setEmail($email);
        $purchaser->setBrand(rand());
        $purchaser->setType(Purchaser::CUSTOMER_TYPE);

        $this->purchaserService->persist($purchaser);

        $this->assertEquals($purchaser->getId(), 1);

        $this->assertDatabaseHas('ecommerce_customers', ['email' => $email]);
    }
}
