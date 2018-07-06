<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Customer;
use Stripe\Token;

class PaymentMethodJsonControllerTest extends EcommerceTestCase
{
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
                "source" => "card_token",
                "detail" => "The card token field is required when method type is credit-card.",
            ],
            [
                "source" => "gateway",
                "detail" => "The gateway field is required.",
            ],
            [
                "source" => "user_id",
                "detail" => "The user id field is required when customer id is not present."
            ],
            [
                "source" => "customer_id",
                "detail" => "The customer id field is required when user id is not present."
            ]
        ], $results->decodeResponseJson('meta')['errors']);
    }

    public function test_store_payment_method_paypal_without_required_fields()
    {
        $results = $this->call('PUT', '/payment-method', [
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE
        ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "gateway",
                "detail" => "The gateway field is required.",
            ],
            [
                "source" => "token",
                "detail" => "The token field is required when method type is paypal.",
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
        ], $results->decodeResponseJson('meta')['errors']);
    }

    public function test_store_method_type_required()
    {
        $results = $this->call('PUT', '/payment-method',
            [
                'user_id' => rand(),
                'gateway' => 'stripe'
            ]);

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "method_type",
                "detail" => "The method type field is required.",
            ]
        ], $results->decodeResponseJson('meta')['errors']);
    }

    public function test_user_store_credit_card_payment_method()
    {
        $userId                 = $this->createAndLogInNewUser();
        $cardExpirationDate     = $this->faker->creditCardExpirationDate;
        $cardYear               = $cardExpirationDate->format('Y');
        $cardMonth              = $cardExpirationDate->format('m');
        $cardFingerprint        = self::VALID_VISA_CARD_NUM;
        $cardLast4              = $this->faker->randomNumber(4);
        $cardType               = $this->faker->creditCardType;
        $currency               = $this->faker->currencyCode;
        $customer               = new Customer();
        $customer->email        = $this->faker->email;
        $fakerCard              = new Card();
        $fakerCard->fingerprint = $cardFingerprint;
        $fakerCard->brand       = $cardType;
        $fakerCard->last4       = $cardLast4;
        $fakerCard->exp_year    = $cardExpirationDate->format('Y');
        $fakerCard->exp_month   = $cardExpirationDate->format('m');
        $fakerCard->id          = $this->faker->word;
        $cardToken              = new Token();
        $cardToken->id          = rand();
        $cardToken->card        = $fakerCard;
        $this->stripeExternalHelperMock->method('createCustomer')->willReturn($customer);
        $this->stripeExternalHelperMock->method('createCardToken')->willReturn($cardToken);
        $this->stripeExternalHelperMock->method('retrieveToken')->willReturn($cardToken);
        $this->stripeExternalHelperMock->method('createCard')->willReturn($fakerCard);

        $results = $this->call('PUT', '/payment-method', [
            'method_type'                  => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card_year'                    => $cardYear,
            'card_month'                   => $cardMonth,
            'card_fingerprint'             => $cardFingerprint,
            'card_number_last_four_digits' => $cardLast4,
            'company_name'                 => $cardType,
            'currency'                     => $currency,
            'user_id'                      => $userId,
            'gateway'                      => 'drumeo',
            'card_token'                   => $cardToken->id
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
                'fingerprint'      => $cardFingerprint,
                'last_four_digits' => $cardLast4,
                'cardholder_name'  => '',
                'company_name'     => $cardType,

                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
        ], $results->decodeResponseJson()['data'][0]);

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
                'fingerprint'          => $cardFingerprint,
                'last_four_digits'     => $cardLast4,
                'company_name'         => $cardType,
                'payment_gateway_name' => 'drumeo',
                'created_on'           => Carbon::now()->toDateTimeString(),
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
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $expressCheckoutToken = self::VALID_EXPRESS_CHECKOUT_TOKEN;
        $addressId            = $this->faker->numberBetween();
        $customerId           = null;
        $currency             = 'cad';

        $results = $this->call('PUT', '/payment-method', [
            'method_type'            => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'token' => $expressCheckoutToken,
            'address_id'             => $addressId,
            'user_id'                => $userId,
            'customer_id'            => $customerId,
            'currency'               => $currency,
            'gateway'        => 'drumeo'
        ]);

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'created_on'  => Carbon::now()->toDateTimeString(),
            'updated_on'  => null,
            'currency'    => $currency,
            'method'      => [
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
        ], $results->decodeResponseJson()['data'][0]);

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
                'payment_gateway_name' => 'drumeo',
                'created_on'           => Carbon::now()->toDateTimeString(),
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
        $cardExpirationDate     = $this->faker->creditCardExpirationDate;
        $cardFingerprint        = self::VALID_VISA_CARD_NUM;
        $customer               = new Customer();
        $customer->email        = $this->faker->email;
        $fakerCard              = new Card();
        $fakerCard->fingerprint = $cardFingerprint;
        $fakerCard->brand       = $cardType;
        $fakerCard->last4       = $cardLast4;
        $fakerCard->exp_year    = $cardExpirationDate->format('Y');
        $fakerCard->exp_month   = $cardExpirationDate->format('m');
        $fakerCard->id          = $this->faker->word;
        $cardToken              = new Token();
        $cardToken->id          = rand();
        $cardToken->card        = $fakerCard;
        $this->stripeExternalHelperMock->method('createCustomer')->willReturn($customer);
        $this->stripeExternalHelperMock->method('retrieveToken')->willReturn($cardToken);

        //incorrect card number
        $cardFingerprint = $this->faker->randomNumber();

        $this->stripeExternalHelperMock->method('createCard')->willThrowException(new PaymentFailedException('The card number is incorrect. Check the card’s number or use a different card.'));

        $this->expectException(PaymentFailedException::class);

        $results = $this->call('PUT', '/payment-method', [
            'method_type'                  => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card_token'                    => $this->faker->creditCardNumber,
            'currency'                     => $currency,
            'user_id'                      => $userId,
            'gateway'              => 'drumeo'
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
                'type'             => $cardType,
                'fingerprint'      => $cardFingerprint,
                'last_four_digits' => $cardLast4,
                'company_name'     => $cardType,
                'created_on'       => Carbon::now()->toDateTimeString()
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
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willThrowException(new PaymentFailedException('Payment failed'));

        $this->expectException(PaymentFailedException::class);
        $results = $this->call('PUT', '/payment-method', [
            'method_type'            => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'token' => $expressCheckoutToken,
            'address_id'             => $addressId,
            'user_id'                => $userId,
            'customer_id'            => $customerId,
            'currency'               => $currency,
            'gateway'        => 'drumeo'
        ]);

        //assert error message subset results
        $this->assertArraySubset([
            'title' => 'Not found.',
            // 'detail' => 'Creation failed, method type(' . PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE . ') not allowed or incorrect data.'
        ], $results->decodeResponseJson('error'));

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
                'payment_gateway_name'   => 'drumeo',
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

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_name' => 'recordeo'
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
                'update_method'   => 'create-credit-card',
                'method_type'     => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'payment_gateway' => 'drumeo'
            ]
        );

        //assert respons status code and errors
        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "card_token",
                "detail" => "The card token field is required when update method is create-credit-card.",
            ]
        ], $results->decodeResponseJson('meta')['errors']);

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
        $userId     = $this->createAndLogInNewUser();
        $creditCard = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_name' => 'drumeo'
        ]));

        $paymentMethod     = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));
        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]));

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => 'update-current-credit-card',
                'method_type'   => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'user_id'       => $userId
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
        ], $results->decodeResponseJson('meta')['errors']);
    }

    public function test_update_payment_method_use_paypal_validation()
    {
        $userId     = $this->createAndLogInNewUser();
        $creditCard = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_name' => 'recordeo'
        ]));

        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method'   => 'use-paypal',
                'method_type'     => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'payment_gateway' => 'recordeo'
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
            [
                "source" => "token",
                "detail" => "The token field is required when update method is use-paypal.",
            ],
            [
                "source" => "address_id",
                "detail" => "The address id field is required when update payment method and use paypal.",
            ]
        ], $results->decodeResponseJson('meta')['errors']);

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
        $userId          = $this->createAndLogInNewUser();
        $expirationDate  = $this->faker->creditCardExpirationDate;
        $cardFingerprint = self::VALID_VISA_CARD_NUM;
        $cardLast4       = $this->faker->randomNumber(4);
        $cardType        = $this->faker->creditCardType;
        $cardHolderName  = $this->faker->word;

        $userId = $this->createAndLogInNewUser();

        $customer               = new Customer();
        $customer->email        = $this->faker->email;
        $fakerCard              = new Card();
        $fakerCard->fingerprint = $cardFingerprint;
        $fakerCard->brand       = $cardType;
        $fakerCard->last4       = $cardLast4;
        $fakerCard->exp_year    = $expirationDate->format('Y');
        $fakerCard->exp_month   = $expirationDate->format('m');
        $fakerCard->id          = $this->faker->word;
        $cardToken              = new Token();
        $cardToken->id          = rand();
        $cardToken->card        = $fakerCard;
        $this->stripeExternalHelperMock->method('createCustomer')->willReturn($customer);
        $this->stripeExternalHelperMock->method('createCardToken')->willReturn($cardToken);
        $this->stripeExternalHelperMock->method('retrieveToken')->willReturn($cardToken);
        $this->stripeExternalHelperMock->method('createCard')->willReturn($fakerCard);

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard([
            'payment_gateway_name' => 'drumeo'
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
                'update_method'                => 'create-credit-card',
                'method_type'                  => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card_token'                    => $cardToken->id,
                'user_id'                      => $userId,
                'gateway'              => 'drumeo'
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset([
            'id'          => $paymentMethod['id'],
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'created_on'  => $paymentMethod['created_on'],
            'updated_on'  => Carbon::now()->toDateTimeString(),
        ], $results->decodeResponseJson()['data'][0]);

        $this->assertDatabaseMissing(ConfigService::$tablePaymentMethod,
            [
                'id'        => $paymentMethod['id'],
                'method_id' => $creditCard['id']
            ]);
    }

    public function test_update_payment_method_use_paypal()
    {
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')->willReturn(rand());
        $userId     = $this->createAndLogInNewUser();
        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());

        $paymentMethod     = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));
        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]));

        $expressCheckoutToken = self::VALID_EXPRESS_CHECKOUT_TOKEN;

        $addressId = rand();

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method'          => 'use-paypal',
                'method_type'            => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'token' => $expressCheckoutToken,
                'address_id'             => $addressId,
                'gateway'        => 'drumeo'
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset([
            'id'          => $paymentMethod['id'],
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'created_on'  => $paymentMethod['created_on'],
            'updated_on'  => Carbon::now()->toDateTimeString(),
            'currency'    => $paymentMethod['currency'],
        ], $results->decodeResponseJson()['data'][0]);

        //assert data updated in db
        $this->assertDatabaseHas(ConfigService::$tablePaymentMethod,
            [
                'id'          => $paymentMethod['id'],
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE
            ]);
        $this->assertDatabaseHas(ConfigService::$tablePaypalBillingAgreement,
            [
                'payment_gateway_name' => 'drumeo'
            ]);
    }

    public function test_delete_payment_method_not_authenticated_user()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willThrowException(
            new NotAllowedException('This action is unauthorized.')
        );

        $creditCard    = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]));

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson('meta')['errors']);

        //assert payment method still exist in db
        $this->assertDatabaseHas(ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id']
            ]);
    }

    public function test_user_delete_payment_method_credit_card()
    {
        $userId = $this->createAndLogInNewUser();

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());

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
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id'          => $paymentMethod['id'],
                'method_type' => $paymentMethod['method_type'],
                'method_id'   => 1,
                'deleted_on'  => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_delete_payment_method_paypal()
    {
        $userId = $this->createAndLogInNewUser();

        $paypalBilling = $this->paypalBillingAgreementRepository->create($this->faker->paypalBillingAgreement([
            'payment_gateway_name' => 'drumeo'
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
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id'          => $paymentMethod['id'],
                'method_type' => $paymentMethod['method_type'],
                'method_id'   => 1,
                'deleted_on'  => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_admin_delete_payment_method()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $creditCard    = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));

        $userPaymentMethod = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => rand(),
            'payment_method_id' => $paymentMethod['id']
        ]));

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertSoftDeleted(ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id']
            ]);
    }

    public function test_get_user_payment_methods()
    {
        $userId                    = $this->faker->numberBetween();
        $creditCard                = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod1            = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method_id'   => $creditCard['id']
        ]));
        $paymentMethod1['user']    = [
            'user_id'    => $userId,
            'is_primary' => 1
        ];
        $paymentMethod1['user_id'] = $userId;
        $paypalBilling             = $this->paypalBillingAgreementRepository->create($this->faker->paypalBillingAgreement([
            'payment_gateway_name' => 'drumeo'
        ]));

        $paymentMethod2            = $this->paymentMethodRepository->create($this->faker->paymentMethod([
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'method_id'   => $paypalBilling['id']
        ]));
        $paymentMethod2['user']    = [
            'user_id'    => $userId,
            'is_primary' => 1
        ];
        $paymentMethod2['user_id'] = $userId;

        $assignPaymentMethodToUser                   = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod1['id'],
            'is_primary'        => 1
        ]));
        $assignPaymentMethodToUser['payment_method'] = (array) $paymentMethod1;
        $userPaymentMethod                           = $this->userPaymentMethodRepository->create($this->faker->userPaymentMethod([
            'user_id'           => $userId,
            'payment_method_id' => $paymentMethod2['id'],
            'is_primary'        => 1
        ]));
        $userPaymentMethod['payment_method']         = (array) $paymentMethod2;
        $results                                     = $this->call('GET', '/user-payment-method/' . $userId);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals([$assignPaymentMethodToUser->getArrayCopy(), $assignPaymentMethodToUser->getArrayCopy()],
            $results->decodeResponseJson('data'));
    }

    public function test_get_user_payment_methods_not_exists()
    {
        $results = $this->call('GET', '/user-payment-method/' . rand());

        $this->assertEmpty($results->decodeResponseJson('data'));
    }
}
