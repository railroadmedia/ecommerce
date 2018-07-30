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
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Services\CartAddressService;
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

    /**
     * @var \Railroad\Ecommerce\Repositories\AddressRepository
     */
    protected $addressRepository;

    CONST VALID_VISA_CARD_NUM = '4242424242424242';
    CONST VALID_EXPRESS_CHECKOUT_TOKEN = 'EC-84G07962U40732257';

    protected function setUp()
    {
        parent::setUp();

        $this->paymentMethodRepository = $this->app->make(PaymentMethodRepository::class);
        $this->creditCardRepository = $this->app->make(CreditCardRepository::class);
        $this->paypalBillingAgreementRepository = $this->app->make(PaypalBillingAgreementRepository::class);
        $this->customerRepository = $this->app->make(CustomerRepository::class);
        $this->userPaymentMethodRepository = $this->app->make(UserPaymentMethodsRepository::class);
        $this->addressRepository = $this->app->make(AddressRepository::class);
    }

    public function test_store_payment_method_credit_card_without_required_fields()
    {
        $results = $this->call(
            'PUT',
            '/payment-method',
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'user_email' => $this->faker->email
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
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
                    "detail" => "The user id field is required when customer id is not present.",
                ],
                [
                    "source" => "customer_id",
                    "detail" => "The customer id field is required when user id is not present.",
                ],
            ],
            $results->decodeResponseJson('meta')['errors']
        );
    }

    public function test_store_payment_method_paypal_without_required_fields()
    {
        $results = $this->call(
            'PUT',
            '/payment-method',
            [
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'user_email' => $this->faker->email
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
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
                    "detail" => "The user id field is required when customer id is not present.",
                ],
                [
                    "source" => "customer_id",
                    "detail" => "The customer id field is required when user id is not present.",
                ],
            ],
            $results->decodeResponseJson('meta')['errors']
        );
    }

    public function test_store_method_type_required()
    {
        $results = $this->call(
            'PUT',
            '/payment-method',
            [
                'user_id' => rand(),
                'gateway' => 'stripe',
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    "source" => "method_type",
                    "detail" => "The method type field is required.",
                ],
                [
                    "source" => "user_email",
                    "detail" => "The user email field is required when customer id is not present.",
                ],
            ],
            $results->decodeResponseJson('meta')['errors']
        );
    }

    public function test_user_store_credit_card_payment_method()
    {
        $userId = $this->createAndLogInNewUser();
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $cardYear = $cardExpirationDate->format('Y');
        $cardMonth = $cardExpirationDate->format('m');
        $cardFingerprint = self::VALID_VISA_CARD_NUM;
        $cardLast4 = $this->faker->randomNumber(4);
        $cardType = $this->faker->creditCardType;
        $currency = $this->faker->currencyCode;
        $customer = new Customer();
        $customer->email = $this->faker->email;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $cardFingerprint;
        $fakerCard->brand = $cardType;
        $fakerCard->last4 = $cardLast4;
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;
        $cardToken = new Token();
        $cardToken->id = rand();
        $cardToken->card = $fakerCard;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($customer);
        $this->stripeExternalHelperMock->method('createCardToken')
            ->willReturn($cardToken);
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($cardToken);
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $results = $this->call(
            'PUT',
            '/payment-method',
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card_year' => $cardYear,
                'card_month' => $cardMonth,
                'card_fingerprint' => $cardFingerprint,
                'card_number_last_four_digits' => $cardLast4,
                'company_name' => $cardType,
                'currency' => $currency,
                'user_id' => $userId,
                'gateway' => 'drumeo',
                'card_token' => $cardToken->id,
            ]
        );

        //assert response status code
        $this->assertEquals(200, $results->getStatusCode());

        //assert payment data subset
        $this->assertArraySubset(
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
                'updated_on' => null,
                'currency' => $currency,
                'method' => [
                    'fingerprint' => $cardFingerprint,
                    'last_four_digits' => $cardLast4,
                    'cardholder_name' => '',
                    'company_name' => $cardType,

                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                    'updated_on' => null,
                ],
            ],
            $results->decodeResponseJson()['data'][0]
        );

        //assert payment method, credit card, link between user and payment method saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'currency' => $currency,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $cardFingerprint,
                'last_four_digits' => $cardLast4,
                'company_name' => $cardType,
                'payment_gateway_name' => 'drumeo',
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => 1,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_user_store_paypal_payment_method()
    {
        $userId = $this->createAndLogInNewUser();
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn(rand());
        $expressCheckoutToken = self::VALID_EXPRESS_CHECKOUT_TOKEN;
        $addressId = $this->faker->numberBetween();
        $customerId = null;
        $currency = 'cad';

        $results = $this->call(
            'PUT',
            '/payment-method',
            [
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'token' => $expressCheckoutToken,
                'address_id' => $addressId,
                'user_id' => $userId,
                'customer_id' => $customerId,
                'currency' => $currency,
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
                'updated_on' => null,
                'currency' => $currency,
                'method' => [
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                    'updated_on' => null,
                ],
            ],
            $results->decodeResponseJson()['data'][0]
        );

        //assert payment method, credit card, link between user and payment method saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'currency' => $currency,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'payment_gateway_name' => 'drumeo',
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => 1,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_store_payment_method_credit_card_failed()
    {
        $userId = $this->createAndLogInNewUser();
        $cardExpirationDate =
            Carbon::now()
                ->addYear(2);
        $cardYear = $cardExpirationDate->format('Y');
        $cardMonth = $cardExpirationDate->format('m');
        $cardLast4 = $this->faker->randomNumber(4);
        $cardType = $this->faker->creditCardType;
        $currency = $this->faker->currencyCode;
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $cardFingerprint = self::VALID_VISA_CARD_NUM;
        $customer = new Customer();
        $customer->email = $this->faker->email;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $cardFingerprint;
        $fakerCard->brand = $cardType;
        $fakerCard->last4 = $cardLast4;
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;
        $cardToken = new Token();
        $cardToken->id = rand();
        $cardToken->card = $fakerCard;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($customer);
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($cardToken);

        //incorrect card number
        $cardFingerprint = $this->faker->randomNumber();

        $this->stripeExternalHelperMock->method('createCard')
            ->willThrowException(
                new PaymentFailedException(
                    'The card number is incorrect. Check the card’s number or use a different card.'
                )
            );

        $this->expectException(PaymentFailedException::class);

        $results = $this->call(
            'PUT',
            '/payment-method',
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card_token' => $this->faker->creditCardNumber,
                'currency' => $currency,
                'user_id' => $userId,
                'gateway' => 'drumeo',
                'user_email' => $this->faker->email,
            ]
        );

        //assert error message subset results
        $this->assertArraySubset(
            [
                'title' => 'Not found.',
                'detail' => 'Creation failed, method type(' .
                    PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE .
                    ') not allowed or incorrect data.Can not create token:: The card number is not a valid credit card number.',
            ],
            $results->decodeResponseJson('meta')['errors'][0]
        );

        //assert payment method data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'currency' => $currency,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        //assert credit card data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tableCreditCard,
            [
                'type' => $cardType,
                'fingerprint' => $cardFingerprint,
                'last_four_digits' => $cardLast4,
                'company_name' => $cardType,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_user_store_paypal_payment_method_failed()
    {
        $expressCheckoutToken = $this->faker->numberBetween();
        $addressId = $this->faker->numberBetween();
        $userId = rand();
        $customerId = null;
        $currency = 'cad';
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willThrowException(new PaymentFailedException('Payment failed'));

        $this->expectException(PaymentFailedException::class);
        $results = $this->call(
            'PUT',
            '/payment-method',
            [
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'token' => $expressCheckoutToken,
                'address_id' => $addressId,
                'user_id' => $userId,
                'customer_id' => $customerId,
                'currency' => $currency,
                'gateway' => 'drumeo',
            ]
        );

        //assert error message subset results
        $this->assertArraySubset(
            [
                'title' => 'Not found.',
                // 'detail' => 'Creation failed, method type(' . PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE . ') not allowed or incorrect data.'
            ],
            $results->decodeResponseJson('error')
        );

        //assert payment method data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'currency' => $currency,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        //assert paypal billing agreement data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'express_checkout_token' => $expressCheckoutToken,
                'address_id' => $addressId,
                'expiration_date' => Carbon::now()
                    ->addYears(10)
                    ->toDateTimeString(),
                'payment_gateway_name' => 'drumeo',
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseMissing(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => 1,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_update_payment_method_validation_fail()
    {
        $userId = $this->createAndLogInNewUser();

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $this->faker->randomNumber(4),
            []
        );

        // assert respons status code and errors
        $this->assertEquals(422, $response->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'gateway',
                    'detail' => 'The gateway field is required.',
                ],
                [
                    'source' => 'year',
                    'detail' => 'The year field is required.',
                ],
                [
                    'source' => 'month',
                    'detail' => 'The month field is required.',
                ],
                [
                    'source' => 'country',
                    'detail' => 'The country field is required.',
                ],
            ],
            $response->decodeResponseJson('meta')['errors']
        );
    }

    public function test_update_payment_method_permissions_fail()
    {
        $userId = $this->createAndLogInNewUser();
        $expirationDate = $this->faker->creditCardExpirationDate;

        $payload = [
            'gateway' => 'recordeo',
            'year' => $expirationDate->format('Y'),
            'month' => $expirationDate->format('m'),
            'country' => $this->faker->word,
        ];

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $this->faker->randomNumber(4),
            $payload
        );

        // assert respons status code and errors
        $this->assertEquals(403, $response->getStatusCode());

        $response->assertJsonFragment(
            [
                [
                    'title' => 'Not allowed.',
                    'detail' => 'You cannot update payment methods.',
                ],
            ]
        );
    }

    public function test_update_payment_method_not_found()
    {
        $userId = $this->createAndLogInNewUser();
        $expirationDate = $this->faker->creditCardExpirationDate;

        $payload = [
            'gateway' => 'recordeo',
            'year' => $expirationDate->format('Y'),
            'month' => $expirationDate->format('m'),
            'country' => $this->faker->word,
        ];

        $id = $this->faker->randomNumber(4);

        $this->permissionServiceMock->method('can')
            ->willReturn(true);

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $id,
            $payload
        );

        // assert respons status code and errors
        $this->assertEquals(404, $response->getStatusCode());

        $response->assertJsonFragment(
            [
                [
                    'title' => 'Not found.',
                    'detail' => 'Update failed, payment method not found with id: ' . $id,
                ],
            ]
        );
    }

    public function test_update_payment_method()
    {
        $userId = $this->createAndLogInNewUser();

        $creditCard = $this->creditCardRepository->create(
            $this->faker->creditCard(
                [
                    'payment_gateway_name' => 'recordeo',
                ]
            )
        );

        $billingAddress = $this->addressRepository->create(
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => 'recordeo',
                'user_id' => $userId,
                'state' => '',
                'country' => '',
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => $methodType,
                    'method_id' => $creditCard['id'],
                    'billing_address_id' => $billingAddress->id,
                ]
            )
        );

        $userPaymentMethod = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );

        $expirationDate = $this->faker->creditCardExpirationDate;

        $payload = [
            'gateway' => 'recordeo',
            'year' => $expirationDate->format('Y'),
            'month' => $expirationDate->format('m'),
            'country' => $this->faker->word,
            'state' => $this->faker->word,
        ];

        $this->permissionServiceMock->method('can')
            ->willReturn(true);

        $updatedCard = (object)[
            'fingerprint' => $creditCard->fingerprint,
            'last4' => $creditCard->last_four_digits,
            'name' => $creditCard->cardholder_name,
            'exp_year' => $payload['year'],
            'exp_month' => $payload['month'],
            'id' => $creditCard->external_id,
            'customer' => $creditCard->external_customer_id,
            'address_country' => $payload['country'],
            'address_state' => $payload['state'],
        ];

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $this->stripeExternalHelperMock->method('updateCard')
            ->willReturn($updatedCard);

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            $payload
        );

        // assert respons status code and response
        $this->assertEquals(200, $response->getStatusCode());

        $expirationDate = Carbon::createFromDate(
            $updatedCard->exp_year,
            $updatedCard->exp_month
        )
            ->toDateTimeString();

        $response->assertJsonFragment(
            [
                'fingerprint' => $updatedCard->fingerprint,
                'last_four_digits' => $updatedCard->last4,
                'expiration_date' => $expirationDate,
            ]
        );

        // assert database updates
        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'id' => $creditCard['id'],
                'expiration_date' => $expirationDate,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'id' => $billingAddress->id,
                'state' => $updatedCard->address_state,
                'country' => $updatedCard->address_country,
            ]
        );
    }

    public function test_update_payment_method_update_credit_card_validation()
    {
        $userId = $this->createAndLogInNewUser();
        $creditCard = $this->creditCardRepository->create(
            $this->faker->creditCard(
                [
                    'payment_gateway_name' => 'drumeo',
                ]
            )
        );

        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    'method_id' => $creditCard['id'],
                ]
            )
        );
        $userPaymentMethod = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );

        $results = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => 'update-current-credit-card',
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'user_id' => $userId,
            ]
        );

        //assert results status code and errors
        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(
            [
                [
                    "source" => "gateway",
                    "detail" => "The gateway field is required.",
                ],
                [
                    "source" => "year",
                    "detail" => "The year field is required.",
                ],
                [
                    "source" => "month",
                    "detail" => "The month field is required.",
                ],
                [
                    "source" => "country",
                    "detail" => "The country field is required.",
                ],
            ],
            $results->decodeResponseJson('meta')['errors']
        );
    }

    public function test_update_payment_method_use_paypal_validation()
    {
        $userId = $this->createAndLogInNewUser();
        $creditCard = $this->creditCardRepository->create(
            $this->faker->creditCard(
                [
                    'payment_gateway_name' => 'recordeo',
                ]
            )
        );

        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    'method_id' => $creditCard['id'],
                ]
            )
        );

        $results = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => 'use-paypal',
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'payment_gateway' => 'recordeo',
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals(
            [
                [
                    "source" => "token",
                    "detail" => "The token field is required when update method is use-paypal.",
                ],
                [
                    "source" => "address_id",
                    "detail" => "The address id field is required when update payment method and use paypal.",
                ],
            ],
            $results->decodeResponseJson('meta')['errors']
        );

        //assert payment method data not updated in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
                'method_id' => $creditCard['id'],
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            ]
        );
    }

    public function test_user_update_payment_method_create_credit_card_response()
    {
        $userId = $this->createAndLogInNewUser();
        $expirationDate = $this->faker->creditCardExpirationDate;
        $cardFingerprint = self::VALID_VISA_CARD_NUM;
        $cardLast4 = $this->faker->randomNumber(4);
        $cardType = $this->faker->creditCardType;
        $cardHolderName = $this->faker->word;

        $userId = $this->createAndLogInNewUser();

        $customer = new Customer();
        $customer->email = $this->faker->email;
        $fakerCard = new Card();
        $fakerCard->fingerprint = $cardFingerprint;
        $fakerCard->brand = $cardType;
        $fakerCard->last4 = $cardLast4;
        $fakerCard->exp_year = $expirationDate->format('Y');
        $fakerCard->exp_month = $expirationDate->format('m');
        $fakerCard->id = $this->faker->word;
        $cardToken = new Token();
        $cardToken->id = rand();
        $cardToken->card = $fakerCard;
        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($customer);
        $this->stripeExternalHelperMock->method('createCardToken')
            ->willReturn($cardToken);
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($cardToken);
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $creditCard = $this->creditCardRepository->create(
            $this->faker->creditCard(
                [
                    'payment_gateway_name' => 'drumeo',
                ]
            )
        );

        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    'method_id' => $creditCard['id'],
                ]
            )
        );

        $userPaymentMethod = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );

        $results = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => 'create-credit-card',
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card_token' => $cardToken->id,
                'user_id' => $userId,
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset(
            [
                'id' => $paymentMethod['id'],
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'created_on' => $paymentMethod['created_on'],
                'updated_on' => Carbon::now()
                    ->toDateTimeString(),
            ],
            $results->decodeResponseJson()['data'][0]
        );

        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
                'method_id' => $creditCard['id'],
            ]
        );
    }

    public function test_update_payment_method_use_paypal()
    {
        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn(rand());
        $userId = $this->createAndLogInNewUser();
        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());

        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    'method_id' => $creditCard['id'],
                ]
            )
        );
        $userPaymentMethod = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );

        $expressCheckoutToken = self::VALID_EXPRESS_CHECKOUT_TOKEN;

        $addressId = rand();

        $results = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => 'use-paypal',
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'token' => $expressCheckoutToken,
                'address_id' => $addressId,
                'gateway' => 'drumeo',
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset(
            [
                'id' => $paymentMethod['id'],
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'created_on' => $paymentMethod['created_on'],
                'updated_on' => Carbon::now()
                    ->toDateTimeString(),
                'currency' => $paymentMethod['currency'],
            ],
            $results->decodeResponseJson()['data'][0]
        );

        //assert data updated in db
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            ]
        );
        $this->assertDatabaseHas(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'payment_gateway_name' => 'drumeo',
            ]
        );
    }

    public function test_delete_payment_method_not_authenticated_user()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')
            ->willThrowException(
                new NotAllowedException('This action is unauthorized.')
            );

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    'method_id' => $creditCard['id'],
                ]
            )
        );

        $userPaymentMethod = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ],
            $results->decodeResponseJson('meta')['errors']
        );

        //assert payment method still exist in db
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
            ]
        );
    }

    public function test_user_delete_payment_method_credit_card()
    {
        $userId = $this->createAndLogInNewUser();

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());

        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    'method_id' => $creditCard['id'],
                ]
            )
        );

        $assignPaymentMethodToUser = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
                'method_type' => $paymentMethod['method_type'],
                'method_id' => 1,
                'deleted_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_delete_payment_method_paypal()
    {
        $userId = $this->createAndLogInNewUser();

        $paypalBilling = $this->paypalBillingAgreementRepository->create(
            $this->faker->paypalBillingAgreement(
                [
                    'payment_gateway_name' => 'drumeo',
                ]
            )
        );

        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                    'method_id' => $paypalBilling['id'],
                ]
            )
        );

        $assignPaymentMethodToUser = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
                'method_type' => $paymentMethod['method_type'],
                'method_id' => 1,
                'deleted_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );
    }

    public function test_admin_delete_payment_method()
    {
        $userId = $this->createAndLogInNewUser();
        $this->permissionServiceMock->method('canOrThrow')
            ->willReturn(true);

        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    'method_id' => $creditCard['id'],
                ]
            )
        );

        $userPaymentMethod = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => rand(),
                    'payment_method_id' => $paymentMethod['id'],
                ]
            )
        );

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertSoftDeleted(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
            ]
        );
    }

    public function test_get_user_payment_methods()
    {
        $userId = $this->faker->numberBetween();
        $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
        $paymentMethod1 = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                    'method_id' => $creditCard['id'],
                ]
            )
        );
        $paymentMethod1['user'] = [
            'user_id' => $userId,
            'is_primary' => 1,
        ];
        $paymentMethod1['user_id'] = $userId;
        $paypalBilling = $this->paypalBillingAgreementRepository->create(
            $this->faker->paypalBillingAgreement(
                [
                    'payment_gateway_name' => 'drumeo',
                ]
            )
        );

        $paymentMethod2 = $this->paymentMethodRepository->create(
            $this->faker->paymentMethod(
                [
                    'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                    'method_id' => $paypalBilling['id'],
                ]
            )
        );
        $paymentMethod2['user'] = [
            'user_id' => $userId,
            'is_primary' => 1,
        ];
        $paymentMethod2['user_id'] = $userId;

        $assignPaymentMethodToUser = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod1['id'],
                    'is_primary' => 1,
                ]
            )
        );
        $assignPaymentMethodToUser['payment_method'] = (array)$paymentMethod1;
        $userPaymentMethod = $this->userPaymentMethodRepository->create(
            $this->faker->userPaymentMethod(
                [
                    'user_id' => $userId,
                    'payment_method_id' => $paymentMethod2['id'],
                    'is_primary' => 1,
                ]
            )
        );
        $userPaymentMethod['payment_method'] = (array)$paymentMethod2;
        $results = $this->call('GET', '/user-payment-method/' . $userId);

        $this->assertEquals(200, $results->getStatusCode());
        $this->assertEquals(
            [$assignPaymentMethodToUser->getArrayCopy(), $userPaymentMethod->getArrayCopy()],
            $results->decodeResponseJson('data')
        );
    }

    public function test_get_user_payment_methods_not_exists()
    {
        $results = $this->call('GET', '/user-payment-method/' . rand());

        $this->assertEmpty($results->decodeResponseJson('data'));
    }
}
