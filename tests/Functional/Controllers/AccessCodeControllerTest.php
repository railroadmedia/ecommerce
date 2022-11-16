<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Doctrine\Common\Cache\ArrayCache;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Event;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Events\AccessCodeClaimed;
use Railroad\Ecommerce\Services\AccessCodeService;
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

    protected function setUp(): void
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
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'digital_access_type' => Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS,
            'digital_access_time_type' => Product::DIGITAL_ACCESS_TIME_TYPE_ONE_TIME,
            'digital_access_time_interval_type' => Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR,
            'digital_access_time_interval_length' => 1,
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
        $response->assertSessionHas('access-code-claimed-success', true);

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

    public function test_claim_create_user_product_with_expiration_with_context_event()
    {
        Event::fake([AccessCodeClaimed::class]);

        $user  = $this->fakeUser();
        $context = $this->faker->word;

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'digital_access_type' => Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS,
            'digital_access_time_type' => Product::DIGITAL_ACCESS_TIME_TYPE_ONE_TIME,
            'digital_access_time_interval_type' => Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR,
            'digital_access_time_interval_length' => 1,
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
            'context' => $context,
        ]);

        Event::assertDispatched(AccessCodeClaimed::class);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('access-code-claimed-success', true);

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
        Event::fake([AccessCodeClaimed::class]);

        $user  = $this->fakeUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'subscription_interval_type' => null,
            'subscription_interval_count' => null,
            'digital_access_type' => null,
            'digital_access_time_type' => null,
            'digital_access_time_interval_type' => null,
            'digital_access_time_interval_length' => null,
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

        Event::assertDispatched(AccessCodeClaimed::class);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('access-code-claimed-success', true);

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
        Event::fake([AccessCodeClaimed::class]);

        $user  = $this->fakeUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'digital_access_type' => Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS,
            'digital_access_time_type' => Product::DIGITAL_ACCESS_TIME_TYPE_RECURRING,
            'digital_access_time_interval_type' => Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR,
            'digital_access_time_interval_length' => 1,
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

        Event::assertDispatched(AccessCodeClaimed::class);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('access-code-claimed-success', true);

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

    public function test_claim_extend_active_subscription_one_time_product_claim()
    {
        Event::fake([AccessCodeClaimed::class]);

        $user  = $this->fakeUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'digital_access_type' => Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS,
            'digital_access_time_type' => Product::DIGITAL_ACCESS_TIME_TYPE_ONE_TIME,
            'digital_access_time_interval_type' => Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR,
            'digital_access_time_interval_length' => 1,
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

        Event::assertDispatched(AccessCodeClaimed::class);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('access-code-claimed-success', true);

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
        Event::fake([AccessCodeClaimed::class]);

        $user  = $this->fakeUser();

        $product = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'digital_access_type' => Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS,
            'digital_access_time_type' => Product::DIGITAL_ACCESS_TIME_TYPE_ONE_TIME,
            'digital_access_time_interval_type' => Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR,
            'digital_access_time_interval_length' => 1,
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

        Event::assertDispatched(AccessCodeClaimed::class);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('access-code-claimed-success', true);

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

    public function test_user_with_access_only_from_user_product_redeems_another_code()
    {
        $digitalOneTimeDummySku1 = 'digital-one-time-dummy-sku-1';
        $digitalSubscriptionDummySku1 = 'digital-subscription-dummy-sku-1';

        $this->app['config']->set(
            'ecommerce.code_redeem_product_sku_swap',
            [$digitalOneTimeDummySku1 => $digitalSubscriptionDummySku1]
        );

        $this->app['config']->set(
            'ecommerce.membership_product_skus_for_code_redeem',
            [$digitalOneTimeDummySku1, $digitalSubscriptionDummySku1]
        );

        $user = $this->fakeUser();

        $productOneTime = $this->fakeProduct([
            'sku' => $digitalOneTimeDummySku1,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'digital_access_type' => null,
            'digital_access_time_type' => null,
            'digital_access_time_interval_type' => null,
            'digital_access_time_interval_length' => null,
        ]);

        $productSubscription = $this->fakeProduct([
            'sku' => $digitalSubscriptionDummySku1,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'digital_access_type' => Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS,
            'digital_access_time_type' => Product::DIGITAL_ACCESS_TIME_TYPE_ONE_TIME,
            'digital_access_time_interval_type' => Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR,
            'digital_access_time_interval_length' => 1,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$productOneTime['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $this->databaseManager->table('ecommerce_user_products')->insert([
            'user_id' => $user['id'],
            'product_id' => $productSubscription['id'],
            'quantity' => 1,
            'expiration_date' => Carbon::now()->addYear(1)->startOfDay()->toDateTimeString(),
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code'],
            'credentials_type' => 'existing',
            'user_email' => $user['email'],
            'user_password' => $this->faker->word,
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('access-code-claimed-success', true);

        app()->make('EcommerceArrayCache')->flushAll();

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $user['id'],
                'product_id' => $productSubscription['id'],
                'expiration_date' => Carbon::now()
                    ->addYear(2)
                    ->startOfDay()
                    ->toDateTimeString()
            ]
        );

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

    /*
     * This effectively duplicates "test_user_with_access_only_from_user_product_redeems_another_code" thus, could be
     * deleted. I've left it here in the off chance it one day fails uniquely and is then of some use.
     *
     * Jonathan Nov 2020
     */
    public function test_user_with_access_from_code_redeems_another_code()
    {
        Event::fake([AccessCodeClaimed::class]);

        $digitalOneTimeDummySku1 = 'digital-one-time-dummy-sku-1';
        $digitalSubscriptionDummySku1 = 'digital-subscription-dummy-sku-1';

        $this->app['config']->set(
            'ecommerce.code_redeem_product_sku_swap',
            [$digitalOneTimeDummySku1 => $digitalSubscriptionDummySku1]
        );

        $this->app['config']->set(
            'ecommerce.membership_product_skus_for_code_redeem',
            [$digitalOneTimeDummySku1, $digitalSubscriptionDummySku1]
        );

        $user = $this->fakeUser();

        $productOneTime = $this->fakeProduct([
            'sku' => $digitalOneTimeDummySku1,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'digital_access_type' => null,
            'digital_access_time_type' => null,
            'digital_access_time_interval_type' => null,
            'digital_access_time_interval_length' => null,
        ]);

        $productSubscription = $this->fakeProduct([
            'sku' => $digitalSubscriptionDummySku1,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'digital_access_type' => Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS,
            'digital_access_time_type' => Product::DIGITAL_ACCESS_TIME_TYPE_RECURRING,
            'digital_access_time_interval_type' => Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR,
            'digital_access_time_interval_length' => 1,
        ]);

        $accessCodeOne = $this->fakeAccessCode([
            'product_ids' => [$productOneTime['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $accessCodeTwo = $this->fakeAccessCode([
            'product_ids' => [$productOneTime['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        // call code-redeem first time
        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCodeOne['code'],
            'credentials_type' => 'existing',
            'user_email' => $user['email'],
            'user_password' => $this->faker->word,
        ]);

        Event::assertDispatched(AccessCodeClaimed::class);

        $this->assertEquals(302, $response->getStatusCode());

        $response->assertSessionHas('access-code-claimed-success', true);

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $user['id'],
                'product_id' => $productSubscription['id'],
                'expiration_date' => Carbon::now()
                    ->addYear(1)
                    ->startOfDay()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_access_codes',
            [
                'id' => $accessCodeOne['id'],
                'is_claimed' => true,
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );

        app()->make('EcommerceArrayCache')->flushAll();

        // call code-redeem a second time

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCodeTwo['code'],
            'credentials_type' => 'existing',
            'user_email' => $user['email'],
            'user_password' => $this->faker->word,
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        $response->assertSessionHas('access-code-claimed-success', true);

        app()->make('EcommerceArrayCache')->flushAll();

        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $user['id'],
                'product_id' => $productSubscription['id'],
                'expiration_date' => Carbon::now()
                    ->addYear(2)
                    ->startOfDay()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_access_codes',
            [
                'id' => $accessCodeTwo['id'],
                'is_claimed' => true,
                'claimer_id' => $user['id'],
                'claimed_on' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_user_with_multiple_duplicated_access_redeems_another_code()
    {
        $digitalOneTimeDummySku1 = 'digital-one-time-dummy-sku-1';
        $digitalSubscriptionDummySku1 = 'digital-subscription-dummy-sku-1';

        $this->app['config']->set(
            'ecommerce.code_redeem_product_sku_swap',
            [$digitalOneTimeDummySku1 => $digitalSubscriptionDummySku1]
        );

        $this->app['config']->set(
            'ecommerce.membership_product_skus_for_code_redeem',
            [$digitalOneTimeDummySku1, $digitalSubscriptionDummySku1]
        );

        $user = $this->fakeUser();

        $productOneTime = $this->fakeProduct([
            'sku' => $digitalOneTimeDummySku1,
            'type' => Product::TYPE_DIGITAL_ONE_TIME,
            'digital_access_type' => null,
            'digital_access_time_type' => null,
            'digital_access_time_interval_type' => null,
            'digital_access_time_interval_length' => null,
        ]);

        $productSubscription = $this->fakeProduct([
            'sku' => $digitalSubscriptionDummySku1,
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'digital_access_type' => Product::DIGITAL_ACCESS_TYPE_ALL_CONTENT_ACCESS,
            'digital_access_time_type' => Product::DIGITAL_ACCESS_TIME_TYPE_ONE_TIME,
            'digital_access_time_interval_type' => Product::DIGITAL_ACCESS_TIME_INTERVAL_TYPE_YEAR,
            'digital_access_time_interval_length' => 1,
        ]);

        $accessCode = $this->fakeAccessCode([
            'product_ids' => [$productOneTime['id']],
            'is_claimed' => 0,
            'claimed_on' => null
        ]);

        $earlierDate = Carbon::now()->addMonths(rand(2,6))->startOfDay()->toDateTimeString();
        $laterDate = Carbon::now()->addYear(1)->startOfDay()->toDateTimeString();

        $this->databaseManager->table('ecommerce_user_products')->insert([
            'user_id' => $user['id'],
            'product_id' => $productSubscription['id'],
            'quantity' => 1,
            'expiration_date' => $earlierDate,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);

        $this->databaseManager->table('ecommerce_user_products')->insert([
            'user_id' => $user['id'],
            'product_id' => $productSubscription['id'],
            'quantity' => 1,
            'expiration_date' => $laterDate,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ]);

        $results = $this->databaseManager->table('ecommerce_user_products')->get();

        $idToExpectUpdated = null;

        foreach($results as $result){
            if($result->expiration_date === $laterDate){
                $idToExpectUpdated = $result->id;
            }
        }

        if(empty($idToExpectUpdated)){
            $this->fail('$idToExpectUpdated is not set');
        }

        $response = $this->call('POST', '/access-codes/redeem', [
            'access_code' => $accessCode['code'],
            'credentials_type' => 'existing',
            'user_email' => $user['email'],
            'user_password' => $this->faker->word,
        ]);

        $this->assertEquals(302, $response->getStatusCode());

        // assert the session has the success message
        $response->assertSessionHas('access-code-claimed-success', true);

        app()->make('EcommerceArrayCache')->flushAll();

        // assert the user product data was saved in the db
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'id' => $idToExpectUpdated,
                'user_id' => $user['id'],
                'product_id' => $productSubscription['id'],
                'expiration_date' => Carbon::now()
                    ->addYear(2)
                    ->startOfDay()
                    ->toDateTimeString()
            ]
        );

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