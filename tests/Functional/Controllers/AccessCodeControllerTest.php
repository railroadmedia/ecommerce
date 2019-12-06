<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class AccessCodeControllerTest extends EcommerceTestCase
{
    use WithoutMiddleware;

    /**
     * @var MockObject|AuthManager
     */
    protected $authManagerMock;

    /**
     * @var MockObject|SessionGuard
     */
    protected $sessionGuardMock;

    protected function setUp()
    {
        parent::setUp();

        $this->authManagerMock =
            $this->getMockBuilder(AuthManager::class)
                ->disableOriginalConstructor()
                ->setMethods(['guard'])
                ->getMock();

        $this->sessionGuardMock =
            $this->getMockBuilder(SessionGuard::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->authManagerMock->method('guard')
            ->willReturn($this->sessionGuardMock);

        $this->app->instance(Factory::class, $this->authManagerMock);

        $this->sessionGuardMock->method('loginUsingId')
            ->willReturn(true);
    }

    public function test_claim_validation()
    {
        $response = $this->call('POST', '/access-codes/redeem', []);

        $response->assertSessionHasErrors(
            ['access_code', 'credentials_type']
        );
    }

    public function test_claim_validation_existing()
    {
        $response = $this->call(
            'POST',
            '/access-codes/redeem',
            [
                'access_code' => $this->faker->shuffleString($this->faker->bothify('???###???###???###???###')),
                'credentials_type' => 'existing'
            ]
        );

        $response->assertSessionHasErrors(
            ['user_email', 'user_password']
        );
    }

    public function test_claim_validation_new()
    {
        $response = $this->call(
            'POST',
            '/access-codes/redeem',
            [
                'access_code' => $this->faker->shuffleString($this->faker->bothify('???###???###???###???###')),
                'credentials_type' => 'new'
            ]
        );

        $response->assertSessionHasErrors(
            ['email', 'password']
        );
    }

    public function test_claim_create_user_product_with_expiration()
    {
        $user  = $this->fakeUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code'],
            'credentials_type' => 'existing',
            'user_email' => $user['email'],
            'user_password' => $this->faker->word,
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_user_products',
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
            'ecommerce_access_codes',
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_claim_create_user_product_without_expiration()
    {
        $user  = $this->fakeUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code'],
            'credentials_type' => 'existing',
            'user_email' => $user['email'],
            'user_password' => $this->faker->word,
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $user['id'],
                'product_id' => $product['id'],
                'expiration_date' => null
            ]
        );

        // assert access code was set as claimed
        $this->assertDatabaseHas(
            'ecommerce_access_codes',
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_claim_extend_active_subscription()
    {
        $user  = $this->fakeUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => null,
            'user_id' => $user['id'],
            'paid_until' => Carbon::now()
                ->addMonths(2)
                ->startOfDay()
                ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code'],
            'credentials_type' => 'existing',
            'user_email' => $user['email'],
            'user_password' => $this->faker->word,
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the subscription data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'user_id' => $user['id'],
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
            'ecommerce_access_codes',
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );

        // assert subscription access code was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_subscription_access_codes',
            [
                'subscription_id' => $subscription['id'],
                'access_code_id' => $accessCode['id'],
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_claim_create_user_product_expired_subscription()
    {
        $user  = $this->fakeUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => null,
            'user_id' => $user['id'],
            'paid_until' => Carbon::now()
                ->subMonths(2)
                ->startOfDay()
                ->toDateTimeString(),
            'is_active' => 1,
            'interval_count' => 1,
            'interval_type' => config('ecommerce.interval_type_yearly'),
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code'],
            'credentials_type' => 'existing',
            'user_email' => $user['email'],
            'user_password' => $this->faker->word,
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_user_products',
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
            'ecommerce_access_codes',
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }
}
