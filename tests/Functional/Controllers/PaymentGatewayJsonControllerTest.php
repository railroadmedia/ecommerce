<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;


use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Permissions\Exceptions\NotAllowedException;

class PaymentGatewayJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var PaymentGatewayRepository
     */
    private $paymentGatewayRepository;

    public function setUp()
    {
        parent::setUp();

        $this->paymentGatewayRepository = $this->app->make(PaymentGatewayRepository::class);
    }

    public function test_store_unauthorized_user()
    {
        $this->permissionServiceMock->method('canOrThrow')->willThrowException(
            new NotAllowedException('This action is unauthorized.')
        );

        $type = $this->faker->randomElement(['stripe', 'paypal']);
        $name = $this->faker->text;
        $config = $this->faker->word;
        $brand = $this->faker->word;

        $results = $this->call(
            'PUT',
            '/payment-gateway',
            [
                'type' => $type,
                'name' => $name,
                'config' => $config,
                'brand' => $brand,
            ]
        );

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            ,
            $results->decodeResponseJson()['error']
        );
    }

    public function test_store_validation()
    {
        $this->permissionServiceMock->method('canOrThrow');

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
                ],
            ],
            $results->decodeResponseJson()['errors']
        );
    }

    public function test_store_response()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $type = $this->faker->randomElement(['stripe', 'paypal']);
        $name = $this->faker->text;
        $config = $this->faker->word;
        $brand = $this->faker->word;

        $results = $this->call(
            'PUT',
            '/payment-gateway',
            [
                'type' => $type,
                'name' => $name,
                'config' => $config,
                'brand' => $brand,
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertArraySubset(
            [
                'type' => $type,
                'name' => $name,
                'config' => $config,
                'brand' => $brand,
            ],
            $results->decodeResponseJson()['results']
        );
    }

    public function test_update_unauthorized_user()
    {
        $this->permissionServiceMock->method('canOrThrow')->willThrowException(
            new NotAllowedException('This action is unauthorized.')
        );

        $results = $this->call('PATCH', '/payment-gateway/' . rand());

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            ,
            $results->decodeResponseJson()['error']
        );
    }

    public function test_update_payment_gateway_not_exist()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $randomId = rand();
        $results = $this->call('PATCH', '/payment-gateway/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Update failed, payment gateway not found with id: " . $randomId,
            ]
            ,
            $results->decodeResponseJson()['error']
        );
    }

    public function test_update_payment_gateway_response()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway());
        $newName = $this->faker->text;

        $results = $this->call(
            'PATCH',
            'payment-gateway/' . $paymentGateway['id'],
            [
                'name' => $newName,
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset(
            [
                'id' => $paymentGateway['id'],
                'name' => $newName,
            ],
            $results->decodeResponseJson()['results']
        );

    }

    public function test_delete_payment_gateway_unauthorized_user()
    {
        $this->permissionServiceMock->method('canOrThrow')->willThrowException(
            new NotAllowedException('This action is unauthorized.')
        );

        $results = $this->call('DELETE', '/payment-gateway/' . rand());

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            ,
            $results->decodeResponseJson()['error']
        );
    }

    public function test_delete_payment_gateway_not_exist()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $randomId = rand();
        $results = $this->call('DELETE', '/payment-gateway/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => 'Delete failed, could not find a payment gateway to delete.',
            ]
            ,
            $results->decodeResponseJson()['error']
        );
    }

    public function test_delete_payment_gateway_response()
    {
        $this->permissionServiceMock->method('canOrThrow');

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway());

        $results = $this->call('DELETE', 'payment-gateway/' . $paymentGateway['id']);

        $this->assertEquals(204, $results->getStatusCode());
    }
}
