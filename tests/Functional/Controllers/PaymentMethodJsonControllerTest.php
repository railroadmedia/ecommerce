<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Factories\CustomerFactory;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Repositories\PaymentGatewayRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaymentMethodJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var PaymentMethodFactory
     */
    private $paymentMethodFactory;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var PaymentGatewayRepository
     */
    protected $paymentGatewayRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentMethodRepository
     */
    protected $paymentMethodRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\CreditCardRepository
     */
    protected $creditCardRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository
     */
    protected $paypalBillingAgreementRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository
     */
    protected $userPaymentMethodRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\CustomerRepository
     */
    protected $customerRepository;

    CONST VALID_VISA_CARD_NUM          = '4242424242424242';
    CONST VALID_EXPRESS_CHECKOUT_TOKEN = 'EC-84G07962U40732257';

    protected function setUp()
    {
        parent::setUp();
        $this->paymentMethodFactory             = $this->app->make(PaymentMethodFactory::class);
        $this->customerFactory                  = $this->app->make(CustomerFactory::class);
        $this->paymentGatewayRepository         = $this->app->make(PaymentGatewayRepository::class);
        $this->paymentMethodRepository          = $this->app->make(PaymentMethodRepository::class);
        $this->creditCardRepository             = $this->app->make(CreditCardRepository::class);
        $this->paypalBillingAgreementRepository = $this->app->make(PaypalBillingAgreementRepository::class);
        $this->customerRepository               = $this->app->make(CustomerRepository::class);
        $this->userPaymentMethodRepository      = $this->app->make(UserPaymentMethodsRepository::class);
    }

    public function test_store_payment_method_credit_card_without_required_fields()
    {
        $results = $this->call('PUT', '/payment-method', [
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE
        ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "card_year",
                "detail" => "The card year field is required when method type is credit card.",
            ],
            [
                "source" => "card_month",
                "detail" => "The card month field is required when method type is credit card.",
            ],
            [
                "source" => "card_fingerprint",
                "detail" => "The card fingerprint field is required when method type is credit card.",
            ],
            [
                "source" => "card_number_last_four_digits",
                "detail" => "The card number last four digits field is required when method type is credit card.",
            ],
            [
                "source" => "company_name",
                "detail" => "The company name field is required when method type is credit card.",
            ],
            [
                "source" => "user_id",
                "detail" => "The user id field is required when customer id is not present."
            ],
            [
                "source" => "customer_id",
                "detail" => "The customer id field is required when user id is not present."
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store_payment_method_paypal_without_required_fields()
    {
        $results = $this->call('PUT', '/payment-method', [
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE
        ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "express_checkout_token",
                "detail" => "The express checkout token field is required when method type is paypal.",
            ],
            [
                "source" => "address_id",
                "detail" => "The address id field is required when method type is paypal.",
            ],
            [
                "source" => "user_id",
                "detail" => "The user id field is required when customer id is not present."
            ],
            [
                "source" => "customer_id",
                "detail" => "The customer id field is required when user id is not present."
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store_method_type_required()
    {
        $results = $this->call('PUT', '/payment-method',
            [
                'user_id' => rand()
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "method_type",
                "detail" => "The method type field is required.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_user_store_credit_card_payment_method()
    {
        $userId             = $this->createAndLogInNewUser();
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $cardYear           = $cardExpirationDate->format('Y');
        $cardMonth          = $cardExpirationDate->format('m');
        $cardFingerprint    = self::VALID_VISA_CARD_NUM;
        $cardLast4          = $this->faker->randomNumber(4);
        $cardType           = $this->faker->creditCardType;
        $currency           = $this->faker->currencyCode;
        $paymentGateway     = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));

        $results = $this->call('PUT', '/payment-method', [
            'method_type'                  => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card_year'                    => $cardYear,
            'card_month'                   => $cardMonth,
            'card_fingerprint'             => $cardFingerprint,
            'card_number_last_four_digits' => $cardLast4,
            'company_name'                 => $cardType,
            'currency'                     => $currency,
            'user_id'                      => $userId,
            'payment_gateway'              => $paymentGateway['id']
        ]);

        //assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        //assert payment data subset
        $this->assertArraySubset([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'created_on'  => Carbon::now()->toDateTimeString(),
            'updated_on'  => null,
            'currency'    => $currency,
            'method'      => [
                'type'              => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint'       => $cardFingerprint,
                'last_four_digits'  => $cardLast4,
                'cardholder_name'   => '',
                'company_name'      => $cardType,
                'external_provider' => 'stripe',
                'expiration_date'   => Carbon::create(
                    $cardYear,
                    $cardMonth,
                    12,
                    0,
                    0,
                    0
                ),
                'created_on'        => Carbon::now()->toDateTimeString(),
                'updated_on'        => null
            ]
        ], $results->decodeResponseJson()['results']);

        //assert payment method, credit card, link between user and payment method saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'currency'    => $currency,
                'created_on'  => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'type'               => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint'        => $cardFingerprint,
                'last_four_digits'   => $cardLast4,
                'company_name'       => $cardType,
                'external_provider'  => 'stripe',
                'expiration_date'    => Carbon::create(
                    $cardYear,
                    $cardMonth,
                    12,
                    0,
                    0,
                    0
                )->toDateTimeString(),
                'payment_gateway_id' => $paymentGateway['id'],
                'created_on'         => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id'           => $userId,
                'payment_method_id' => 1,
                'created_on'        => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_user_store_paypal_payment_method()
    {
        $expressCheckoutToken = self::VALID_EXPRESS_CHECKOUT_TOKEN;
        $addressId            = $this->faker->numberBetween();
        $userId               = rand();
        $customerId           = null;
        $currency             = 'cad';
        $paymentGateway       = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'paypal_1']));

        $results = $this->call('PUT', '/payment-method', [
            'method_type'            => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'express_checkout_token' => $expressCheckoutToken,
            'address_id'             => $addressId,
            'user_id'                => $userId,
            'customer_id'            => $customerId,
            'currency'               => $currency,
            'payment_gateway'        => $paymentGateway['id']
        ]);

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'created_on'  => Carbon::now()->toDateTimeString(),
            'updated_on'  => null,
            'currency'    => $currency,
            'method'      => [
                'express_checkout_token' => $expressCheckoutToken,
                'address_id'             => $addressId,
                'expiration_date'        => Carbon::now()->addYears(10)->toDateTimeString(),
                'created_on'             => Carbon::now()->toDateTimeString(),
                'updated_on'             => null
            ]
        ], $results->decodeResponseJson()['results']);

        //assert payment method, credit card, link between user and payment method saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'currency'    => $currency,
                'created_on'  => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'express_checkout_token' => $expressCheckoutToken,
                'address_id'             => $addressId,
                'expiration_date'        => Carbon::now()->addYears(10)->toDateTimeString(),
                'payment_gateway_id'     => $paymentGateway['id'],
                'created_on'             => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id'           => $userId,
                'payment_method_id' => 1,
                'created_on'        => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_store_payment_method_credit_card_failed()
    {
        $userId             = $this->createAndLogInNewUser();
        $cardExpirationDate = Carbon::now()->addYear(2);
        $cardYear           = $cardExpirationDate->format('Y');
        $cardMonth          = $cardExpirationDate->format('m');
        $cardLast4          = $this->faker->randomNumber(4);
        $cardType           = $this->faker->creditCardType;
        $currency           = $this->faker->currencyCode;

        //incorrect card number
        $cardFingerprint = $this->faker->randomNumber();

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));

        $results = $this->call('PUT', '/payment-method', [
            'method_type'                  => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card_year'                    => $cardYear,
            'card_month'                   => $cardMonth,
            'card_fingerprint'             => $cardFingerprint,
            'card_number_last_four_digits' => $cardLast4,
            'company_name'                 => $cardType,
            'currency'                     => $currency,
            'user_id'                      => $userId,
            'payment_gateway'              => $paymentGateway['id']
        ]);

        //assert error message subset results
        $this->assertArraySubset([
            'title'  => 'Not found.',
            'detail' => 'Creation failed, method type(' . PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE . ') not allowed or incorrect data.Can not create token:: The card number is not a valid credit card number.'
        ], $results->decodeResponseJson('error'));

        //assert payment method data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'currency'    => $currency,
                'created_on'  => Carbon::now()->toDateTimeString()
            ]
        );

        //assert credit card data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tableCreditCard,
            [
                'type'               => $cardType,
                'fingerprint'        => $cardFingerprint,
                'last_four_digits'   => $cardLast4,
                'company_name'       => $cardType,
                'payment_gateway_id' => $paymentGateway['id'],
                'created_on'         => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_user_store_paypal_payment_method_failed()
    {
        $expressCheckoutToken = $this->faker->numberBetween();
        $addressId            = $this->faker->numberBetween();
        $userId               = rand();
        $customerId           = null;
        $currency             = 'cad';
        $paymentGateway       = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'paypal_1']));

        $results = $this->call('PUT', '/payment-method', [
            'method_type'            => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'express_checkout_token' => $expressCheckoutToken,
            'address_id'             => $addressId,
            'user_id'                => $userId,
            'customer_id'            => $customerId,
            'currency'               => $currency,
            'payment_gateway'        => $paymentGateway['id']
        ]);

        //assert error message subset results
        $this->assertArraySubset([
            'title' => 'Not found.',
            // 'detail' => 'Creation failed, method type(' . PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE . ') not allowed or incorrect data.'
        ], $results->decodeResponseJson('error'));

        $this->assertContains('Creation failed, method type(' . PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE . ') not allowed or incorrect data.', $results->decodeResponseJson('error')['detail']);
        //assert payment method data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'currency'    => $currency,
                'created_on'  => Carbon::now()->toDateTimeString()
            ]
        );

        //assert paypal billing agreement data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'express_checkout_token' => $expressCheckoutToken,
                'address_id'             => $addressId,
                'expiration_date'        => Carbon::now()->addYears(10)->toDateTimeString(),
                'payment_gateway_id'     => $paymentGateway['id'],
                'created_on'             => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseMissing(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id'           => $userId,
                'payment_method_id' => 1,
                'created_on'        => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_update_payment_method_create_credit_card_validation()
    {
        $userId = $this->createAndLogInNewUser();

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));
        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]));

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method'   => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
                'method_type'     => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'payment_gateway' => $paymentGateway['id']
            ]
        );

        //assert respons status code and errors
        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "card_year",
                "detail" => "The card year field is required when create or update a credit card.",
            ],
            [
                "source" => "card_month",
                "detail" => "The card month field is required when create or update a credit card.",
            ],
            [
                "source" => "card_fingerprint",
                "detail" => "The card finger print field is required when create a new credit card.",
            ],
            [
                "source" => "card_number_last_four_digits",
                "detail" => "The card last four digits field is required when create a new credit card.",
            ],
            [
                "source" => "company_name",
                "detail" => "The company name field is required when create a new credit card.",
            ]
        ], $results->decodeResponseJson()['errors']);

        //assert payment method not updated in the db
        $this->assertDatabaseHas(ConfigService::$tablePaymentMethod,
            [
                'id'        => $paymentMethod['id'],
                'method_id' => $creditCard['id']
            ]);

        $this->assertDatabaseMissing(
            ConfigService::$tableCreditCard,
            ['id' => $creditCard['id'] + 1]
        );
    }

    public function test_update_payment_method_update_credit_card_validation()
    {
        $userId         = $this->createAndLogInNewUser();
        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));
        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD,
                'method_type'   => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'user_id'       => rand()
            ]
        );

        //assert results status code and errors
        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
            [
                "source" => "card_year",
                "detail" => "The card year field is required when create or update a credit card.",
            ],
            [
                "source" => "card_month",
                "detail" => "The card month field is required when create or update a credit card.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_update_payment_method_use_paypal_validation()
    {
        $userId         = $this->createAndLogInNewUser();
        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));
        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $paymentGatewayPayPal = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'paypal_1']));
        $results              = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method'   => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL,
                'method_type'     => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'payment_gateway' => $paymentGatewayPayPal['id']
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
            [
                "source" => "express_checkout_token",
                "detail" => "The express checkout token field is required when update payment method and use paypal.",
            ],
            [
                "source" => "address_id",
                "detail" => "The address id field is required when update payment method and use paypal.",
            ]
        ], $results->decodeResponseJson()['errors']);

        //assert payment method data not updated in the db
        $this->assertDatabaseHas(ConfigService::$tablePaymentMethod,
            [
                'id'          => $paymentMethod['id'],
                'method_id'   => $creditCard['id'],
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE
            ]);
    }

    public function test_user_update_payment_method_create_credit_card_response()
    {
        $expirationDate  = $this->faker->creditCardExpirationDate;
        $cardFingerprint = self::VALID_VISA_CARD_NUM;
        $cardLast4       = $this->faker->randomNumber(4);
        $cardType        = $this->faker->creditCardType;
        $cardHolderName  = $this->faker->word;

        $userId = $this->createAndLogInNewUser();

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));
        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method'                => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
                'method_type'                  => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card_year'                    => $expirationDate->format('Y'),
                'card_month'                   => $expirationDate->format('m'),
                'card_fingerprint'             => $cardFingerprint,
                'card_number_last_four_digits' => $cardLast4,
                'cardholder_name'              => $cardHolderName,
                'company_name'                 => $cardType,
                'user_id'                      => $userId,
                'payment_gateway'              => $paymentGateway['id']
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset([
            'id'          => $paymentMethod['id'],
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'created_on'  => $paymentMethod['created_on'],
            'updated_on'  => Carbon::now()->toDateTimeString(),
            'currency'    => $paymentMethod['currency'],
        ], $results->decodeResponseJson()['results']);

        //assert new credit card saved in the db
        $this->assertDatabaseHas(ConfigService::$tableCreditCard,
            [
                'fingerprint'        => $cardFingerprint,
                'last_four_digits'   => $cardLast4,
                'cardholder_name'    => $cardHolderName,
                'company_name'       => $cardType,
                'payment_gateway_id' => $paymentGateway['id']
            ]);

        $this->assertDatabaseMissing(ConfigService::$tablePaymentMethod,
            [
                'id'        => $paymentMethod['id'],
                'method_id' => $creditCard['id']
            ]);
    }

    public function test_update_payment_method_use_paypal()
    {
        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));
        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $expressCheckoutToken = self::VALID_EXPRESS_CHECKOUT_TOKEN;

        $addressId            = rand();
        $paymentGatewayPaypal = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'paypal_1']));

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method'          => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL,
                'method_type'            => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'express_checkout_token' => $expressCheckoutToken,
                'address_id'             => $addressId,
                'payment_gateway'        => $paymentGatewayPaypal['id']
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset([
            'id'          => $paymentMethod['id'],
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'created_on'  => $paymentMethod['created_on'],
            'updated_on'  => Carbon::now()->toDateTimeString(),
            'currency'    => $paymentMethod['currency'],
        ], $results->decodeResponseJson()['results']);

        //assert data updated in db
        $this->assertDatabaseHas(ConfigService::$tablePaymentMethod,
            [
                'id'          => $paymentMethod['id'],
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE
            ]);
        $this->assertDatabaseHas(ConfigService::$tablePaypalBillingAgreement,
            [
                'express_checkout_token' => $expressCheckoutToken,
                'address_id'             => $addressId,
                'payment_gateway_id'     => $paymentGatewayPaypal['id']
            ]);
    }

    public function test_delete_payment_method_not_authenticated_user()
    {
        $this->permissionServiceMock->method('canOrThrow')->willThrowException(
            new NotAllowedException('This action is unauthorized.')
        );

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));
        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);

        //assert payment method still exist in db
        $this->assertDatabaseHas(ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id']
            ]);
    }

    public function test_user_delete_payment_method_credit_card()
    {
        $userId = $this->createAndLogInNewUser();

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));
        $creditCard     = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $assignPaymentMethodToUser = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]));

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'id'          => $paymentMethod['id'],
                'method_type' => $paymentMethod['method_type'],
                'method_id'   => 1
            ]
        );
        $this->assertDatabaseMissing(
            ConfigService::$tableCreditCard,
            [
                'id'   => $paymentMethod['method']['id'],
                'type' => $paymentMethod['method_type'],
            ]
        );
    }

    public function test_delete_payment_method_paypal()
    {
        $userId = $this->createAndLogInNewUser();

        $paymentGateway = $this->paymentGatewayRepository->create($this->faker->paymentGateway(['config' => 'stripe_1']));
        $paypalBilling  = $this->paypalBillingAgreementRepository->create($this->faker->paypalBillingAgreement([
            'payment_gateway_id' => $paymentGateway['id']
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'method_id'   => $paypalBilling['id']
        ]));

        $assignPaymentMethodToUser = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]));

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'id'          => $paymentMethod['id'],
                'method_type' => $paymentMethod['method_type'],
                'method_id'   => 1
            ]
        );
        $this->assertDatabaseMissing(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'id' => $paymentMethod['method']['id']
            ]
        );
    }
}
