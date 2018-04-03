<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;


use Carbon\Carbon;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaymentJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var PaymentMethodFactory
     */
    protected $paymentMethodFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->paymentMethodFactory = $this->app->make(PaymentMethodFactory::class);
    }

    public function test_user_store_payment()
    {
        $this->createAndLogInNewUser();

        $paymentMethod = $this->paymentMethodFactory->store();
        $due = $this->faker->numberBetween(0,1000);
        $type = $this->faker->randomElement([PaymentService::ORDER_PAYMENT_TYPE, PaymentService::RENEWAL_PAYMENT_TYPE]);
        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'due' => $due,
            'type' => $type
        ]);
        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
            'id' => 1,
            'due' => $due,
            'type' => $type,
            'payment_method_id' => $paymentMethod['id'],
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ], $results->decodeResponseJson()['results']);
    }

    public function test_admin_store_any_payment()
    {
        $this->createAndLoginAdminUser();

        $paymentMethod = rand();
        $due = $this->faker->numberBetween(0,1000);
        $type = $this->faker->randomElement([PaymentService::ORDER_PAYMENT_TYPE, PaymentService::RENEWAL_PAYMENT_TYPE]);
        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod,
            'due' => $due,
            'type' => $type
        ]);
        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
            'id' => 1,
            'due' => $due,
            'type' => $type,
            'payment_method_id' => $paymentMethod,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ], $results->decodeResponseJson()['results']);
    }

    public function test_user_can_not_store_other_payment()
    {
        $this->createAndLogInNewUser();

        $paymentMethod = rand();
        $due = $this->faker->numberBetween(0,1000);
        $type = $this->faker->randomElement([PaymentService::ORDER_PAYMENT_TYPE, PaymentService::RENEWAL_PAYMENT_TYPE]);
        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod,
            'due' => $due,
            'type' => $type
        ]);
        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
        $this->assertArraySubset([], $results->decodeResponseJson()['results']);
    }

    public function test_admin_store_manual_payment()
    {
        $this->createAndLoginAdminUser();

        $paymentMethod = null;
        $due = $this->faker->numberBetween(0,1000);
        $type = $this->faker->randomElement([PaymentService::ORDER_PAYMENT_TYPE, PaymentService::RENEWAL_PAYMENT_TYPE]);
        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod,
            'due' => $due,
            'type' => $type
        ]);
        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
            'id' => 1,
            'due' => $due,
            'type' => $type,
            'payment_method_id' => $paymentMethod,
            'status' => true,
            'external_provider' => PaymentService::MANUAL_PAYMENT_TYPE,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ], $results->decodeResponseJson()['results']);
    }

}
