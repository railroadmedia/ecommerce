<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\UserProductService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class SubscriptionJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentMethodRepository
     */
    protected $paymentMethodRepository;

    /**
     * @var CreditCardRepository
     */
    protected $creditCardRepository;

    /**
     * @var UserProductRepository
     */
    protected $userProductRepository;

    public function setUp()
    {
        parent::setUp();
        $this->subscriptionRepository = $this->app->make(SubscriptionRepository::class);
        $this->productRepository = $this->app->make(ProductRepository::class);
        $this->paymentMethodRepository = $this->app->make(PaymentMethodRepository::class);
        $this->creditCardRepository = $this->app->make(CreditCardRepository::class);
        $this->userProductRepository = $this->app->make(UserProductRepository::class);
    }

    public function test_delete()
    {
        $subscription = $this->subscriptionRepository->create($this->faker->subscription());

        $results = $this->call('DELETE', '/subscription/' . $subscription['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertSoftDeleted(
            ConfigService::$tableSubscription,
            [
                'id' => $subscription['id'],
            ]
        );
    }

    public function test_delete_not_existing_subscription()

    {
        $randomId = $this->faker->randomNumber();

        $results = $this->call('DELETE', '/subscription/' . $randomId);

        //assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        //assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Delete failed, subscription not found with id: " . $randomId,
            ],
            $results->decodeResponseJson('meta')['errors']
        );
    }

    public function test_pull_subscriptions()
    {
        $page = 1;
        $limit = 10;
        $nrSubscriptions = 10;
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'type' => ConfigService::$typeSubscription,
                ]
            )
        );
        unset($product['discounts']);

        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod());
            $subscription = $this->subscriptionRepository->create(
                $this->faker->subscription(
                    [
                        'product_id' => $product['id'],
                        'payment_method_id' => $paymentMethod['id'],
                    ]
                )
            );
            $subscriptions[$i] = $subscription->getArrayCopy();
            $subscriptions[$i]['payment_method'] = (array)$paymentMethod;
            $subscriptions[$i]['product'] = (array)$product;
        }

        $results = $this->call(
            'GET',
            '/subscriptions',
            [
                'page' => $page,
                'limit' => $limit,
            ]
        );

        $this->assertEquals($subscriptions, $results->decodeResponseJson('data'));
    }

    public function test_pull_subscriptions_for_specific_user()
    {
        $page = 1;
        $limit = 10;
        $nrSubscriptions = 10;
        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'type' => ConfigService::$typeSubscription,
                ]
            )
        );
        unset($product['discounts']);
        $userId = $this->faker->numberBetween();

        for ($i = 0; $i < 5; $i++) {
            $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod());
            $subscription = $this->subscriptionRepository->create(
                $this->faker->subscription(
                    [
                        'product_id' => $product['id'],
                        'payment_method_id' => $paymentMethod['id'],
                        'user_id' => rand(),
                    ]
                )
            );
        }
        for ($i = 0; $i < $nrSubscriptions; $i++) {
            $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod());
            $subscription = $this->subscriptionRepository->create(
                $this->faker->subscription(
                    [
                        'product_id' => $product['id'],
                        'payment_method_id' => $paymentMethod['id'],
                        'user_id' => $userId,
                    ]
                )
            );

            $subscriptions[$i] = $subscription->getArrayCopy();
            $subscriptions[$i]['payment_method'] = (array)$paymentMethod;
            $subscriptions[$i]['product'] = (array)$product;
        }

        $results = $this->call(
            'GET',
            '/subscriptions',
            [
                'page' => $page,
                'limit' => $limit,
                'user_id' => $userId,
            ]
        );

        $this->assertEquals($subscriptions, $results->decodeResponseJson('data'));
        $this->assertEquals($nrSubscriptions, $results->decodeResponseJson('meta')['totalResults']);
    }

    public function test_update_not_existing_subscription()
    {
        $results = $this->call('PATCH', '/subscription/' . rand());

        $this->assertEquals(404, $results->getStatusCode());
    }

    public function test_update_subscription()
    {
        $subscription = $this->subscriptionRepository->create($this->faker->subscription());
        $newPrice = $this->faker->numberBetween();

        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscription['id'],
            [
                'total_price_per_payment' => $newPrice,
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals(
            array_merge(
                $subscription->getArrayCopy(),
                [
                    'total_price_per_payment' => $newPrice,
                    'updated_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            ),
            $results->decodeResponseJson('data')[0]
        );

        unset($subscription['payment_method']);
        unset($subscription['product']);

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            array_merge(
                $subscription->getArrayCopy(),
                [
                    'total_price_per_payment' => $newPrice,
                    'updated_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );
    }

    public function test_cancel_subscription()
    {
        $subscription = $this->subscriptionRepository->create($this->faker->subscription());
        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscription['id'],
            [
                'is_active' => false,
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals(
            array_merge(
                $subscription->getArrayCopy(),
                [
                    'is_active' => 0,
                    'canceled_on' => Carbon::now()
                        ->toDateTimeString(),
                    'updated_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            ),
            $results->decodeResponseJson('data')[0]
        );

        unset($subscription['payment_method']);
        unset($subscription['product']);

        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            array_merge(
                $subscription->getArrayCopy(),
                [
                    'is_active' => 0,
                    'canceled_on' => Carbon::now()
                        ->toDateTimeString(),
                    'updated_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );
    }

    public function test_update_subscription_validation()
    {
        $subscription = $this->subscriptionRepository->create($this->faker->subscription());
        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscription['id'],
            [
                'payment_method_id' => rand(),
                'total_cycles_due' => -2,
                'interval_type' => $this->faker->word,
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(3, count($results->decodeResponseJson('meta')['errors']));
    }

    public function test_update_subscription_date()
    {
        $subscription = $this->subscriptionRepository->create($this->faker->subscription());
        $results = $this->call(
            'PATCH',
            '/subscription/' . $subscription['id'],
            [
                'paid_until' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals(
            Carbon::now()
                ->toDateTimeString(),
            $results->decodeResponseJson('data')[0]['paid_until']
        );
    }

    public function test_renew_subscription()
    {
        $userId = $this->createAndLogInNewUser();

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn(new Charge());

        $product = $this->productRepository->create(
            $this->faker->product(
                [
                    'type' => ConfigService::$typeSubscription,
                    'subscription_interval_type' => ConfigService::$intervalTypeYearly,
                    'subscription_interval_count' => 1
                ]
            )
        );
        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    'method_id' => $creditCard['id'],
                ]
            )
        );
        $subscription = $this->subscriptionRepository->create(
            $this->faker->subscription(
                [
                    'product_id' => $product['id'],
                    'payment_method_id' => $paymentMethod['id'],
                    'user_id' => $userId,
                    'paid_until' => Carbon::now()->subDay(1)->toDateTimeString(),
                    'is_active' => 1,
                    'interval_count' => 1,
                    'interval_type' => ConfigService::$intervalTypeYearly
                ]
            )
        );

        $userProduct = $this->userProductRepository->create($this->faker->userProduct([
            'user_id' => $userId,
            'product_id' => $product['id'],
            'quantity' => 1,

        ]));
        $results = $this->call('POST', '/subscription-renew/' . $subscription['id']);

        $this->assertEquals(201, $results->getStatusCode());

        $this->assertDatabaseHas(ConfigService::$tableUserProduct,[
            'user_id' => $userId,
            'product_id' => $product['id'],
            'quantity' => 1,
            'expiration_date' => Carbon::now()->addYear(1)->startOfDay()->toDateTimeString()
        ]);

        $this->assertDatabaseHas(ConfigService::$tableSubscription,[
            'id' => $subscription['id'],
            'is_active' => 1,
            'paid_until' => Carbon::now()->addYear(1)->startOfDay()->toDateTimeString()
        ]);
    }
}
