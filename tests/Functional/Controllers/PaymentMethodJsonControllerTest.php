<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Events\PaypalPaymentMethodEvent;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Stripe\Card;
use Stripe\Customer;
use Stripe\Token;

class PaymentMethodJsonControllerTest extends EcommerceTestCase
{
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
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'user_email' => $this->faker->email
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals(
            [
                [
                    'source' => 'card_token',
                    'detail' => 'The card token field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'gateway',
                    'detail' => 'The gateway field is required.',
                    'title' => 'Validation failed.'
                ],
                [
                    'source' => 'address_id',
                    'detail' => 'The address id field is required.',
                    'title' => 'Validation failed.',
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
                    "title" => "Validation failed.",
                    "source" => "card_token",
                    "detail" => "The card token field is required.",
                ],
                [
                    "title" => "Validation failed.",
                    "source" => "address_id",
                    "detail" => "The address id field is required.",
                ]
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

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = 'recordeo';

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => [new Customer()]]);
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn(new Token());
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $address = $this->fakeAddress();

        $payload = [
            'card_token' => 'tok_mastercard',
            'gateway' => $gateway,
            'method_type' => $methodType,
            'currency' => $currency,
            'set_default' => true,
            'user_email' => $customer['email'],
            'user_id' => $customer['id'],
            'address_id' => $address['id'],
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
                        'type' => 'creditCard',
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
                'credit_card_id' => 1,
                'paypal_billing_agreement_id' => null,
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $fakerCard->fingerprint,
                'last_four_digits' => $fakerCard->last4,
                'company_name' => $fakerCard->brand,
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

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = 'recordeo';

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->word;
        $fakerCard->last4 = $this->faker->randomNumber(4);
        $fakerCard->exp_year = $cardExpirationDate->format('Y');
        $fakerCard->exp_month = $cardExpirationDate->format('m');
        $fakerCard->id = $this->faker->word;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $this->stripeExternalHelperMock->method('getCustomersByEmail')
            ->willReturn(['data' => [new Customer()]]);
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn(new Token());
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $address = $this->fakeAddress();

        $payload = [
            'card_token' => 'tok_mastercard',
            'gateway' => $gateway,
            'method_type' => $methodType,
            'currency' => $currency,
            'set_default' => false,
            'user_email' => $customer['email'],
            'user_id' => $customer['id'],
            'address_id' => $address['id'],
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
                        'type' => 'creditCard',
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
                'credit_card_id' => 1,
                'paypal_billing_agreement_id' => null,
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $fakerCard->fingerprint,
                'last_four_digits' => $fakerCard->last4,
                'company_name' => $fakerCard->brand,
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

        $customer = new Customer();
        $customer->email = $this->faker->email;

        $currency = $this->faker->currencyCode;
        $cardExpirationDate = $this->faker->creditCardExpirationDate;

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4);
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

        $this->stripeExternalHelperMock->method('createCard')
            ->willThrowException(
                new PaymentFailedException(
                    'The card number is incorrect. Check the card’s number or use a different card.'
                )
            );

        $address = $this->fakeAddress();

        $this->expectException(PaymentFailedException::class);

        $results = $this->call(
            'PUT',
            '/payment-method',
            [
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'card_token' => $this->faker->creditCardNumber,
                'currency' => $currency,
                'user_id' => $userId,
                'gateway' => 'drumeo',
                'user_email' => $this->faker->email,
                'address_id' => $address['id'],
            ]
        );

        $this->assertArraySubset(
            [
                'title' => 'Payment failed.',
                'detail' => 'The card number is incorrect. Check the card’s number or use a different card.',
            ],
            $results->decodeResponseJson('errors')
        );



        // assert payment method data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
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

    public function test_update_payment_method_permissions_fail()
    {
        $userId = $this->createAndLogInNewUser();

        $customer = $this->fakeUser();

        $currency = $this->faker->currencyCode;

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $expirationDate = $this->faker->creditCardExpirationDate;

        $gateway = 'recordeo';

        $creditCard = $this->fakeCreditCard();

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $creditCard = $this->fakeCreditCard();

        $payload = [
            'gateway' => 'recordeo',
            'year' => $expirationDate->format('Y'),
            'month' => $expirationDate->format('m'),
            'country' => $this->faker->word,
        ];

        $updatedCard = (object)[
            'fingerprint' => $creditCard['fingerprint'],
            'last4' => $creditCard['last_four_digits'],
            'name' => $creditCard['cardholder_name'],
            'exp_year' => $payload['year'],
            'exp_month' => $payload['month'],
            'id' => $creditCard['external_id'],
            'customer' => $creditCard['external_customer_id'],
            'address_country' => $payload['country'],
            'address_state' => $payload['state'] ?? '',
        ];

        $this->stripeExternalHelperMock->method('updateCard')
            ->willReturn($updatedCard);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $exceptionMessage = 'You are not allowed to update payment method';

        $this->permissionServiceMock->method('canOrThrow')
            ->willThrowException(
                new NotAllowedException($exceptionMessage)
            );

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            $payload
        );

        // assert respons status code and errors
        $this->assertEquals(403, $response->getStatusCode());

        $response->assertJsonFragment(
            [
                'detail' => $exceptionMessage,
                'title' => 'Not allowed.',
            ]
        );
    }

