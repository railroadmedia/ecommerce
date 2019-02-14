<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;

class PaymentJsonControllerTest extends EcommerceTestCase
{

    protected function setUp()
    {
        parent::setUp();

        // todo DEVE-31 - add taxes
    }

    public function test_user_store_payment()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $due = $this->faker->numberBetween(0, 1000);
        $currency = $this->getCurrency();
        $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due; // todo - update with DEVE-30 specs
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')->willReturn($fakerCharge);

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $response = $this->call(
            'PUT',
            '/payment',
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'due' => $due,
                        'currency' => $currency,
                        'payment_gateway' => $gateway
                    ],
                    'relationships' => [
                        'paymentMethod' => [
                            'data' => [
                                'type' => 'paymentMethod',
                                'id' => $paymentMethod['id']
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'payment',
                    'attributes' => [
                        'total_due' => $due,
                        'total_paid' => $due,
                        'total_refunded' => null,
                        'type' => 'order',
                        'external_provider' => 'stripe',
                        'status' => '1',
                        'message' => '',
                        'currency' => $currency,
                        'created_at' => Carbon::now()->toDateTimeString(),
                        'updated_at' => Carbon::now()->toDateTimeString()
                    ],
                ],
            ],
            $response->decodeResponseJson()
        );

        // assert payment exists in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => $due,
                'total_paid' => $due,
                'total_refunded' => null,
                'type' => ConfigService::$orderPaymentType,
                'payment_method_id' => $paymentMethod['id'],
                'external_provider' => 'stripe',
                'currency' => $currency,
                'status' => '1',
                'message' => '',
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ]
        );
    }

    /*
    public function test_user_store_paypal_payment()
    {
        $userId = $this->createAndLogInNewUser();
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
            'type'              => ConfigService::$orderPaymentType,
            'payment_method_id' => $paymentMethod['id'],
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $results->decodeResponseJson()['data'][0]);

        //assert payment exists in the db
        $this->assertDatabaseHas(ConfigService::$tablePayment,
            [
                'due'               => 100,
                'type'              => ConfigService::$orderPaymentType,
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
        $due = $this->faker->numberBetween(0, 1000);

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);
        $this->permissionServiceMock->method('can')->willReturn(true);

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
            'type'              => ConfigService::$orderPaymentType,
            'currency'          => 'cad',
            'payment_method_id' => $paymentMethod['id'],
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $results->decodeResponseJson()['data'][0]);

        //assert payment exists in the db
        $this->assertDatabaseHas(ConfigService::$tablePayment,
            [
                'due'               => $due,
                'type'              => ConfigService::$orderPaymentType,
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
            , $results->decodeResponseJson('error'));
        $this->assertArraySubset([], $results->decodeResponseJson('results'));
    }

    public function test_admin_store_manual_payment()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

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
            'type'              => ConfigService::$orderPaymentType,
            'payment_method_id' => $paymentMethod,
            'status'            => true,
            'external_provider' => ConfigService::$manualPaymentType,
            'created_on'        => Carbon::now()->toDateTimeString(),
            'updated_on'        => null
        ], $results->decodeResponseJson()['data'][0]);
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
            , $results->decodeResponseJson('meta')['errors']);
        $this->assertEquals([], $results->decodeResponseJson('data'));
        $this->assertEquals(0, $results->decodeResponseJson('meta')['totalResults']);
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
            , $results->decodeResponseJson('meta')['errors']);
        $this->assertEquals([], $results->decodeResponseJson('data'));
    }

    public function test_index()
    {
    }

    public function test_payment() // todo - rename
    {
        $payment = $this->paymentRepository->create($this->faker->payment());
        $results = $this->call('DELETE', '/payment/' . $payment['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertSoftDeleted(
            ConfigService::$tablePayment,
            [
                'id' => $payment['id']
            ]
        );
    }

    public function test_delete_not_existing_payment()

    {
        $randomId = $this->faker->randomNumber();

        $results = $this->call('DELETE', '/payment/' . $randomId);

        //assert response status code
        $this->assertEquals(404, $results->getStatusCode());

        //assert the error message that it's returned in JSON format
        $this->assertEquals(
            [
                "title"  => "Not found.",
                "detail" => "Delete failed, payment not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson('meta')['errors']);
    }
    */
}
