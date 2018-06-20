<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class SubscriptionJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    protected $subscriptionRepository;

    public function setUp()
    {
        parent::setUp();
        $this->subscriptionRepository = $this->app->make(SubscriptionRepository::class);
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
}
