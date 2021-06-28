<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\AccessCodeService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class AccessCodeServiceTest extends EcommerceTestCase
{
    /**
     * @var AccessCodeService
     */
    public $accessCodeService;

    protected function setUp()
    {
        parent::setUp();

        $this->accessCodeService = $this->app->make(AccessCodeService::class);
    }

    public function test_generate_new_access_code()
    {
        $product = $this->fakeProduct(
            [
                'active' => 1,
                'stock' => $this->faker->numberBetween(20, 100),
                'price' => $this->faker->randomFloat(2, 15, 20),
            ]
        );
        $brand = 'drumeo';

        $accessCode = $this->accessCodeService->generateAccessCode([$product['id']], $brand);

        $this->assertEquals($accessCode->getBrand(), $brand);
        $this->assertEquals($accessCode->getProductIds(), [$product['id']]);
        $this->assertEquals($accessCode->getIsClaimed(), false);
        $this->assertEquals($accessCode->getClaimer(), null);
        $this->assertEquals($accessCode->getClaimedOn(), null);

        $this->assertDatabaseHas(
            'ecommerce_access_codes',
            [
                'code' => $accessCode->getCode(),
                'product_ids' => serialize($accessCode->getProductIds()),
                'is_claimed' => false,
                'claimer_id' => null,
                'claimed_on' => null,
                'brand' => $brand,
                'note' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );
    }
}
