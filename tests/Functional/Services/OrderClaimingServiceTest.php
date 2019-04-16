<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Services\OrderClaimingService;
use Railroad\Ecommerce\Services\PurchaserService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class OrderClaimingServiceTest extends EcommerceTestCase
{
    /**
     * @var OrderClaimingService
     */
    protected $orderClaimingService;

    protected function setUp()
    {
        parent::setUp();

        // mocks

        $this->orderClaimingService = app()->make(OrderClaimingService::class);
    }

    public function test_claim_order()
    {
        // WIP
    }
}
