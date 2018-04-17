<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaymentGatewayJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var PaymentGatewayFactory
     */
    private $paymentGatewayFactory;

    /**
     * @var PaymentMethodFactory
     */
    private $paymentMethodFactory;


    public function setUp()
    {
        parent::setUp();
        $this->paymentGatewayFactory = $this->app->make(PaymentGatewayFactory::class);
        $this->paymentMethodFactory = $this->app->make(PaymentMethodFactory::class);
    }

    public function test_store_unauthorized_user()
    {
        $this->createAndLogInNewUser();

        $results = $this->call('PUT', '/payment-gateway');

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_store_validation()
    {
        $this->createAndLoginAdminUser();
        $results = $this->call('PUT', 'payment-gateway');

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(
            [
                [
                    "source" => "type",
                    "detail" => "The type field is required.",
                ],
                [
                    "source" => "name",
                    "detail" => "The name field is required.",
                ],
                [
                    "source" => "config",
                    "detail" => "The config field is required.",
                ]
            ]
            , $results->decodeResponseJson()['errors']);
    }

    public function test_store_response()
    {
        $this->createAndLoginAdminUser();
        $type = $this->faker->randomElement(['stripe', 'paypal']);
        $name = $this->faker->text;
        $config = $this->faker->word;

        $results = $this->call('PUT', '/payment-gateway',
            [
                'type' => $type,
                'name' => $name,
                'config' => $config
            ]);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertArraySubset([
            'type' => $type,
            'name' => $name,
            'config' => $config
        ], $results->decodeResponseJson()['results']);
    }

    public function test_update_unauthorized_user()
    {
        $this->createAndLogInNewUser();

        $results = $this->call('PATCH', '/payment-gateway/' . rand());

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_update_payment_gateway_not_exist()
    {
        $this->createAndLoginAdminUser();
        $randomId = rand();
        $results = $this->call('PATCH', '/payment-gateway/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Update failed, payment gateway not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_update_payment_gateway_response()
    {
        $this->createAndLoginAdminUser();
        $paymentGateway = $this->paymentGatewayFactory->store();
        $newName = $this->faker->text;

        $results = $this->call('PATCH', 'payment-gateway/' . $paymentGateway['id'],
            [
                'name' => $newName
            ]);

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset([
            'id' => $paymentGateway['id'],
            'name' => $newName
        ], $results->decodeResponseJson()['results']);

    }

    public function test_delete_payment_gateway_unauthorized_user()
    {
        $this->createAndLogInNewUser();
        $randomId = rand();
        $results = $this->call('DELETE', '/payment-gateway/' . $randomId);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_delete_payment_gateway_not_exist()
    {
        $this->createAndLoginAdminUser();
        $randomId = rand();
        $results = $this->call('DELETE', '/payment-gateway/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Delete failed, payment gateway not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_delete_payment_gateway_in_used()
    {
        $this->createAndLoginAdminUser();
        $paymentGateway = $this->paymentGatewayFactory->store();
        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE, $paymentGateway['id']);

        $results = $this->call('DELETE', 'payment-gateway/' . $paymentGateway['id']);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "Delete failed, the payment gateway it's in used.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_delete_payment_gateway_response()
    {
        $this->createAndLoginAdminUser();
        $paymentGateway = $this->paymentGatewayFactory->store();

        $results = $this->call('DELETE', 'payment-gateway/' . $paymentGateway['id']);

        $this->assertEquals(204, $results->getStatusCode());
    }
}