    public function test_update_payment_method_not_found()
    {
        $userId = $this->createAndLogInNewUser();

        $expirationDate = $this->faker->creditCardExpirationDate;

        $paymentMethodId = $this->faker->randomNumber(4);

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethodId,
            [
                'gateway' => 'recordeo',
                'year' => $expirationDate->format('Y'),
                'month' => $expirationDate->format('m'),
                'country' => $this->faker->word,
            ]
        );

        // assert respons status code and errors
        $this->assertEquals(404, $response->getStatusCode());

        $details = 'Update failed, payment method not found with id: ' .
            $paymentMethodId;

        $response->assertJsonFragment(
            [
                'detail' => $details,
                'title' => 'Not found.',
            ]
        );
    }

    public function test_update_payment_method()
    {
        $userId = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $actualDate = Carbon::now()->startOfMonth();

        $newDate = Carbon::now()->addYear(1)->startOfMonth();

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $creditCard = $this->fakeCreditCard([
            'expiration_date' => $actualDate->toDateTimeString()
        ]);

        $payload = [
            'gateway' => $gateway,
            'year' => $newDate->format('Y'),
            'month' => $newDate->format('m'),
            'country' => $this->faker->word,
        ];

        $updatedCard = (object)[
            'fingerprint' => $this->faker->shuffleString(
                $this->faker->bothify('???###???###???###???###')
            ),
            'last4' => $this->faker->randomNumber(4, true),
            'name' => $this->faker->name,
            'exp_year' => $payload['year'],
            'exp_month' => $payload['month'],
            'id' => $this->faker->word,
            'customer' => $this->faker->word,
            'address_country' => $payload['country'],
            'address_state' => $payload['state'] ?? '',
            'brand' => $this->faker->creditCardType
        ];

        $this->stripeExternalHelperMock->method('updateCard')
            ->willReturn($updatedCard);

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id']
        ]);

        // add a subscription
        $subscription = $this->fakeSubscription([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            $payload
        );

        // assert respons status code
        $this->assertEquals(200, $response->getStatusCode());

        unset($paymentMethod['credit_card_id']);

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'paymentMethod',
                    'id' => $paymentMethod['id'],
                    'attributes' => array_diff_key(
                        $paymentMethod,
                        [
                            'id' => true,
                            'billing_address_id' => true
                        ]
                    ),
                ],
            ],
            $response->decodeResponseJson()
        );

        // assert card was created
        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $updatedCard->fingerprint,
                'last_four_digits' => $updatedCard->last4,
                'cardholder_name' => $updatedCard->name,
                'company_name' => $updatedCard->brand,
                'external_id' => $updatedCard->id,
                'external_customer_id' => $updatedCard->customer,
                'expiration_date' => $newDate->toDateTimeString(),
                'payment_gateway_name' => $payload['gateway'],
                'updated_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    function test_update_payment_method_not_credit_card()
    {
        $userId = $this->createAndLogInNewUser();

        $customer = $this->fakeUser();

        $methodType = PaymentMethod::TYPE_PAYPAL;

        $actualDate = Carbon::now()->startOfMonth();

        $newDate = Carbon::now()->addYear(1)->startOfMonth();

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $creditCard = $this->fakeCreditCard([
            'expiration_date' => $actualDate->toDateTimeString()
        ]);

        $payload = [
            'gateway' => $gateway,
            'year' => $newDate->format('Y'),
            'month' => $newDate->format('m'),
            'country' => $this->faker->word,
        ];

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => PaymentMethod::TYPE_PAYPAL,
            'billing_address_id' => $address['id'],
        ]);

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            $payload
        );

        $this->assertEquals(404, $response->getStatusCode());

        $details = 'Only credit card payment methods may be updated';

        $response->assertJsonFragment(
            [
                'detail' => $details,
                'title' => 'Payment failed.',
            ]
        );

        // assert credit card data not saved in the db
        $this->assertDatabaseMissing(
            ConfigService::$tableCreditCard,
            [
                'id' => $creditCard['id'],
                'expiration_date' => $newDate->toDateTimeString(),
            ]
        );
    }

    function test_update_payment_method_not_default()
    {
        $userId = $this->createAndLogInNewUser();

        $customer = $this->fakeUser();

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $actualDate = Carbon::now()->startOfMonth();

        $newDate = Carbon::now()->addYear(1)->startOfMonth();

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $creditCardOne = $this->fakeCreditCard([
            'expiration_date' => $actualDate->toDateTimeString()
        ]);

        $creditCardTwo = $this->fakeCreditCard([
            'expiration_date' => $actualDate->toDateTimeString()
        ]);

        $payload = [
            'gateway' => $gateway,
            'year' => $newDate->format('Y'),
            'month' => $newDate->format('m'),
            'country' => $this->faker->word,
        ];

        $updatedCardTwo = (object)[
            'fingerprint' => $this->faker->shuffleString(
                $this->faker->bothify('???###???###???###???###')
            ),
            'last4' => $this->faker->randomNumber(4, true),
            'name' => $this->faker->name,
            'exp_year' => $payload['year'],
            'exp_month' => $payload['month'],
            'id' => $this->faker->word,
            'customer' => $this->faker->word,
            'address_country' => $payload['country'],
            'address_state' => $payload['state'] ?? '',
            'brand' => $this->faker->creditCardType
        ];

        $this->stripeExternalHelperMock->method('updateCard')
            ->willReturn($updatedCardTwo);

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethodOne = $this->fakePaymentMethod([
            'credit_card_id' => $creditCardOne['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethodOne = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'payment_method_id' => $paymentMethodOne['id'],
            'is_primary' => true
        ]);

        $paymentMethodTwo = $this->fakePaymentMethod([
            'credit_card_id' => $creditCardTwo['id'],
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethodTwo = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'payment_method_id' => $paymentMethodTwo['id'],
            'is_primary' => false
        ]);

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethodTwo['id'],
            $payload
        );

        unset($paymentMethodTwo['credit_card_id']);

        // assert respons status code
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'paymentMethod',
                    'id' => $paymentMethodTwo['id'],
                    'attributes' => array_diff_key(
                        $paymentMethodTwo,
                        [
                            'id' => true,
                            'billing_address_id' => true
                        ]
                    ),
                ],
            ],
            $response->decodeResponseJson()
        );

        // assert card was created
        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $updatedCardTwo->fingerprint,
                'last_four_digits' => $updatedCardTwo->last4,
                'cardholder_name' => $updatedCardTwo->name,
                'company_name' => $updatedCardTwo->brand,
                'external_id' => $updatedCardTwo->id,
                'external_customer_id' => $updatedCardTwo->customer,
                'expiration_date' => $newDate->toDateTimeString(),
                'payment_gateway_name' => $payload['gateway'],
                'updated_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // assert payment method was set as default
        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $customer['id'],
                'payment_method_id' => $paymentMethodOne['id'],
                'is_primary' => 1,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $customer['id'],
                'payment_method_id' => $paymentMethodTwo['id'],
                'is_primary' => 0,
            ]
        );
    }

    public function test_update_payment_method_set_default()
    {
        Event::fake();

        $userId = $this->createAndLogInNewUser();

        $customer = $this->fakeUser();

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $actualDate = Carbon::now()->startOfMonth();

        $newDate = Carbon::now()->addYear(1)->startOfMonth();

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $creditCardOne = $this->fakeCreditCard([
            'expiration_date' => $actualDate->toDateTimeString()
        ]);

        $creditCardTwo = $this->fakeCreditCard([
            'expiration_date' => $actualDate->toDateTimeString()
        ]);

        $payload = [
            'gateway' => $gateway,
            'year' => $newDate->format('Y'),
            'month' => $newDate->format('m'),
            'country' => $this->faker->word,
            'set_default' => true
        ];

        $updatedCardTwo = (object)[
            'fingerprint' => $this->faker->shuffleString(
                $this->faker->bothify('???###???###???###???###')
            ),
            'last4' => $this->faker->randomNumber(4, true),
            'name' => $this->faker->name,
            'exp_year' => $payload['year'],
            'exp_month' => $payload['month'],
            'id' => $this->faker->word,
            'customer' => $this->faker->word,
            'address_country' => $payload['country'],
            'address_state' => $payload['state'] ?? '',
            'brand' => $this->faker->creditCardType
        ];

        $this->stripeExternalHelperMock->method('updateCard')
            ->willReturn($updatedCardTwo);

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethodOne = $this->fakePaymentMethod([
            'credit_card_id' => $creditCardOne['id'],
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethodOne = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'payment_method_id' => $paymentMethodOne['id'],
            'is_primary' => true
        ]);

        $paymentMethodTwo = $this->fakePaymentMethod([
            'credit_card_id' => $creditCardTwo['id'],
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethodTwo = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'payment_method_id' => $paymentMethodTwo['id'],
            'is_primary' => false
        ]);

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethodTwo['id'],
            $payload
        );

        unset($paymentMethodTwo['credit_card_id']);

        // assert respons status code
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'paymentMethod',
                    'id' => $paymentMethodTwo['id'],
                    'attributes' => array_diff_key(
                        $paymentMethodTwo,
                        [
                            'id' => true,
                            'billing_address_id' => true
                        ]
                    ),
                ],
            ],
            $response->decodeResponseJson()
        );

        // assert card was created
        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $updatedCardTwo->fingerprint,
                'last_four_digits' => $updatedCardTwo->last4,
                'cardholder_name' => $updatedCardTwo->name,
                'company_name' => $updatedCardTwo->brand,
                'external_id' => $updatedCardTwo->id,
                'external_customer_id' => $updatedCardTwo->customer,
                'expiration_date' => $newDate->toDateTimeString(),
                'payment_gateway_name' => $payload['gateway'],
                'updated_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // assert payment method was set as default
        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $customer['id'],
                'payment_method_id' => $paymentMethodOne['id'],
                'is_primary' => 0,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $customer['id'],
                'payment_method_id' => $paymentMethodTwo['id'],
                'is_primary' => 1,
            ]
        );

        // assert event raised and test the ids from event
        Event::assertDispatched(
            UserDefaultPaymentMethodEvent::class,
            function ($e) use ($paymentMethodTwo, $customer) {
                return $paymentMethodTwo['id'] == $e->getDefaultPaymentMethodId() &&
                    $customer['id'] == $e->getUserId();
            }
        );
    }

    public function test_update_payment_method_update_subscription()
    {
        $userId = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $actualDate = Carbon::now()->startOfMonth();

        $newDate = Carbon::now()->addYear(1)->startOfMonth();

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $creditCard = $this->fakeCreditCard([
            'expiration_date' => $actualDate->toDateTimeString()
        ]);

        $payload = [
            'gateway' => $gateway,
            'year' => $newDate->format('Y'),
            'month' => $newDate->format('m'),
            'country' => $this->faker->word,
        ];

        $updatedCard = (object)[
            'fingerprint' => $this->faker->shuffleString(
                $this->faker->bothify('???###???###???###???###')
            ),
            'last4' => $this->faker->randomNumber(4, true),
            'name' => $this->faker->name,
            'exp_year' => $payload['year'],
            'exp_month' => $payload['month'],
            'id' => $this->faker->word,
            'customer' => $this->faker->word,
            'address_country' => $payload['country'],
            'address_state' => $payload['state'] ?? '',
            'brand' => $this->faker->creditCardType
        ];

        $this->stripeExternalHelperMock->method('updateCard')
            ->willReturn($updatedCard);

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCard['id'],
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        // add a subscription
        $subscription = $this->fakeSubscription([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]);

        $response = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            $payload
        );

        unset($paymentMethod['credit_card_id']);

        // assert respons status code
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'data' => [
                    'type' => 'paymentMethod',
                    'id' => $paymentMethod['id'],
                    'attributes' => array_diff_key(
                        $paymentMethod,
                        [
                            'id' => true,
                            'billing_address_id' => true
                        ]
                    ),
                ],
            ],
            $response->decodeResponseJson()
        );

        // assert card was created
        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $updatedCard->fingerprint,
                'last_four_digits' => $updatedCard->last4,
                'cardholder_name' => $updatedCard->name,
                'company_name' => $updatedCard->brand,
                'external_id' => $updatedCard->id,
                'external_customer_id' => $updatedCard->customer,
                'expiration_date' => $newDate->toDateTimeString(),
                'payment_gateway_name' => $payload['gateway'],
                'updated_at' => Carbon::now()->toDateTimeString()
            ]
        );

        // assert the new payment method was set as subscription payment method
        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethod['id'],
            ]
        );
    }

    public function test_set_default()
    {
        Event::fake();

        $userId = $this->createAndLogInNewUser();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType,
            'brand' => 'recordeo',
            'user_id' => $userId,
            'state' => '',
            'country' => '',
            'created_at' => Carbon::now()
        ]);

        $creditCardOne = $this->fakeCreditCard([
            'payment_gateway_name' => 'recordeo',
        ]);

        $creditCardTwo = $this->fakeCreditCard([
            'payment_gateway_name' => 'recordeo',
        ]);

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $oldPrimaryPaymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCardOne['id'],
            'billing_address_id' => $address['id'],
        ]);

        $this->fakePaymentMethod();

        $newPrimaryPaymentMethod = $this->fakePaymentMethod([
            'credit_card_id' => $creditCardTwo['id'],
            'billing_address_id' => $address['id'],
        ]);

        $userOldPrimaryPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $oldPrimaryPaymentMethod['id'],
            'is_primary' => true,
        ]);

        $userNewPrimaryPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $newPrimaryPaymentMethod['id'],
            'is_primary' => false
        ]);

        $response = $this->call(
            'PATCH',
            '/payment-method/set-default',
            ['id' => $newPrimaryPaymentMethod['id']]
        );

        $this->assertEquals(204, $response->getStatusCode());

        // assert event raised and test the ids from event
        Event::assertDispatched(
            UserDefaultPaymentMethodEvent::class,
            function ($e) use ($newPrimaryPaymentMethod, $userId) {
                return $newPrimaryPaymentMethod['id'] == $e->getDefaultPaymentMethodId() &&
                    $userId == $e->getUserId();
            }
        );

        // assert database updates - old defaultPaymentMethod is not primary
        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => $oldPrimaryPaymentMethod['id'],
                'is_primary' => 0,
            ]
        );

        // assert payment method was set as default
        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => $newPrimaryPaymentMethod['id'], // 3
                'is_primary' => 1,
            ]
        );
    }

    public function test_set_default_update_subscription()
    {
        $userId = $this->createAndLogInNewUser();

        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $expirationDate = Carbon::now()->startOfMonth();

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $creditCardOne = $this->fakeCreditCard([
            'expiration_date' => $expirationDate->toDateTimeString()
        ]);

        $creditCardTwo = $this->fakeCreditCard([
            'expiration_date' => $expirationDate->toDateTimeString()
        ]);

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethodOne = $this->fakePaymentMethod([
            'method_id' => $creditCardOne['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethodOne = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethodOne['id'],
            'is_primary' => true
        ]);

        $subscription = $this->fakeSubscription([
            'user_id' => $userId,
            'payment_method_id' => $userPaymentMethodOne['id']
        ]);

        $this->fakePaymentMethod();

        $paymentMethodTwo = $this->fakePaymentMethod([
            'method_id' => $creditCardTwo['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethodTwo = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethodTwo['id'],
            'is_primary' => false
        ]);

        $payload = [
            'id' => $paymentMethodTwo['id']
        ];

        $response = $this->call(
            'PATCH',
            '/payment-method/set-default',
            $payload
        );

        // assert respons status code
        $this->assertEquals(204, $response->getStatusCode());

        // assert the new payment method was set as subscription payment method
        $this->assertDatabaseHas(
            ConfigService::$tableSubscription,
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodTwo['id'], // 3
            ]
        );

        // assert payment method was set as default
        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodOne['id'],
                'is_primary' => 0,
            ]
        );

        // assert database updates - old defaultPaymentMethod is not primary
        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodTwo['id'],
                'is_primary' => 1,
            ]
        );
    }

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
                'type' => ConfigService::$billingAddressType,
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
                'type' => ConfigService::$billingAddressType,
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
                'paypal_billing_agreement_id' => $paymentMethodId,
                'billing_address_id' => 1,
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodId,
                'is_primary' => 1,
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

    public function test_update_payment_method_update_credit_card_validation()
    {
        $userId = $this->createAndLogInNewUser();

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        // add a subscription
        $subscription = $this->fakeSubscription([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id']
        ]);

        $results = $this->call(
            'PATCH',
            '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => 'update-current-credit-card',
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'user_id' => $userId,
            ]
        );

        // assert results status code and errors
        $this->assertEquals(422, $results->getStatusCode());

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
            $results->decodeResponseJson('errors')
        );
    }

    public function test_delete_payment_method_not_authenticated_user()
    {
        $userId = $this->createAndLogInNewUser();

        $customer = $this->fakeUser();

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        // add a subscription
        $subscription = $this->fakeSubscription([
            'user_id' => $customer['id'],
            'payment_method_id' => $paymentMethod['id']
        ]);

        $message = 'This action is unauthorized.';

        $this->permissionServiceMock
            ->method('canOrThrow')
            ->willThrowException(
                new NotAllowedException($message)
            );

        $results = $this->call(
            'DELETE',
            '/payment-method/' . $paymentMethod['id']
        );

        $this->assertEquals(403, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'title' => 'Not allowed.',
                'detail' => $message,
            ],
            $results->decodeResponseJson('error')
        );

        // assert payment method was not soft deleted
        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
                'deleted_at' => null
            ]
        );
    }

    public function test_user_delete_payment_method_credit_card()
    {
        $userId = $this->createAndLogInNewUser();

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $defaultCreditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $defaultPaymentMethod = $this->fakePaymentMethod([
            'method_id' => $defaultCreditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $defaultUserPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $defaultPaymentMethod['id'],
            'is_primary' => true
        ]);

        $otherCreditCard = $this->fakeCreditCard();

        $otherPaymentMethod = $this->fakePaymentMethod([
            'method_id' => $otherCreditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $otherUserPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $otherPaymentMethod['id'],
            'is_primary' => false
        ]);

        // add a subscription
        $subscription = $this->fakeSubscription([
            'user_id' => $userId,
            'payment_method_id' => $defaultUserPaymentMethod['id']
        ]);

        $results = $this->call(
            'DELETE',
            '/payment-method/' . $otherPaymentMethod['id']
        );

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertSoftDeleted(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $otherPaymentMethod['id'],
            ]
        );
    }

    public function test_delete_payment_method_paypal()
    {
        $userId = $this->createAndLogInNewUser();

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $defaultCreditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $defaultPaymentMethod = $this->fakePaymentMethod([
            'method_id' => $defaultCreditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $defaultUserPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $defaultPaymentMethod['id'],
            'is_primary' => true
        ]);

        $paypalBillingAgreement = $this->fakePaypalBillingAgreement();

        $paypalPaymentMethod = $this->fakePaymentMethod([
            'method_id' => $paypalBillingAgreement['id'],
            'method_type' => PaymentMethod::TYPE_PAYPAL,
            'billing_address_id' => $address['id'],
        ]);

        $paypalUserPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paypalPaymentMethod['id'],
            'is_primary' => false
        ]);

        // add a subscription
        $subscription = $this->fakeSubscription([
            'user_id' => $userId,
            'payment_method_id' => $defaultUserPaymentMethod['id']
        ]);

        $results = $this->call(
            'DELETE',
            '/payment-method/' . $paypalPaymentMethod['id']
        );

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertSoftDeleted(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paypalPaymentMethod['id'],
            ]
        );
    }

    public function test_admin_delete_payment_method()
    {
        $userId = $this->createAndLogInNewUser();

        $customer = $this->fakeUser();

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $this->permissionServiceMock->method('canOrThrow')
            ->willReturn(true);

        $defaultCreditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $defaultPaymentMethod = $this->fakePaymentMethod([
            'method_id' => $defaultCreditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $defaultUserPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'payment_method_id' => $defaultPaymentMethod['id'],
            'is_primary' => true
        ]);

        $otherCreditCard = $this->fakeCreditCard();

        $otherPaymentMethod = $this->fakePaymentMethod([
            'method_id' => $otherCreditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $otherUserPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $customer['id'],
            'payment_method_id' => $otherPaymentMethod['id'],
            'is_primary' => false
        ]);

        // add a subscription
        $subscription = $this->fakeSubscription([
            'user_id' => $customer['id'],
            'payment_method_id' => $defaultUserPaymentMethod['id']
        ]);

        $results = $this->call(
            'DELETE',
            '/payment-method/' . $otherPaymentMethod['id']
        );

        $this->assertEquals(204, $results->getStatusCode());

        $this->assertSoftDeleted(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $otherPaymentMethod['id'],
            ]
        );
    }

    public function test_delete_default_payment_method_not_allowed()
    {
        $userId = $this->createAndLogInNewUser();

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $gateway = $this->faker->randomElement(
            array_keys(ConfigService::$paymentGateways['stripe'])
        );

        $defaultCreditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType
        ]);

        $defaultPaymentMethod = $this->fakePaymentMethod([
            'method_id' => $defaultCreditCard['id'],
            'method_type' => $methodType,
            'billing_address_id' => $address['id'],
        ]);

        $defaultUserPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $defaultPaymentMethod['id'],
            'is_primary' => true
        ]);

        // add a subscription
        $subscription = $this->fakeSubscription([
            'user_id' => $userId,
            'payment_method_id' => $defaultUserPaymentMethod['id']
        ]);

        $results = $this->call(
            'DELETE',
            '/payment-method/' . $defaultPaymentMethod['id']
        );

        $this->assertEquals(403, $results->getStatusCode());

        $this->assertArraySubset(
            [
                'title' => 'Not allowed.',
                'detail' => 'Delete failed, can not delete the default payment method',
            ],
            $results->decodeResponseJson('errors')
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $defaultPaymentMethod['id'],
                'deleted_at' => null,
            ]
        );
    }

    public function test_get_user_payment_methods()
    {
        $userId = $this->createAndLogInNewUser();

        $address = $this->fakeAddress([
            'type' => ConfigService::$billingAddressType,
            'brand' => 'recordeo',
            'user_id' => $userId,
            'state' => '',
            'country' => '',
            'created_at' => Carbon::now()
        ]);

        $creditCardOne = $this->fakeCreditCard([
            'payment_gateway_name' => 'recordeo',
        ]);

        $creditCardTwo = $this->fakeCreditCard([
            'payment_gateway_name' => 'recordeo',
        ]);

        $paypalAgreement = $this->fakePaypalBillingAgreement([
            'payment_gateway_name' => 'recordeo',
        ]);

        $methodType = PaymentMethod::TYPE_CREDIT_CARD;

        $paymentMethodsCc = [];

        $paymentMethodOne = $this->fakePaymentMethod([
            'credit_card_id' => $creditCardOne['id'],
            'billing_address_id' => $address['id'],
        ]);
        $paymentMethodOne['credit_card_id'] = $creditCardOne['id'];

        $paymentMethodsCc[$paymentMethodOne['id']] = $paymentMethodOne;

        $paymentMethodTwo = $this->fakePaymentMethod([
            'credit_card_id' => $creditCardTwo['id'],
            'billing_address_id' => $address['id'],
        ]);
        $paymentMethodTwo['credit_card_id'] = $creditCardTwo['id'];

        $paymentMethodsCc[$paymentMethodTwo['id']] = $paymentMethodTwo;

        $paymentMethodThree = $this->fakePaymentMethod([
            'paypal_billing_agreement_id' => $paymentMethodOne['id'],
            'billing_address_id' => $address['id'],
        ]);
        $paymentMethodThree['paypal_billing_agreement_id'] = $paypalAgreement['id'];

        $userPaymentMethods = [];

        $userPaymentMethods[] = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethodOne['id'],
            'is_primary' => true,
        ]);

        $userPaymentMethods[] = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethodTwo['id'],
            'is_primary' => false
        ]);

        $userPaymentMethods[] = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethodThree['id'],
            'is_primary' => false
        ]);

        $expected = ['data' => []];
        $userRelation = [
            'data' => [
                'type' => 'user',
                'id' => $userId,
            ]
        ];

        foreach ($userPaymentMethods as $userPaymentMethod) {

            $paymentMethodId = $userPaymentMethod['payment_method_id'];

            $methodType = isset($paymentMethodsCc[$paymentMethodId]) ?
                                'creditCard' :
                                'paypalAgreement';

            $methodId = isset($paymentMethodsCc[$paymentMethodId]) ?
                                $paymentMethodsCc[$paymentMethodId]['credit_card_id'] :
                                $paymentMethodThree['paypal_billing_agreement_id'];

            $methodRelation = [
                'data' => [
                    'type' => $methodType,
                    'id' => $methodId
                ]
            ];

            $relations = [
                'paymentMethod' => [
                    'data' => [
                        'type' => 'paymentMethod',
                        'id' => $userPaymentMethod['payment_method_id']
                    ]
                ],
                'user' => $userRelation,
                'method' => $methodRelation
            ];

            $expected['data'][] = [
                'type' => 'userPaymentMethods',
                'id' => $userPaymentMethod['id'],
                'attributes' => array_diff_key(
                    $userPaymentMethod,
                    [
                        'id' => true,
                        'payment_method_id' => true,
                        'user_id' => true
                    ]
                ),
                'relationships' => $relations
            ];
        }

        $results = $this->call('GET', '/user-payment-method/' . $userId);

        $decodedResult = $results->decodeResponseJson();

        // assert 'data' response block, including related ids
        // the 'included' associated data is not assert here
        $this->assertArraySubset(
            $expected,
            $decodedResult
        );
    }

    public function test_get_user_payment_methods_user_not_found()
    {
        $this->permissionServiceMock->method('canOrThrow')->willReturn(true);

        $userId = rand();

        $response = $this->call('GET', '/user-payment-method/' . $userId);

        $this->assertEquals(404, $response->getStatusCode());

        $this->assertArraySubset(
            [
                'title' => 'Not found.',
                'detail' => 'Pull failed, user not found with id: ' . $userId,
            ],
            $response->decodeResponseJson('errors')
        );
    }
}
