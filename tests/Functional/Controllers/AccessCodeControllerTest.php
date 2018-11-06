<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\AccessCodeRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Usora\Repositories\UserRepository;

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

    /**
     * @var UserRepository
     */
    protected $userRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->accessCodeRepository = $this->app->make(AccessCodeRepository::class);
        $this->productRepository = $this->app->make(ProductRepository::class);
        $this->subscriptionRepository = $this->app->make(SubscriptionRepository::class);
        $this->userRepository = $this->app->make(UserRepository::class);
    }

    public function test_claim_validation()
    {
        $response = $this->call('POST', '/access-codes/redeem', []);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals([
            [
                'source' => 'access_code',
                'detail' => 'The access code field is required.',
            ],
            [
                'source' => 'email',
                'detail' => 'The email field is required.',
            ],
            [
                'source' => 'password',
                'detail' => 'The password field is required.',
            ],
        ], $response->decodeResponseJson('meta')['errors']);
    }

    public function test_claim_create_user_product()
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

    public function test_claim_for_user_email_not_found()
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

        $email = $this->faker->email;

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code'],
            'claim_for_user_email' => $email
        ]);

        //assert the response status code
        $this->assertEquals(404, $response->getStatusCode());

        //assert that all the validation errors are returned
        $this->assertEquals(
            [
                'title' => 'Not found.',
                'detail' => 'Claim failed, user not found with email: ' . $email,
            ],
            $response->decodeResponseJson('meta')['errors']
        );
    }

    public function test_admin_claim_for_user_email()
    {
        $adminId  = $this->createAndLogInNewUser();

        $email = $this->faker->email;

        $user = $this->userRepository->create([
            'email' => $email,
            'password' => $this->faker->password,
            'display_name' => $this->faker->name
        ]);

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
            'access_code' => $accessCode['code'],
            'claim_for_user_email' => $email
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tableUserProduct,
            [
                'user_id' => $user['id'],
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
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_release_validation()
    {
        $response = $this->call('POST', '/access-codes/release', []);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals([
            [
                'source' => 'access_code_id',
                'detail' => 'The access code id field is required.',
            ]
        ], $response->decodeResponseJson('meta')['errors']);
    }

    public function test_release_validation_unclaimed()
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

        $response = $this->call('POST', '/access-codes/release', [
            'access_code_id' => $accessCode['id']
        ]);

        //assert the response status code
        $this->assertEquals(422, $response->getStatusCode());

        // assert that all the validation errors are returned
        $this->assertEquals([
            [
                'source' => 'access_code_id',
                'detail' => 'The selected access code id is invalid.',
            ]
        ], $response->decodeResponseJson('meta')['errors']);
    }

    public function test_release()
    {
        $userId  = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

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
                'is_claimed' => 1,
                'claimed_on' => Carbon::now()->toDateTimeString()
            ])
        );

        $response = $this->call('POST', '/access-codes/release', [
            'access_code_id' => $accessCode['id']
        ]);

        //assert the response status code
        $this->assertEquals(302, $response->getStatusCode());

        // assert access code was set as claimed
        $this->assertDatabaseHas(
            ConfigService::$tableAccessCode,
            [
                'id' => $accessCode['id'],
                'is_claimed' => false,
                'claimer_id' => null,
                'claimed_on' => null
            ]
        );
    }
}
