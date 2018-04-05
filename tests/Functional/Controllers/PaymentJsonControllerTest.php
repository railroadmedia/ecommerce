<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;


use Carbon\Carbon;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Services\PaymentMethodService;
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
            'currency' => $this->faker->currencyCode,
            'due' => $due,
            'type' => $type
        ]);

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
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

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $this->faker->creditCardExpirationDate->format('Y'),
            $this->faker->month,
            $this->faker->word,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            rand(),
            rand(),
            $this->faker->word,
            rand(),
            $this->faker->currencyCode,
            null,
            rand());
        $due = $this->faker->numberBetween(0,1000);
        $type = $this->faker->randomElement([PaymentService::ORDER_PAYMENT_TYPE, PaymentService::RENEWAL_PAYMENT_TYPE]);
        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'currency' => $this->faker->currencyCode,
            'due' => $due,
            'type' => $type
        ]);
        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
            'due' => $due,
            'type' => $type,
            'payment_method_id' => $paymentMethod['id'],
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
            'currency' => $this->faker->currencyCode,
            'type' => $type
        ]);
        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
            'due' => $due,
            'type' => $type,
            'payment_method_id' => $paymentMethod,
            'status' => true,
            'external_provider' => PaymentService::MANUAL_PAYMENT_TYPE,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null
        ], $results->decodeResponseJson()['results']);
    }

    public function test_user_store_payment_invalid_order_id()
    {
        $this->createAndLogInNewUser();

        $paymentMethod = $this->paymentMethodFactory->store();
        $due = $this->faker->numberBetween(0,1000);
        $type = $this->faker->randomElement([PaymentService::ORDER_PAYMENT_TYPE, PaymentService::RENEWAL_PAYMENT_TYPE]);
        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'due' => $due,
            'type' => $type,
            'order_id' => rand()
        ]);

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
            [
                "source" => "order_id",
                "detail" => "The selected order id is invalid.",
            ]]
            , $results->decodeResponseJson()['errors']);
        $this->assertArraySubset([], $results->decodeResponseJson()['results']);
    }

    public function test_user_store_payment_invalid_subscription_id()
    {
        $this->createAndLogInNewUser();

        $paymentMethod = $this->paymentMethodFactory->store();
        $due = $this->faker->numberBetween(0,1000);
        $type = $this->faker->randomElement([PaymentService::ORDER_PAYMENT_TYPE, PaymentService::RENEWAL_PAYMENT_TYPE]);
        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'due' => $due,
            'type' => $type,
            'subscription_id' => rand()
        ]);

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
                [
                    "source" => "subscription_id",
                    "detail" => "The selected subscription id is invalid.",
                ]]
            , $results->decodeResponseJson()['errors']);
        $this->assertArraySubset([], $results->decodeResponseJson()['results']);
    }

}
