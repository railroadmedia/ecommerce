<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class AccessCodeControllerTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
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

        $product = $this->fakeProduct([
            'type' => Product::TYPE_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code']
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_user_products',
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
            'ecommerce_access_codes',
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

        $product = $this->fakeProduct([
            'type' => Product::TYPE_SUBSCRIPTION,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$product['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code']
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $userId,
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
                'claimer_id' => $userId,
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_claim_extend_active_subscription()
    {
        $userId  = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => null,
            'user_id' => $userId,
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
            'access_code' => $accessCode['code']
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the subscription data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
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
            'ecommerce_access_codes',
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $userId,
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
        $userId  = $this->createAndLogInNewUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_SUBSCRIPTION,
            'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
            'subscription_interval_count' => 1,
        ]);

        $subscription = $this->fakeSubscription([
            'product_id' => $product['id'],
            'payment_method_id' => null,
            'user_id' => $userId,
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
            'access_code' => $accessCode['code']
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('success', true);

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_user_products',
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
            'ecommerce_access_codes',
            [
                'id' => $accessCode['id'],
                'is_claimed' => true,
                'claimer_id' => $userId,
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }
}
