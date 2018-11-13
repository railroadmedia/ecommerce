<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class AccessCodeControllerTest extends EcommerceTestCase
{
    /**
     * @var AccessCodeRepository
     */
    protected $accessCodeRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->accessCodeRepository = $this->app->make(AccessCodeRepository::class);
        $this->productRepository = $this->app->make(ProductRepository::class);
        $this->subscriptionRepository = $this->app->make(SubscriptionRepository::class);
    }

    public function test_claim_validation()
    {
        $response = $this->call('POST', '/access-codes/redeem', []);

        $response->assertSessionHasErrors(
            ['access_code', 'email', 'password']
        );
    }

    public function test_claim_create_user_product_with_expiration()
    {
        $userId  = $this->createAndLogInNewUser();

        $product = $this->productRepository->create(
            $this->faker->product([
                'type' => ConfigService::$typeSubscription,
                'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                'subscription_interval_count' => 1,
            ])
        );

        $accessCode = $this->accessCodeRepository->create(
            $this->faker->accessCode([
                'product_ids' => [$product['id']],
                'is_claimed' => 0,
                'claimed_on' => null
            ])
        );

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code']
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString()
            ]
        );

        // assert access code was set as claimed
        $this->assertDatabaseHas(
            ConfigService::$tableAccessCode,
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $userId,
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_claim_create_user_product_without_expiration()
    {
        $userId  = $this->createAndLogInNewUser();

        $product = $this->productRepository->create(
            $this->faker->product([
                'type' => ConfigService::$typeSubscription,
                'subscription_interval_type' => null,
                'subscription_interval_count' => null,
            ])
        );

        $accessCode = $this->accessCodeRepository->create(
            $this->faker->accessCode([
                'product_ids' => [$product['id']],
                'is_claimed' => 0,
                'claimed_on' => null
            ])
        );

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code']
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'expiration_date' => null
            ]
        );

        // assert access code was set as claimed
        $this->assertDatabaseHas(
            ConfigService::$tableAccessCode,
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $userId,
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_claim_extend_active_subscription()
    {
        $userId  = $this->createAndLogInNewUser();

        $product = $this->productRepository->create(
            $this->faker->product([
                'type' => ConfigService::$typeSubscription,
                'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                'subscription_interval_count' => 1,
            ])
        );

        $subscription = $this->subscriptionRepository->create(
            $this->faker->subscription([
                'product_id' => $product['id'],
                'payment_method_id' => null,
                'user_id' => $userId,
                'paid_until' => Carbon::now()
                    ->addMonths(2)
                    ->startOfDay()
                    ->toDateTimeString(),
                'is_active' => 1,
                'interval_count' => 1,
                'interval_type' => ConfigService::$intervalTypeYearly,
            ])
        );

        $accessCode = $this->accessCodeRepository->create(
            $this->faker->accessCode([
                'product_ids' => [$product['id']],
                'is_claimed' => 0,
                'claimed_on' => null
            ])
        );

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code']
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the subscription data was saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'paid_until' => Carbon::now()
                    ->addYear(1)
                    ->addMonths(2)
                    ->startOfDay()
                    ->toDateTimeString()
            ]
        );

        // assert access code was set as claimed
        $this->assertDatabaseHas(
            ConfigService::$tableAccessCode,
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $userId,
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );

        // assert subscription access code was saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tableSubscriptionAccessCode,
            [
                'subscription_id' => $subscription['id'],
                'access_code_id' => $accessCode['id'],
                'created_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_claim_create_user_product_expired_subscription()
    {
        $userId  = $this->createAndLogInNewUser();

        $product = $this->productRepository->create(
            $this->faker->product([
                'type' => ConfigService::$typeSubscription,
                'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                'subscription_interval_count' => 1,
            ])
        );

        $subscription = $this->subscriptionRepository->create(
            $this->faker->subscription([
                'product_id' => $product['id'],
                'payment_method_id' => null,
                'user_id' => $userId,
                'paid_until' => Carbon::now()
                    ->subMonths(2)
                    ->startOfDay()
                    ->toDateTimeString(),
                'is_active' => 1,
                'interval_count' => 1,
                'interval_type' => ConfigService::$intervalTypeYearly,
            ])
        );

        $accessCode = $this->accessCodeRepository->create(
            $this->faker->accessCode([
                'product_ids' => [$product['id']],
                'is_claimed' => 0,
                'claimed_on' => null
            ])
        );

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code']
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString()
            ]
        );

        // assert access code was set as claimed
        $this->assertDatabaseHas(
            ConfigService::$tableAccessCode,
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $userId,
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }
}
