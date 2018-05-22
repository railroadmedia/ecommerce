<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Permissions\Services\PermissionService;

class PaymentJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var PaymentGatewayRepository
     */
    private $paymentGatewayRepository;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * @var \Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository
     */
    private $userPaymentMethodRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->paymentGatewayRepository    = $this->app->make(PaymentGatewayRepository::class);
        $this->paymentMethodRepository     = $this->app->make(PaymentMethodRepository::class);
        $this->creditCardRepository        = $this->app->make(CreditCardRepository::class);
        $this->permissionService           = $this->app->make(PermissionService::class);
        $this->userPaymentMethodRepository = $this->app->make(UserPaymentMethodsRepository::class);
    }

    public function test_user_store_payment()
    {
        $userId = $this->createAndLogInNewUser();

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway([
            'brand'  => ConfigService::$brand,
            'config' => 'stripe_1'
        ]));
        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod     = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));
        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]));

        $due = $this->faker->numberBetween(0, 1000);

        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'currency'          => 'cad',
            'due'               => $due
        ]);

        //assert response status code and content
        $this->assertEquals(200, $results->getStatusCode());
        $this->assertArraySubset([
            'due'               => $due,
            'type'              => PaymentService::ORDER_PAYMENT_TYPE,
            'payment_method_id' => $paymentMethod['id'],
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $results->decodeResponseJson()['results']);

        //assert payment exists in the db
        $this->assertDatabaseHas(ConfigService::$tablePayment,
            [
                'due'               => $due,
                'type'              => PaymentService::ORDER_PAYMENT_TYPE,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency'          => 'cad',
                'status'            => 1,
                'message'           => null,
                'created_on'        => Carbon::now()->toDateTimeString(),
                'updated_on'        => null
            ]);
    }

    public function test_admin_store_any_payment()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway([
            'brand'  => ConfigService::$brand,
            'config' => 'stripe_1'
        ]));
        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));
        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => rand(),
            'payment_method_id' => $paymentMethod['id']
        ]));

        $due     = $this->faker->numberBetween(0, 1000);
        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'due'               => $due
        ]);

        //assert response
        $this->assertEquals(200, $results->getStatusCode());
        $this->assertArraySubset([
            'due'               => $due,
            'type'              => PaymentService::ORDER_PAYMENT_TYPE,
            'currency'          => 'CAD',
            'payment_method_id' => $paymentMethod['id'],
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $results->decodeResponseJson()['results']);

        //assert payment exists in the db
        $this->assertDatabaseHas(ConfigService::$tablePayment,
            [
                'due'               => $due,
                'type'              => PaymentService::ORDER_PAYMENT_TYPE,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency'          => 'CAD',
                'status'            => 1,
                'message'           => null,
                'created_on'        => Carbon::now()->toDateTimeString(),
                'updated_on'        => null
            ]);
    }

    public function test_user_can_not_store_other_payment()
    {
        $this->createAndLogInNewUser();

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway([
            'brand'  => ConfigService::$brand,
            'config' => 'stripe_1'
        ]));

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));
        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => rand(),
            'payment_method_id' => $paymentMethod['id']
        ]));

        $due = $this->faker->numberBetween(0, 1000);

        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'due'               => $due
        ]);
        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
        $this->assertArraySubset([], $results->decodeResponseJson()['results']);
    }

    public function test_admin_store_manual_payment()
    {
        $this->permissionServiceMock->method('is')->willReturn(true);

        $paymentMethod = null;
        $due           = $this->faker->numberBetween(0, 1000);
        $results       = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod,
            'due'               => $due,
            'currency'          => $this->faker->currencyCode
        ]);

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
            'due'               => $due,
            'type'              => PaymentService::ORDER_PAYMENT_TYPE,
            'payment_method_id' => $paymentMethod,
            'status'            => true,
            'external_provider' => PaymentService::MANUAL_PAYMENT_TYPE,
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $results->decodeResponseJson()['results']);
    }

    public function test_user_store_payment_invalid_order_id()
    {
        $this->createAndLogInNewUser();

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => rand()
        ]));
        $due           = $this->faker->numberBetween(0, 1000);
        $results       = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'due'               => $due,
            'order_id'          => rand()
        ]);

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
                [
                    "source" => "order_id",
                    "detail" => "The selected order id is invalid.",
                ]
            ]
            , $results->decodeResponseJson()['errors']);
        $this->assertArraySubset([], $results->decodeResponseJson()['results']);
    }

    public function test_user_store_payment_invalid_subscription_id()
    {
        $this->createAndLogInNewUser();

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => rand()
        ]));

        $due     = $this->faker->numberBetween(0, 1000);
        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'due'               => $due,
            'subscription_id'   => rand()
        ]);

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
                [
                    "source" => "subscription_id",
                    "detail" => "The selected subscription id is invalid.",
                ]
            ]
            , $results->decodeResponseJson()['errors']);
        $this->assertArraySubset([], $results->decodeResponseJson()['results']);
    }
}
