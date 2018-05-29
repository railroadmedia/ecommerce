<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Permissions\Services\PermissionService;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;

class PaymentJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

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

    /**
     * @var \Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->paymentMethodRepository          = $this->app->make(PaymentMethodRepository::class);
        $this->creditCardRepository             = $this->app->make(CreditCardRepository::class);
        $this->permissionService                = $this->app->make(PermissionService::class);
        $this->userPaymentMethodRepository      = $this->app->make(UserPaymentMethodsRepository::class);
        $this->paypalBillingAgreementRepository = $this->app->make(PaypalBillingAgreementRepository::class);
    }

    public function test_user_store_payment()
    {
        $userId = $this->createAndLogInNewUser();
        $due    = $this->faker->numberBetween(0, 1000);

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge           = new Charge();
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount   = $due;
        $fakerCharge->status   = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());

        $paymentMethod     = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));
        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]));

        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'currency'          => 'cad',
            'due'               => $due,
            'payment_gateway'   => 'drumeo'
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
                'message'           => '',
                'created_on'        => Carbon::now()->toDateTimeString(),
                'updated_on'        => null
            ]);
    }

    public function test_user_store_paypal_payment()
    {
        $userId                 = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('createReferenceTransaction')->willReturn(rand());

        $paypalBillingAgreement = $this->paypalBillingAgreementRepository->create($this->faker->paypalBillingAgreement());
        $paymentMethod          = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_id'   => $paypalBillingAgreement['id'],
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'currency'    => 'CAD'
        ]));
        $userPaymentMethod      = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]));
        $results                = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'currency'          => 'CAD',
            'due'               => 100,
            'payment_gateway'   => 'drumeo'
        ]);

        //assert response status code and content
        $this->assertEquals(200, $results->getStatusCode());
        $this->assertArraySubset([
            'due'               => 100,
            'type'              => PaymentService::ORDER_PAYMENT_TYPE,
            'payment_method_id' => $paymentMethod['id'],
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $results->decodeResponseJson()['results']);

        //assert payment exists in the db
        $this->assertDatabaseHas(ConfigService::$tablePayment,
            [
                'due'               => 100,
                'type'              => PaymentService::ORDER_PAYMENT_TYPE,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'paypal',
                'currency'          => 'CAD',
                'status'            => 1,
                'message'           => '',
                'created_on'        => Carbon::now()->toDateTimeString(),
                'updated_on'        => null
            ]);
    }

    public function test_admin_store_any_payment()
    {
        $due     = $this->faker->numberBetween(0, 1000);

        $this->permissionServiceMock->method('is')->willReturn(true);
        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge           = new Charge();
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount   = $due;
        $fakerCharge->status   = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());

        $paymentMethod     = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));
        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => rand(),
            'payment_method_id' => $paymentMethod['id']
        ]));

        $results = $this->call('PUT', '/payment', [
            'payment_method_id' => $paymentMethod['id'],
            'payment_gateway'   => 'drumeo',
            'due'               => $due
        ]);

        //assert response
        $this->assertEquals(200, $results->getStatusCode());
        $this->assertArraySubset([
            'due'               => $due,
            'type'              => PaymentService::ORDER_PAYMENT_TYPE,
            'currency'          => 'cad',
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
                'message'           => '',
                'created_on'        => Carbon::now()->toDateTimeString(),
                'updated_on'        => null
            ]);
    }

    public function test_user_can_not_store_other_payment()
    {
        $this->createAndLogInNewUser();

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());

        $paymentMethod     = $this->paymentMethodRepository->create($this->faker->paymentMethod([
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
            'payment_gateway'   => 'drumeo',
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
