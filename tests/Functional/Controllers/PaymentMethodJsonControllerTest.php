<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Events\PaypalPaymentMethodEvent;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Stripe\Card;
use Stripe\Customer;
use Stripe\Token;

class PaymentMethodJsonControllerTest extends EcommerceTestCase
{
    CONST VALID_VISA_CARD_NUM = '4242424242424242';
    CONST VALID_EXPRESS_CHECKOUT_TOKEN = 'EC-84G07962U40732257';

    protected function setUp()
    {
        parent::setUp();
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
                    'source' => 'card_token',
                    'detail' => 'The card token field is required when method type is credit-card.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'gateway',
                    'detail' => 'The gateway field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'user_id',
                    'detail' => 'The user id field is required when customer id is not present.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'customer_id',
                    'detail' => 'The customer id field is required when user id is not present.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
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
                    'source' => 'method_type',
                    'detail' => 'The method type field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'user_email',
                    'detail' => 'The user email field is required when customer id is not present.',
                    'title' => 'Validation failed.'
                ],
            ],
            $results->decodeResponseJson('errors')
        );
    }

    public function test_user_store_credit_card_payment_method_set_default()
    {
        Event::fake();

        $userId = $this->createAndLogInNewUser();

        $customer = $this->fakeUser();

        $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'is_primary' => true
        ]);

        $this->permissionServiceMock->method('can')->willReturn(true);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;

        $currency = $this->faker->currencyCode;

        $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

        $gateway = 'recordeo';

        $card = (object)[
            'country' => 'US',
            'fingerprint' => $this->faker->word,
            'last4' => $this->faker->randomNumber(4),
            'name' => $this->faker->word,
            'brand' => $this->faker->creditCardType,
            'exp_year' => $cardExpirationDate->format('Y'),
            'exp_month' => $cardExpirationDate->format('m'),
            'id' => $this->faker->word,
            'customer' => $this->faker->word,
        ];

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => [new Customer()]]);
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn(new Token());
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($card);

        $payload = [
            'card_token' => 'tok_mastercard',
            'gateway' => $gateway,
            'method_type' => $methodType,
            'currency' => $currency,
            'set_default' => true,
            'user_email' => $customer['email'],
            'user_id' => $customer['id']
        ];

        $results = $this->call(
            'PUT',
            '/payment-method',
            $payload
        );

        $paymentResponse = $results->decodeResponseJson();

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'paymentMethod',
                    'attributes' => [
                        'method_type' => $payload['method_type'],
                        'currency' => $currency,
                        'created_at' => Carbon::now()->toDateTimeString()
                    ],
                    'relationships' => [
                        'billingAddress' => [
                            'data' => [
                                'type' => 'address',
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $customer['id']
                    ],
                    [
                        'type' => 'address',
                    ]
                ]
            ],
            $paymentResponse
        );

        // assert event raised and test the ids from event
        Event::assertDispatched(
            UserDefaultPaymentMethodEvent::class,
            function ($e) use ($paymentResponse, $customer) {
                return $paymentResponse['data']['id'] == $e->getDefaultPaymentMethodId() &&
                    $customer['id'] == $e->getUserId();
            }
        );

        // assert payment method, credit card, link between user and payment method saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => $methodType,
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $card->fingerprint,
                'last_four_digits' => $card->last4,
                'company_name' => $card->brand,
                'payment_gateway_name' => $gateway,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $customer['id'],
                'payment_method_id' => $paymentResponse['data']['id'],
                'created_at' => Carbon::now()->toDateTimeString(),
                'is_primary' => 1,
            ]
        );
    }

    public function test_user_store_credit_card_payment_method_not_default()
    {
        Event::fake();

        $userId = $this->createAndLogInNewUser();

        $customer = $this->fakeUser();

        $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'is_primary' => true
        ]);

        $this->permissionServiceMock->method('can')->willReturn(true);

        $cardExpirationDate = $this->faker->creditCardExpirationDate;

        $currency = $this->faker->currencyCode;

        $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

        $gateway = 'recordeo';

        $card = (object)[
            'country' => 'US',
            'fingerprint' => $this->faker->word,
            'last4' => $this->faker->randomNumber(4),
            'name' => $this->faker->word,
            'brand' => $this->faker->creditCardType,
            'exp_year' => $cardExpirationDate->format('Y'),
            'exp_month' => $cardExpirationDate->format('m'),
            'id' => $this->faker->word,
            'customer' => $this->faker->word,
        ];

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => [new Customer()]]);
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn(new Token());
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($card);

        $payload = [
            'card_token' => 'tok_mastercard',
            'gateway' => $gateway,
            'method_type' => $methodType,
            'currency' => $currency,
            'set_default' => false,
            'user_email' => $customer['email'],
            'user_id' => $customer['id']
        ];

        $results = $this->call(
            'PUT',
            '/payment-method',
            $payload
        );

        $paymentResponse = $results->decodeResponseJson();

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'paymentMethod',
                    'attributes' => [
                        'method_type' => $payload['method_type'],
                        'currency' => $currency,
                        'created_at' => Carbon::now()->toDateTimeString()
                    ],
                    'relationships' => [
                        'billingAddress' => [
                            'data' => [
                                'type' => 'address',
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'user',
                        'id' => $customer['id']
                    ],
                    [
                        'type' => 'address',
                    ]
                ]
            ],
            $paymentResponse
        );

        // assert event raised and test the ids from event
        Event::assertNotDispatched(
            UserDefaultPaymentMethodEvent::class,
            function ($e) use ($paymentResponse, $customer) {
                return $paymentResponse['data']['id'] == $e->getDefaultPaymentMethodId() &&
                    $customer['id'] == $e->getUserId();
            }
        );

        // assert payment method, credit card, link between user and payment method saved in the db
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => $methodType,
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $card->fingerprint,
                'last_four_digits' => $card->last4,
                'company_name' => $card->brand,
                'payment_gateway_name' => $gateway,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $customer['id'],
                'payment_method_id' => $paymentResponse['data']['id'],
                'created_at' => Carbon::now()->toDateTimeString(),
                'is_primary' => 0,
            ]
        );
    }

    public function test_store_payment_method_credit_card_failed()
    {
        $userId = $this->createAndLogInNewUser();
        $cardExpirationDate = Carbon::now()->addYear(2);
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

        // incorrect card number
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

        // assert error message subset results
        $this->assertArraySubset(
            [
                'title' => 'Not found.',
                'detail' => 'Creation failed, method type(' .
                    PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE .
                    ') not allowed or incorrect data.Can not create token:: The card number is not a valid credit card number.',
            ],
            $results->decodeResponseJson('meta')['errors'][0]
        );

        // assert payment method data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'currency' => $currency,
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        // assert credit card data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tableCreditCard,
            [
                'type' => $cardType,
                'fingerprint' => $cardFingerprint,
                'last_four_digits' => $cardLast4,
                'company_name' => $cardType,
                'created_on' => Carbon::now()->toDateTimeString(),
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
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'year',
                    'detail' => 'The year field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'month',
                    'detail' => 'The month field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'country',
                    'detail' => 'The country field is required.',
                    'title' => 'Validation failed.'
                ],
            ],
            $response->decodeResponseJson('errors')
        );
    }

    // public function test_update_payment_method_permissions_fail()
    // {
    //     $userId = $this->createAndLogInNewUser();
    //     $expirationDate = $this->faker->creditCardExpirationDate;

    //     $payload = [
    //         'gateway' => 'recordeo',
    //         'year' => $expirationDate->format('Y'),
    //         'month' => $expirationDate->format('m'),
    //         'country' => $this->faker->word,
    //     ];

    //     $exceptionMessage = 'You are not allowed to update payment method';

    //     $this->permissionServiceMock->method('canOrThrow')
    //         ->willThrowException(
    //             new NotAllowedException($exceptionMessage)
    //         );

    //     $response = $this->call(
    //         'PATCH',
    //         '/payment-method/' . $this->faker->randomNumber(4),
    //         $payload
    //     );

    //     // assert respons status code and errors
    //     $this->assertEquals(403, $response->getStatusCode());

    //     $response->assertJsonFragment(
    //         [
    //             'detail' => $exceptionMessage,
    //             'title' => 'Not allowed.',
    //         ]
    //     );
    // }

    // public function test_update_payment_method_not_found()
    // {
    //     $userId = $this->createAndLogInNewUser();
    //     $expirationDate = $this->faker->creditCardExpirationDate;

    //     $payload = [
    //         'gateway' => 'recordeo',
    //         'year' => $expirationDate->format('Y'),
    //         'month' => $expirationDate->format('m'),
    //         'country' => $this->faker->word,
    //     ];

    //     $id = $this->faker->randomNumber(4);

    //     $this->permissionServiceMock->method('can')
    //         ->willReturn(true);

    //     $response = $this->call(
    //         'PATCH',
    //         '/payment-method/' . $id,
    //         $payload
    //     );

    //     // assert respons status code and errors
    //     $this->assertEquals(404, $response->getStatusCode());

    //     $response->assertJsonFragment(
    //         [
    //             [
    //                 'title' => 'Not found.',
    //                 'detail' => 'Update failed, payment method not found with id: ' . $id,
    //             ],
    //         ]
    //     );
    // }

    // public function test_update_payment_method()
    // {
    //     $userId = $this->createAndLogInNewUser();

    //     $creditCard = $this->creditCardRepository->create(
    //         $this->faker->creditCard(
    //             [
    //                 'payment_gateway_name' => 'recordeo',
    //             ]
    //         )
    //     );

    //     $billingAddress = $this->addressRepository->create(
    //         [
    //             'type' => CartAddressService::BILLING_ADDRESS_TYPE,
    //             'brand' => 'recordeo',
    //             'user_id' => $userId,
    //             'state' => '',
    //             'country' => '',
    //             'created_on' => Carbon::now()
    //                 ->toDateTimeString(),
    //         ]
    //     );

    //     $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => $methodType,
    //                 'method_id' => $creditCard['id'],
    //                 'billing_address_id' => $billingAddress->id,
    //             ]
    //         )
    //     );

    //     $userPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $paymentMethod['id'],
    //             ]
    //         )
    //     );

    //     $expirationDate = $this->faker->creditCardExpirationDate;

    //     $payload = [
    //         'gateway' => 'recordeo',
    //         'year' => $expirationDate->format('Y'),
    //         'month' => $expirationDate->format('m'),
    //         'country' => $this->faker->word,
    //         'state' => $this->faker->word,
    //     ];

    //     $this->permissionServiceMock->method('can')
    //         ->willReturn(true);

    //     $updatedCard = (object)[
    //         'fingerprint' => $creditCard->fingerprint,
    //         'last4' => $creditCard->last_four_digits,
    //         'name' => $creditCard->cardholder_name,
    //         'exp_year' => $payload['year'],
    //         'exp_month' => $payload['month'],
    //         'id' => $creditCard->external_id,
    //         'customer' => $creditCard->external_customer_id,
    //         'address_country' => $payload['country'],
    //         'address_state' => $payload['state'],
    //     ];

    //     $this->stripeExternalHelperMock->method('retrieveCustomer')
    //         ->willReturn(new Customer());
    //     $this->stripeExternalHelperMock->method('retrieveCard')
    //         ->willReturn(new Card());
    //     $this->stripeExternalHelperMock->method('updateCard')
    //         ->willReturn($updatedCard);

    //     $response = $this->call(
    //         'PATCH',
    //         '/payment-method/' . $paymentMethod['id'],
    //         $payload
    //     );

    //     // assert respons status code and response
    //     $this->assertEquals(200, $response->getStatusCode());

    //     $expirationDate = Carbon::createFromDate(
    //         $updatedCard->exp_year,
    //         $updatedCard->exp_month
    //     )
    //         ->toDateTimeString();

    //     $response->assertJsonFragment(
    //         [
    //             'fingerprint' => $updatedCard->fingerprint,
    //             'last_four_digits' => $updatedCard->last4,
    //             'expiration_date' => $expirationDate,
    //         ]
    //     );

    //     // assert database updates
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableCreditCard,
    //         [
    //             'id' => $creditCard['id'],
    //             'expiration_date' => $expirationDate,
    //         ]
    //     );

    //     $this->assertDatabaseHas(
    //         ConfigService::$tableAddress,
    //         [
    //             'id' => $billingAddress->id,
    //             'state' => $updatedCard->address_state,
    //             'country' => $updatedCard->address_country,
    //         ]
    //     );
    // }

    // function test_update_payment_method_not_default()
    // {
    //     $userId = $this->createAndLogInNewUser();
    //     $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

    //     // default payment method
    //     $defaultCreditCard = $this->creditCardRepository->create(
    //         $this->faker->creditCard(
    //             [
    //                 'payment_gateway_name' => 'recordeo',
    //             ]
    //         )
    //     );

    //     $defaultBillingAddress = $this->addressRepository->create(
    //         [
    //             'type' => CartAddressService::BILLING_ADDRESS_TYPE,
    //             'brand' => 'recordeo',
    //             'user_id' => $userId,
    //             'state' => '',
    //             'country' => '',
    //             'created_on' => Carbon::now()
    //                 ->toDateTimeString(),
    //         ]
    //     );

    //     $defaultPaymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => $methodType,
    //                 'method_id' => $defaultCreditCard['id'],
    //                 'billing_address_id' => $defaultBillingAddress->id,
    //             ]
    //         )
    //     );

    //     $defaultUserPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $defaultPaymentMethod['id'],
    //                 'is_primary' => 1
    //             ]
    //         )
    //     );

    //     // other non-default payment method
    //     $creditCard = $this->creditCardRepository->create(
    //         $this->faker->creditCard(
    //             [
    //                 'payment_gateway_name' => 'recordeo',
    //             ]
    //         )
    //     );

    //     $billingAddress = $this->addressRepository->create(
    //         [
    //             'type' => CartAddressService::BILLING_ADDRESS_TYPE,
    //             'brand' => 'recordeo',
    //             'user_id' => $userId,
    //             'state' => '',
    //             'country' => '',
    //             'created_on' => Carbon::now()
    //                 ->toDateTimeString(),
    //         ]
    //     );

    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => $methodType,
    //                 'method_id' => $creditCard['id'],
    //                 'billing_address_id' => $billingAddress->id,
    //             ]
    //         )
    //     );

    //     $userPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $paymentMethod['id'],
    //                 'is_primary' => 0
    //             ]
    //         )
    //     );

    //     $expirationDate = $this->faker->creditCardExpirationDate;

    //     $payload = [
    //         'gateway' => 'recordeo',
    //         'year' => $expirationDate->format('Y'),
    //         'month' => $expirationDate->format('m'),
    //         'country' => $this->faker->word,
    //         'state' => $this->faker->word,
    //     ];

    //     $this->permissionServiceMock->method('can')
    //         ->willReturn(true);

    //     $updatedCard = (object)[
    //         'fingerprint' => $creditCard->fingerprint,
    //         'last4' => $creditCard->last_four_digits,
    //         'name' => $creditCard->cardholder_name,
    //         'exp_year' => $payload['year'],
    //         'exp_month' => $payload['month'],
    //         'id' => $creditCard->external_id,
    //         'customer' => $creditCard->external_customer_id,
    //         'address_country' => $payload['country'],
    //         'address_state' => $payload['state'],
    //     ];

    //     $this->stripeExternalHelperMock->method('retrieveCustomer')
    //         ->willReturn(new Customer());
    //     $this->stripeExternalHelperMock->method('retrieveCard')
    //         ->willReturn(new Card());
    //     $this->stripeExternalHelperMock->method('updateCard')
    //         ->willReturn($updatedCard);

    //     $response = $this->call(
    //         'PATCH',
    //         '/payment-method/' . $paymentMethod['id'],
    //         $payload
    //     );

    //     // assert respons status code and response
    //     $this->assertEquals(200, $response->getStatusCode());

    //     $expirationDate = Carbon::createFromDate(
    //         $updatedCard->exp_year,
    //         $updatedCard->exp_month
    //     )
    //         ->toDateTimeString();

    //     $response->assertJsonFragment(
    //         [
    //             'fingerprint' => $updatedCard->fingerprint,
    //             'last_four_digits' => $updatedCard->last4,
    //             'expiration_date' => $expirationDate,
    //         ]
    //     );

    //     // assert database updates
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableCreditCard,
    //         [
    //             'id' => $creditCard['id'],
    //             'expiration_date' => $expirationDate,
    //         ]
    //     );

    //     $this->assertDatabaseHas(
    //         ConfigService::$tableAddress,
    //         [
    //             'id' => $billingAddress->id,
    //             'state' => $updatedCard->address_state,
    //             'country' => $updatedCard->address_country,
    //         ]
    //     );

    //     // assert payment method was not set as default
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableUserPaymentMethods,
    //         [
    //             'user_id' => $userId,
    //             'payment_method_id' => $paymentMethod['id'],
    //             'is_primary' => 0,
    //         ]
    //     );
    // }

    // public function test_update_payment_method_set_default()
    // {
    //     Event::fake();

    //     $userId = $this->createAndLogInNewUser();

    //     $creditCard = $this->creditCardRepository->create(
    //         $this->faker->creditCard(
    //             [
    //                 'payment_gateway_name' => 'recordeo',
    //             ]
    //         )
    //     );

    //     $billingAddress = $this->addressRepository->create(
    //         [
    //             'type' => CartAddressService::BILLING_ADDRESS_TYPE,
    //             'brand' => 'recordeo',
    //             'user_id' => $userId,
    //             'state' => '',
    //             'country' => '',
    //             'created_on' => Carbon::now()
    //                 ->toDateTimeString(),
    //         ]
    //     );

    //     $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => $methodType,
    //                 'method_id' => $creditCard['id'],
    //                 'billing_address_id' => $billingAddress->id,
    //             ]
    //         )
    //     );

    //     $userPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $paymentMethod['id'],
    //             ]
    //         )
    //     );

    //     $expirationDate = $this->faker->creditCardExpirationDate;

    //     $payload = [
    //         'gateway' => 'recordeo',
    //         'year' => $expirationDate->format('Y'),
    //         'month' => $expirationDate->format('m'),
    //         'country' => $this->faker->word,
    //         'state' => $this->faker->word,
    //         'set_default' => true
    //     ];

    //     $this->permissionServiceMock->method('can')
    //         ->willReturn(true);

    //     $updatedCard = (object)[
    //         'fingerprint' => $creditCard->fingerprint,
    //         'last4' => $creditCard->last_four_digits,
    //         'name' => $creditCard->cardholder_name,
    //         'exp_year' => $payload['year'],
    //         'exp_month' => $payload['month'],
    //         'id' => $creditCard->external_id,
    //         'customer' => $creditCard->external_customer_id,
    //         'address_country' => $payload['country'],
    //         'address_state' => $payload['state'],
    //     ];

    //     $this->stripeExternalHelperMock->method('retrieveCustomer')
    //         ->willReturn(new Customer());
    //     $this->stripeExternalHelperMock->method('retrieveCard')
    //         ->willReturn(new Card());
    //     $this->stripeExternalHelperMock->method('updateCard')
    //         ->willReturn($updatedCard);

    //     $response = $this->call(
    //         'PATCH',
    //         '/payment-method/' . $paymentMethod['id'],
    //         $payload
    //     );

    //     // assert respons status code and response
    //     $this->assertEquals(200, $response->getStatusCode());

    //     // assert event raised and test the ids from event
    //     Event::assertDispatched(
    //         UserDefaultPaymentMethodEvent::class,
    //         function ($e) use ($paymentMethod, $userId) {
    //             return $paymentMethod['id'] == $e->getDefaultPaymentMethodId() &&
    //                 $userId == $e->getUserId();
    //         }
    //     );

    //     $expirationDate = Carbon::createFromDate(
    //         $updatedCard->exp_year,
    //         $updatedCard->exp_month
    //     )
    //         ->toDateTimeString();

    //     $response->assertJsonFragment(
    //         [
    //             'fingerprint' => $updatedCard->fingerprint,
    //             'last_four_digits' => $updatedCard->last4,
    //             'expiration_date' => $expirationDate,
    //         ]
    //     );

    //     // assert database updates
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableCreditCard,
    //         [
    //             'id' => $creditCard['id'],
    //             'expiration_date' => $expirationDate,
    //         ]
    //     );

    //     $this->assertDatabaseHas(
    //         ConfigService::$tableAddress,
    //         [
    //             'id' => $billingAddress->id,
    //             'state' => $updatedCard->address_state,
    //             'country' => $updatedCard->address_country,
    //         ]
    //     );

    //     // assert payment method was set as default
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableUserPaymentMethods,
    //         [
    //             'user_id' => $userId,
    //             'payment_method_id' => $paymentMethod['id'],
    //             'is_primary' => 1,
    //         ]
    //     );
    // }

    // public function test_update_payment_method_update_subscription()
    // {
    //     $userId = $this->createAndLogInNewUser();

    //     $creditCard = $this->creditCardRepository->create(
    //         $this->faker->creditCard(
    //             [
    //                 'payment_gateway_name' => 'recordeo',
    //             ]
    //         )
    //     );

    //     $billingAddress = $this->addressRepository->create(
    //         [
    //             'type' => CartAddressService::BILLING_ADDRESS_TYPE,
    //             'brand' => 'recordeo',
    //             'user_id' => $userId,
    //             'state' => '',
    //             'country' => '',
    //             'created_on' => Carbon::now()
    //                 ->toDateTimeString(),
    //         ]
    //     );

    //     $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => $methodType,
    //                 'method_id' => $creditCard['id'],
    //                 'billing_address_id' => $billingAddress->id,
    //             ]
    //         )
    //     );

    //     $userPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $paymentMethod['id'],
    //             ]
    //         )
    //     );

    //     $subscriptionInitialPaymentMethodId = $this->faker->randomNumber();

    //     // add a subscription
    //     $subscription = $this->subscriptionRepository->create(
    //         $this->faker->subscription([
    //             'user_id' => $userId,
    //             'payment_method_id' => $subscriptionInitialPaymentMethodId
    //         ])
    //     );

    //     $expirationDate = $this->faker->creditCardExpirationDate;

    //     $payload = [
    //         'gateway' => 'recordeo',
    //         'year' => $expirationDate->format('Y'),
    //         'month' => $expirationDate->format('m'),
    //         'country' => $this->faker->word,
    //         'state' => $this->faker->word,
    //         'set_default' => true
    //     ];

    //     $this->permissionServiceMock->method('can')
    //         ->willReturn(true);

    //     $updatedCard = (object)[
    //         'fingerprint' => $creditCard->fingerprint,
    //         'last4' => $creditCard->last_four_digits,
    //         'name' => $creditCard->cardholder_name,
    //         'exp_year' => $payload['year'],
    //         'exp_month' => $payload['month'],
    //         'id' => $creditCard->external_id,
    //         'customer' => $creditCard->external_customer_id,
    //         'address_country' => $payload['country'],
    //         'address_state' => $payload['state'],
    //     ];

    //     $this->stripeExternalHelperMock->method('retrieveCustomer')
    //         ->willReturn(new Customer());
    //     $this->stripeExternalHelperMock->method('retrieveCard')
    //         ->willReturn(new Card());
    //     $this->stripeExternalHelperMock->method('updateCard')
    //         ->willReturn($updatedCard);

    //     $response = $this->call(
    //         'PATCH',
    //         '/payment-method/' . $paymentMethod['id'],
    //         $payload
    //     );

    //     // assert respons status code and response
    //     $this->assertEquals(200, $response->getStatusCode());

    //     $expirationDate = Carbon::createFromDate(
    //         $updatedCard->exp_year,
    //         $updatedCard->exp_month
    //     )
    //         ->toDateTimeString();

    //     $response->assertJsonFragment(
    //         [
    //             'fingerprint' => $updatedCard->fingerprint,
    //             'last_four_digits' => $updatedCard->last4,
    //             'expiration_date' => $expirationDate,
    //         ]
    //     );

    //     // assert database updates
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableCreditCard,
    //         [
    //             'id' => $creditCard['id'],
    //             'expiration_date' => $expirationDate,
    //         ]
    //     );

    //     $this->assertDatabaseHas(
    //         ConfigService::$tableAddress,
    //         [
    //             'id' => $billingAddress->id,
    //             'state' => $updatedCard->address_state,
    //             'country' => $updatedCard->address_country,
    //         ]
    //     );

    //     // assert payment method was set as default
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableUserPaymentMethods,
    //         [
    //             'user_id' => $userId,
    //             'payment_method_id' => $paymentMethod['id'],
    //             'is_primary' => 1,
    //         ]
    //     );

    //     // assert the new payment method was set as subscription payment method
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableSubscription,
    //         [
    //             'user_id' => $userId,
    //             'payment_method_id' => $paymentMethod['id'],
    //         ]
    //     );
    // }

    public function test_set_default()
    {
        Event::fake();

        $userId = $this->createAndLogInNewUser();

        $creditCard = $this->fakeCreditCard([
            'payment_gateway_name' => 'recordeo',
        ]);

        $address = $this->fakeAddress([
            'type' => CartAddressService::BILLING_ADDRESS_TYPE,
            'brand' => 'recordeo',
            'user_id' => $userId,
            'state' => '',
            'country' => '',
            'created_at' => Carbon::now()
        ]);

        $creditCard = $this->fakeCreditCard([
            'payment_gateway_name' => 'recordeo',
        ]);

        $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

        $paymentMethod = $this->fakePaymentMethod([
            'method_type' => $methodType,
            'method_id' => $creditCard['id'],
            'billing_address_id' => $address['id'],
        ]);

        $otherPaymentMethod = $this->fakePaymentMethod([
            'method_type' => $methodType,
            'method_id' => $creditCard['id'],
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => false,
        ]);

        $otherUserPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $otherPaymentMethod['id'],
            'is_primary' => true
        ]);

        $response = $this->call(
            'PATCH',
            '/payment-method/set-default',
            ['id' => $paymentMethod['id']]
        );

        $this->assertEquals(204, $response->getStatusCode());

        // assert event raised and test the ids from event
        Event::assertDispatched(
            UserDefaultPaymentMethodEvent::class,
            function ($e) use ($userPaymentMethod, $userId) {
                return $userPaymentMethod['id'] == $e->getDefaultPaymentMethodId() &&
                    $userId == $e->getUserId();
            }
        );

        // assert database updates - old defaultPaymentMethod is not primary
        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethod['id'],
                'is_primary' => 1,
            ]
        );

        // assert payment method was set as default
        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => $otherUserPaymentMethod['id'],
                'is_primary' => 0,
            ]
        );
    }

    // public function test_set_default_update_subscription()
    // {
    //     $userId = $this->createAndLogInNewUser();

    //     $defaultCreditCard = $this->creditCardRepository->create(
    //         $this->faker->creditCard(
    //             [
    //                 'payment_gateway_name' => ConfigService::$brand,
    //             ]
    //         )
    //     );

    //     $otherCreditCard = $this->creditCardRepository->create(
    //         $this->faker->creditCard(
    //             [
    //                 'payment_gateway_name' => ConfigService::$brand,
    //             ]
    //         )
    //     );

    //     $billingAddress = $this->addressRepository->create(
    //         [
    //             'type' => CartAddressService::BILLING_ADDRESS_TYPE,
    //             'brand' => ConfigService::$brand,
    //             'user_id' => $userId,
    //             'state' => '',
    //             'country' => '',
    //             'created_on' => Carbon::now()
    //                 ->toDateTimeString(),
    //         ]
    //     );

    //     $methodType = PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE;

    //     $defaultPaymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => $methodType,
    //                 'method_id' => $defaultCreditCard['id'],
    //                 'billing_address_id' => $billingAddress->id,
    //             ]
    //         )
    //     );

    //     $otherPaymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => $methodType,
    //                 'method_id' => $otherCreditCard['id'],
    //                 'billing_address_id' => $billingAddress->id,
    //             ]
    //         )
    //     );

    //     $defaultUserPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $defaultPaymentMethod['id'],
    //                 'is_primary' => 1,
    //             ]
    //         )
    //     );

    //     // add a subscription
    //     $subscription = $this->subscriptionRepository->create(
    //         $this->faker->subscription([
    //             'user_id' => $userId,
    //             'payment_method_id' => $defaultUserPaymentMethod['id']
    //         ])
    //     );

    //     $otherUserPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $otherPaymentMethod['id'],
    //                 'is_primary' => 0,
    //             ]
    //         )
    //     );

    //     $expirationDate = $this->faker->creditCardExpirationDate;

    //     $payload = [
    //         'id' => $otherUserPaymentMethod['id']
    //     ];

    //     $this->permissionServiceMock->method('canOrThrow')
    //         ->willReturn(true);

    //     $response = $this->call(
    //         'PATCH',
    //         '/payment-method/set-default',
    //         $payload
    //     );

    //     // assert respons status code
    //     $this->assertEquals(200, $response->getStatusCode());

    //     // assert database updates - old defaultPaymentMethod is not primary
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableUserPaymentMethods,
    //         [
    //             'user_id' => $userId,
    //             'payment_method_id' => $defaultPaymentMethod['id'],
    //             'is_primary' => 0,
    //         ]
    //     );

    //     // assert payment method was set as default
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableUserPaymentMethods,
    //         [
    //             'user_id' => $userId,
    //             'payment_method_id' => $otherUserPaymentMethod['id'],
    //             'is_primary' => 1,
    //         ]
    //     );

    //     // assert the new payment method was set as subscription payment method
    //     $this->assertDatabaseHas(
    //         ConfigService::$tableSubscription,
    //         [
    //             'user_id' => $userId,
    //             'payment_method_id' => $otherUserPaymentMethod['id'],
    //         ]
    //     );
    // }

    public function test_get_paypal_url()
    {
        $userId = $this->createAndLogInNewUser();

        $redirectToken = $this->faker->word;

        $this->paypalExternalHelperMock->method('createBillingAgreementExpressCheckoutToken')
            ->willReturn($redirectToken);

        $response = $this->call('GET', '/payment-method/paypal-url');

        // assert respons status code and response
        $this->assertEquals(200, $response->getStatusCode());

        // assert the redirect token is present in the response redirect url
        $this->assertContains($redirectToken, $response->decodeResponseJson('url'));
    }

    public function test_paypal_agreement()
    {
        Event::fake();
        $userId = $this->createAndLogInNewUser();

        $agreementToken = $this->faker->word;
        $agreementId = $this->faker->word;

        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn($agreementId);

        $response = $this->call(
            'GET',
            '/payment-method/paypal-agreement',
            ['token' => $agreementToken]
        );

        // assert respons status code and response
        $this->assertEquals(204, $response->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => ConfigService::$brand,
                'user_id' => $userId
            ]
        );

        $addressId = 1;

        // assert database updates
        $this->assertDatabaseHas(
            ConfigService::$tableAddress,
            [
                'id' => $addressId,
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => ConfigService::$brand,
                'user_id' => $userId
            ]
        );

        $paypalBillingAgreementId = 1;

        $this->assertDatabaseHas(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'id' => $paypalBillingAgreementId,
                'external_id' => $agreementId,
                'payment_gateway_name' => ConfigService::$brand,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $paymentMethodId = 1;

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_id' => $paymentMethodId,
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'billing_address_id' => 1,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodId,
                'is_primary' => 0,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // assert event raised and test the id from event
        Event::assertDispatched(
            PaypalPaymentMethodEvent::class,
            function ($e) use ($paymentMethodId) {
                return $paymentMethodId == $e->getPaymentMethodId();
            }
        );
    }

    // public function test_update_payment_method_update_credit_card_validation()
    // {
    //     $userId = $this->createAndLogInNewUser();
    //     $creditCard = $this->creditCardRepository->create(
    //         $this->faker->creditCard(
    //             [
    //                 'payment_gateway_name' => 'drumeo',
    //             ]
    //         )
    //     );

    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $creditCard['id'],
    //             ]
    //         )
    //     );
    //     $userPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $paymentMethod['id'],
    //             ]
    //         )
    //     );

    //     $results = $this->call(
    //         'PATCH',
    //         '/payment-method/' . $paymentMethod['id'],
    //         [
    //             'update_method' => 'update-current-credit-card',
    //             'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //             'user_id' => $userId,
    //         ]
    //     );

    //     //assert results status code and errors
    //     $this->assertEquals(422, $results->getStatusCode());
    //     $this->assertEquals(
    //         [
    //             [
    //                 "source" => "gateway",
    //                 "detail" => "The gateway field is required.",
    //             ],
    //             [
    //                 "source" => "year",
    //                 "detail" => "The year field is required.",
    //             ],
    //             [
    //                 "source" => "month",
    //                 "detail" => "The month field is required.",
    //             ],
    //             [
    //                 "source" => "country",
    //                 "detail" => "The country field is required.",
    //             ],
    //         ],
    //         $results->decodeResponseJson('meta')['errors']
    //     );
    // }

    // public function test_delete_payment_method_not_authenticated_user()
    // {
    //     $this->permissionServiceMock->method('canOrThrow')
    //         ->willThrowException(
    //             new NotAllowedException('This action is unauthorized.')
    //         );

    //     $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $creditCard['id'],
    //             ]
    //         )
    //     );

    //     $userPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => rand(2, 32767),
    //                 'payment_method_id' => $paymentMethod['id'],
    //             ]
    //         )
    //     );

    //     $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

    //     $this->assertEquals(403, $results->getStatusCode());

    //     $this->assertArraySubset(
    //         [
    //             "title" => "Not allowed.",
    //             "detail" => "This action is unauthorized.",
    //         ],
    //         $results->decodeResponseJson('error')
    //     );

    //     //assert payment method still exist in db
    //     $this->assertDatabaseHas(
    //         ConfigService::$tablePaymentMethod,
    //         [
    //             'id' => $paymentMethod['id'],
    //         ]
    //     );
    // }

    // public function test_user_delete_payment_method_credit_card()
    // {
    //     $userId = $this->createAndLogInNewUser();

    //     $defaultCreditCard = $this->creditCardRepository->create($this->faker->creditCard());

    //     $defaultPaymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $defaultCreditCard['id'],
    //             ]
    //         )
    //     );

    //     $assignDefaultPaymentMethodToUser = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $defaultPaymentMethod['id'],
    //                 'is_primary' => 1
    //             ]
    //         )
    //     );

    //     $creditCard = $this->creditCardRepository->create($this->faker->creditCard());

    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $creditCard['id'],
    //             ]
    //         )
    //     );

    //     $assignPaymentMethodToUser = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $paymentMethod['id'],
    //                 'is_primary' => 0
    //             ]
    //         )
    //     );

    //     $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

    //     $this->assertEquals(204, $results->getStatusCode());
    //     $this->assertDatabaseHas(
    //         ConfigService::$tablePaymentMethod,
    //         [
    //             'id' => $paymentMethod['id'],
    //             'method_type' => $paymentMethod['method_type'],
    //             'method_id' => $creditCard['id'],
    //             'deleted_on' => Carbon::now()
    //                 ->toDateTimeString(),
    //         ]
    //     );
    // }

    // public function test_delete_payment_method_paypal()
    // {
    //     $userId = $this->createAndLogInNewUser();

    //     $defaultCreditCard = $this->creditCardRepository->create($this->faker->creditCard());

    //     $defaultPaymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $defaultCreditCard['id'],
    //             ]
    //         )
    //     );

    //     $assignDefaultPaymentMethodToUser = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $defaultPaymentMethod['id'],
    //                 'is_primary' => 1
    //             ]
    //         )
    //     );

    //     $paypalBilling = $this->paypalBillingAgreementRepository->create(
    //         $this->faker->paypalBillingAgreement(
    //             [
    //                 'payment_gateway_name' => 'drumeo',
    //             ]
    //         )
    //     );

    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $paypalBilling['id'],
    //             ]
    //         )
    //     );

    //     $assignPaymentMethodToUser = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $paymentMethod['id'],
    //                 'is_primary' => 0
    //             ]
    //         )
    //     );

    //     $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

    //     $this->assertEquals(204, $results->getStatusCode());
    //     $this->assertDatabaseHas(
    //         ConfigService::$tablePaymentMethod,
    //         [
    //             'id' => $paymentMethod['id'],
    //             'method_type' => $paymentMethod['method_type'],
    //             'method_id' => 1,
    //             'deleted_on' => Carbon::now()
    //                 ->toDateTimeString(),
    //         ]
    //     );
    // }

    // public function test_admin_delete_payment_method()
    // {
    //     $userId = $this->createAndLogInNewUser();

    //     $this->permissionServiceMock->method('canOrThrow')
    //         ->willReturn(true);

    //     $defaultCreditCard = $this->creditCardRepository->create($this->faker->creditCard());

    //     $defaultPaymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $defaultCreditCard['id'],
    //             ]
    //         )
    //     );

    //     $assignDefaultPaymentMethodToUser = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $defaultPaymentMethod['id'],
    //                 'is_primary' => 1
    //             ]
    //         )
    //     );

    //     $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $creditCard['id'],
    //             ]
    //         )
    //     );

    //     $userPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => rand(),
    //                 'payment_method_id' => $paymentMethod['id'],
    //                 'is_primary' => 0
    //             ]
    //         )
    //     );

    //     $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

    //     $this->assertEquals(204, $results->getStatusCode());

    //     $this->assertDatabaseHas(
    //         ConfigService::$tablePaymentMethod,
    //         [
    //             'id' => $paymentMethod['id'],
    //             'method_type' => $paymentMethod['method_type'],
    //             'deleted_on' => Carbon::now()->toDateTimeString(),
    //         ]
    //     );
    // }

    // public function test_delete_default_payment_method_not_allowed()
    // {
    //     $userId = $this->createAndLogInNewUser();

    //     $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
    //     $paymentMethod = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $creditCard['id'],
    //             ]
    //         )
    //     );

    //     $userPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => rand(),
    //                 'payment_method_id' => $paymentMethod['id'],
    //                 'is_primary' => 1
    //             ]
    //         )
    //     );

    //     $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

    //     $this->assertEquals(403, $results->getStatusCode());

    //     $this->assertArraySubset(
    //         [
    //             "title" => "Not allowed.",
    //             "detail" => "Delete failed, can not delete the default payment method",
    //         ],
    //         $results->decodeResponseJson('meta')['errors']
    //     );

    //     $this->assertDatabaseHas(
    //         ConfigService::$tablePaymentMethod,
    //         [
    //             'id' => $paymentMethod['id'],
    //             'method_type' => $paymentMethod['method_type'],
    //             'deleted_on' => null,
    //         ]
    //     );
    // }

    // public function test_get_user_payment_methods()
    // {
    //     $userId = $this->faker->numberBetween();
    //     $creditCard = $this->creditCardRepository->create($this->faker->creditCard());
    //     $paymentMethod1 = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $creditCard['id'],
    //             ]
    //         )
    //     );
    //     $paymentMethod1['user'] = [
    //         'user_id' => $userId,
    //         'is_primary' => 1,
    //     ];
    //     $paymentMethod1['user_id'] = $userId;
    //     $paypalBilling = $this->paypalBillingAgreementRepository->create(
    //         $this->faker->paypalBillingAgreement(
    //             [
    //                 'payment_gateway_name' => 'drumeo',
    //             ]
    //         )
    //     );

    //     $paymentMethod2 = $this->paymentMethodRepository->create(
    //         $this->faker->paymentMethod(
    //             [
    //                 'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
    //                 'method_id' => $paypalBilling['id'],
    //             ]
    //         )
    //     );
    //     $paymentMethod2['user'] = [
    //         'user_id' => $userId,
    //         'is_primary' => 1,
    //     ];
    //     $paymentMethod2['user_id'] = $userId;

    //     $assignPaymentMethodToUser = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $paymentMethod1['id'],
    //                 'is_primary' => 1,
    //             ]
    //         )
    //     );
    //     $assignPaymentMethodToUser['payment_method'] = (array)$paymentMethod1;
    //     $userPaymentMethod = $this->userPaymentMethodRepository->create(
    //         $this->faker->userPaymentMethod(
    //             [
    //                 'user_id' => $userId,
    //                 'payment_method_id' => $paymentMethod2['id'],
    //                 'is_primary' => 1,
    //             ]
    //         )
    //     );
    //     $userPaymentMethod['payment_method'] = (array)$paymentMethod2;
    //     $results = $this->call('GET', '/user-payment-method/' . $userId);

    //     $this->assertEquals(200, $results->getStatusCode());
    //     $this->assertEquals(
    //         [$assignPaymentMethodToUser->getArrayCopy(), $userPaymentMethod->getArrayCopy()],
    //         $results->decodeResponseJson('data')
    //     );
    // }

    // public function test_get_user_payment_methods_not_exists()
    // {
    //     $results = $this->call('GET', '/user-payment-method/' . rand());

    //     $this->assertEmpty($results->decodeResponseJson('data'));
    // }
}
