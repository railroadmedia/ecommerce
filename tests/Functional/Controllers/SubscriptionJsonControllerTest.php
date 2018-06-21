<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

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

    public function setUp()
    {
        parent::setUp();
        $this->subscriptionRepository  = $this->app->make(SubscriptionRepository::class);
        $this->productRepository       = $this->app->make(ProductRepository::class);
        $this->paymentMethodRepository = $this->app->make(PaymentMethodRepository::class);
    }

    public function test_delete()
    {
        $subscription = $this->subscriptionRepository->create($this->faker->subscription());
        $results      = $this->call('DELETE', '/subscription/' . $subscription['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertSoftDeleted(
            ConfigService::$tableSubscription,
            [
                'id' => $subscription['id']
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
                "title"  => "Not found.",
                "detail" => "Delete failed, subscription not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_pull_subscriptions()
    {
        $page            = 1;
        $limit           = 10;
        $nrSubscriptions = 10;
        $product         = $this->productRepository->create($this->faker->product([
            'type' => ConfigService::$typeSubscription
        ]));

        for($i = 0; $i < $nrSubscriptions; $i++)
        {
            $paymentMethod                       = $this->paymentMethodRepository->create($this->faker->paymentMethod());
            $subscription                        = $this->subscriptionRepository->create($this->faker->subscription([
                'product_id'        => $product['id'],
                'payment_method_id' => $paymentMethod['id']
            ]));
            $subscriptions[$i]                   = $subscription;
            $subscriptions[$i]['payment_method'] = (array) $paymentMethod;
            $subscriptions[$i]['product']        = (array) $product;
        }

        $results = $this->call('GET', '/subscriptions',
            [
                'page'  => $page,
                'limit' => $limit,
            ]);

        $this->assertEquals($subscriptions, $results->decodeResponseJson('results'));
    }

    public function test_pull_subscriptions_for_specific_user(){
        $page            = 1;
        $limit           = 10;
        $nrSubscriptions = 10;
        $product         = $this->productRepository->create($this->faker->product([
            'type' => ConfigService::$typeSubscription
        ]));
        $userId = $this->faker->numberBetween();

        for($i = 0; $i < 5; $i++)
        {
            $paymentMethod                       = $this->paymentMethodRepository->create($this->faker->paymentMethod());
            $subscription                        = $this->subscriptionRepository->create($this->faker->subscription([
                'product_id'        => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => rand()
            ]));
        }
        for($i = 0; $i < $nrSubscriptions; $i++)
        {
            $paymentMethod                       = $this->paymentMethodRepository->create($this->faker->paymentMethod());
            $subscription                        = $this->subscriptionRepository->create($this->faker->subscription([
                'product_id'        => $product['id'],
                'payment_method_id' => $paymentMethod['id'],
                'user_id' => $userId
            ]));

            $subscriptions[$i]                   = $subscription;
            $subscriptions[$i]['payment_method'] = (array) $paymentMethod;
            $subscriptions[$i]['product']        = (array) $product;
        }

        $results = $this->call('GET', '/subscriptions',
            [
                'page'  => $page,
                'limit' => $limit,
                'user_id' => $userId
            ]);

        $this->assertEquals($subscriptions, $results->decodeResponseJson('results'));
        $this->assertEquals($nrSubscriptions, $results->decodeResponseJson('total_results'));
    }
}
