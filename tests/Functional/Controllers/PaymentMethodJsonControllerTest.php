<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\ExternalHelpers\PayPal;
use Railroad\Ecommerce\ExternalHelpers\Stripe;
use Railroad\Ecommerce\Factories\CustomerFactory;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaymentService;
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
     * @var PaymentGatewayFactory
     */
    private $paymentGatewayFactory;

    /**
     * @var Stripe
     */
    private $stripe;

    /**
     * @var PayPal
     */
    private $paypal;

    CONST VALID_VISA_CARD_NUM          = '4242424242424242';
    CONST VALID_EXPRESS_CHECKOUT_TOKEN = 'EC-3SD08272ET778933R';

    protected function setUp()
    {
        parent::setUp();
        $this->paymentMethodFactory  = $this->app->make(PaymentMethodFactory::class);
        $this->customerFactory       = $this->app->make(CustomerFactory::class);
        $this->paymentGatewayFactory = $this->app->make(PaymentGatewayFactory::class);
        $this->stripe                = $this->app->make(Stripe::class);
        $this->paypal                = $this->app->make(PayPal::class);
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
        $userId             = rand();
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $cardYear           = $cardExpirationDate->format('Y');
        $cardMonth          = $cardExpirationDate->format('m');
        $cardFingerprint    = self::VALID_VISA_CARD_NUM;
        $cardLast4          = $this->faker->randomNumber(4);
        $cardType           = $this->faker->creditCardType;
        $currency           = $this->faker->currencyCode;
        $paymentGateway     = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

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

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertArraySubset([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'created_on'  => Carbon::now()->toDateTimeString(),
            'updated_on'  => null,
            'user_id'     => $userId,
            'customer_id' => null,
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
    }

    public function test_user_store_paypal_payment_method()
    {
        $expressCheckoutToken = self::VALID_EXPRESS_CHECKOUT_TOKEN;
        $addressId            = $this->faker->numberBetween();
        $userId               = rand();
        $customerId           = null;
        $currency             = 'cad';
        $paymentGateway       = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal', 'paypal_1');

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
            'user_id'     => $userId,
            'customer_id' => $customerId,
            'currency'    => $currency,
            'method'      => [
                'express_checkout_token' => $expressCheckoutToken,
                'address_id'             => $addressId,
                'expiration_date'        => Carbon::now()->addYears(10)->toDateTimeString(),
                'created_on'             => Carbon::now()->toDateTimeString(),
                'updated_on'             => null
            ]
        ], $results->decodeResponseJson()['results']);
    }

    public function test_update_payment_method_create_credit_card_validation()
    {
        $userId                   = $this->createAndLogInNewUser();
        $currency                 = $this->faker->currencyCode;
        $creditCardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway           = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $creditCardExpirationDate->format('Y'),
            $creditCardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->faker->word,
            rand(),
            $currency,
            $userId,
            null);

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method'   => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
                'method_type'     => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'payment_gateway' => $paymentGateway['id']
            ]
        );

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
    }

    public function test_update_payment_method_update_credit_card_validation()
    {
        $userId             = $this->createAndLogInNewUser();
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway     = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $cardExpirationDate->format('Y'),
            $cardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->faker->word,
            rand(),
            $this->faker->currencyCode,
            $userId,
            null);

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD,
                'method_type'   => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'user_id'       => rand()
            ]
        );

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
        $userId             = $this->createAndLogInNewUser();
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway     = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $cardExpirationDate->format('Y'),
            $cardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->faker->word,
            rand(),
            $this->faker->currencyCode,
            $userId,
            null);

        $paymentGatewayPayPal = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal', 'paypal_1');
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
    }

    public function test_user_update_payment_method_create_credit_card_response()
    {
        $cardYear                 = $this->faker->creditCardExpirationDate->format('Y');
        $cardMonth                = $this->faker->creditCardExpirationDate->format('m');
        $cardFingerprint          = self::VALID_VISA_CARD_NUM;
        $cardLast4                = $this->faker->randomNumber(4);
        $cardType                 = $this->faker->creditCardType;
        $cardHolderName           = $this->faker->name;
        $creditCardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway           = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $userId        = $this->createAndLogInNewUser();
        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $creditCardExpirationDate->format('Y'),
            $creditCardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            self::VALID_EXPRESS_CHECKOUT_TOKEN,
            rand(),
            'usd',
            $userId,
            null);

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method'                => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
                'method_type'                  => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card_year'                    => $cardYear,
                'card_month'                   => $cardMonth,
                'card_fingerprint'             => $cardFingerprint,
                'card_number_last_four_digits' => $cardLast4,
                'cardholder_name'              => $cardHolderName,
                'company_name'                 => $cardType,
                'user_id'                      => $paymentMethod['user_id'],
                'payment_gateway'              => $paymentGateway['id']
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset([
            'id'          => $paymentMethod['id'],
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'created_on'  => $paymentMethod['created_on'],
            'updated_on'  => Carbon::now()->toDateTimeString(),
            'user_id'     => $paymentMethod['user_id'],
            'customer_id' => $paymentMethod['customer_id'],
            'currency'    => $paymentMethod['currency'],
            'method'      => [
                'id'                => $paymentMethod['method']['id'] + 1,
                'type'              => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint'       => $cardFingerprint,
                'last_four_digits'  => $cardLast4,
                'cardholder_name'   => $cardHolderName,
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
    }

    public function test_customer_update_payment_method_update_credit_card_response()
    {
        $customer       = $this->customerFactory->store();
        $cardYear       = $this->faker->creditCardExpirationDate->format('Y');
        $cardMonth      = $this->faker->month;
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $this->faker->creditCardExpirationDate->format('Y'),
            $this->faker->creditCardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->faker->word,
            rand(),
            $this->faker->currencyCode,
            null,
            $customer['id']);

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD,
                'method_type'   => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card_year'     => $cardYear,
                'card_month'    => $cardMonth,
                'customer_id'   => $customer['id']
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset([
            'id'          => $paymentMethod['id'],
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'created_on'  => $paymentMethod['created_on'],
            'updated_on'  => Carbon::now()->toDateTimeString(),
            'user_id'     => $paymentMethod['user_id'],
            'customer_id' => $customer['id'],
            'currency'    => $paymentMethod['currency'],
            'method'      => [
                'id'                => $paymentMethod['method']['id'],
                'type'              => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint'       => $paymentMethod['method']['fingerprint'],
                'last_four_digits'  => $paymentMethod['method']['last_four_digits'],
                'cardholder_name'   => $paymentMethod['method']['cardholder_name'],
                'company_name'      => $paymentMethod['method']['company_name'],
                'external_provider' => 'stripe',
                'expiration_date'   => Carbon::create(
                    $cardYear,
                    $cardMonth,
                    12,
                    0,
                    0,
                    0
                ),
                'created_on'        => $paymentMethod['created_on'],
                'updated_on'        => Carbon::now()->toDateTimeString()
            ]
        ], $results->decodeResponseJson()['results']);
    }

    public function test_customer_update_payment_method_use_paypal()
    {
        $customer                 = $this->customerFactory->store();
        $creditCardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway           = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');
        $this->stripe->setApiKey(ConfigService::$stripeAPI['stripe_1']['stripe_api_secret']);

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $creditCardExpirationDate->format('Y'),
            $creditCardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->stripe->createCardToken(self::VALID_VISA_CARD_NUM, $creditCardExpirationDate->format('m'), $creditCardExpirationDate->format('Y')),
            rand(),
            $this->faker->currencyCode,
            null,
            $customer['id']);

        $expressCheckoutToken = self::VALID_EXPRESS_CHECKOUT_TOKEN;

        $addressId            = rand();
        $paymentGatewayPaypal = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal', 'paypal_1');

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method'          => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL,
                'method_type'            => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'express_checkout_token' => $expressCheckoutToken,
                'address_id'             => $addressId,
                'customer_id'            => $paymentMethod['customer_id'],
                'payment_gateway'        => $paymentGatewayPaypal['id']
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertArraySubset([
            'id'          => $paymentMethod['id'],
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'created_on'  => $paymentMethod['created_on'],
            'updated_on'  => Carbon::now()->toDateTimeString(),
            'user_id'     => $paymentMethod['user_id'],
            'customer_id' => $paymentMethod['customer_id'],
            'currency'    => $paymentMethod['currency'],
            'method'      => [
                'id'                     => $paymentMethod['method']['id'],
                'express_checkout_token' => $expressCheckoutToken,
                'address_id'             => $addressId,
                'expiration_date'        => Carbon::now()->addYears(10),
                'created_on'             => $paymentMethod['created_on'],
                'updated_on'             => Carbon::now()->toDateTimeString()
            ]
        ], $results->decodeResponseJson()['results']);
    }

    public function test_customer_delete_own_payment_method()
    {
        $customer                 = $this->customerFactory->store();
        $creditCardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway           = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $creditCardExpirationDate->format('Y'),
            $creditCardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->faker->word,
            rand(),
            $this->faker->currencyCode,
            null,
            $customer['id']);

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id'], [
            'customer_id' => $customer['id']
        ]);

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

    public function test_delete_payment_method_not_authenticated_user()
    {
        $randomId = rand();
        $results  = $this->call('DELETE', '/payment-method/' . $randomId);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "This action is unauthorized. Please login",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_user_delete_payment_method_credit_card()
    {
        $userId             = $this->createAndLogInNewUser();
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway     = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $cardExpirationDate->format('Y'),
            $cardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->faker->word,
            rand(),
            $userId);
        $results       = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

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

    public function test_admin_delete_payment_method_paypal()
    {
        $this->createAndLoginAdminUser();
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway     = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $cardExpirationDate->format('Y'),
            $cardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->faker->word,
            rand(),
            $this->faker->currencyCode,
            null,
            rand());

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

    public function test_user_delete_other_payment_method_response()
    {
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal', 'paypal_1');
        $paymentMethod  = $this->paymentMethodFactory->store(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE, $paymentGateway['id']);
        $currentUserId  = $this->createAndLogInNewUser();

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(403, $results->getStatusCode());
        $this->assertEquals(
            [
                "title"  => "Not allowed.",
                "detail" => "This action is unauthorized.",
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_user_delete_own_payment_method_response()
    {
        $currentUserId            = $this->createAndLogInNewUser();
        $creditCardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway           = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $creditCardExpirationDate->format('Y'),
            $creditCardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            '',
            null,
            $this->faker->currencyCode,
            $currentUserId,
            null);

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
    }

    public function test_user_create_credit_card_payment_method_and_pay_using_it()
    {
        $userId             = $this->createAndLogInNewUser();
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $cardYear           = $cardExpirationDate->format('Y');
        $cardMonth          = $cardExpirationDate->format('m');
        $cardFingerprint    = self::VALID_VISA_CARD_NUM;
        $cardLast4          = $this->faker->randomNumber(4);
        $cardType           = $this->faker->creditCardType;
        $currency           = 'USD';
        $paymentGateway     = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->call('PUT', '/payment-method', [
            'method_type'                  => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'payment_gateway'              => $paymentGateway['id'],
            'card_year'                    => $cardYear,
            'card_month'                   => $cardMonth,
            'card_fingerprint'             => $cardFingerprint,
            'card_number_last_four_digits' => $cardLast4,
            'company_name'                 => $cardType,
            'currency'                     => $currency,
            'user_id'                      => $userId
        ]);

        $results = $this->call('PUT', '/payment',
            [
                'payment_method_id' => $paymentMethod->decodeResponseJson()['results']['id'],
                'due'               => 210000,
                'type'              => PaymentService::ORDER_PAYMENT_TYPE,
                'currency'          => $currency
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id'          => $paymentMethod->decodeResponseJson()['results']['id'],
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'currency'    => $currency
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'type'             => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint'      => $cardFingerprint,
                'last_four_digits' => $cardLast4,
                'company_name'     => $cardType
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserStripeCustomer,
            [
                'user_id' => $userId
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'due'               => 210000,
                'paid'              => 210000,
                'type'              => PaymentService::ORDER_PAYMENT_TYPE,
                'external_id'       => $results->decodeResponseJson()['results']['external_id'],
                'status'            => 1,
                'payment_method_id' => $paymentMethod->decodeResponseJson()['results']['id'],
                'currency'          => $currency
            ]
        );
    }

    public function test_customer_create_credit_card_payment_method_and_pay_with_it()
    {
        $customer = $this->customerFactory->store();

        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $cardYear           = $cardExpirationDate->format('Y');
        $cardMonth          = $cardExpirationDate->format('m');
        $cardFingerprint    = self::VALID_VISA_CARD_NUM;
        $cardLast4          = $this->faker->randomNumber(4);
        $cardType           = $this->faker->creditCardType;
        $currency           = 'USD';
        $paymentGateway     = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->call('PUT', '/payment-method', [
            'method_type'                  => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card_year'                    => $cardYear,
            'card_month'                   => $cardMonth,
            'card_fingerprint'             => $cardFingerprint,
            'card_number_last_four_digits' => $cardLast4,
            'company_name'                 => $cardType,
            'currency'                     => $currency,
            'customer_id'                  => $customer['id'],
            'payment_gateway'              => $paymentGateway['id']
        ]);

        $results = $this->call('PUT', '/payment',
            [
                'payment_method_id' => $paymentMethod->decodeResponseJson()['results']['id'],
                'due'               => 210000,
                'type'              => PaymentService::ORDER_PAYMENT_TYPE,
                'customer_id'       => $customer['id'],
                'currency'          => $currency
            ]);

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id'          => $paymentMethod->decodeResponseJson()['results']['id'],
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'currency'    => $currency
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'type'             => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint'      => $cardFingerprint,
                'last_four_digits' => $cardLast4,
                'company_name'     => $cardType
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCustomerStripeCustomer,
            [
                'customer_id' => $customer['id']
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'due'               => 210000,
                'paid'              => 210000,
                'type'              => PaymentService::ORDER_PAYMENT_TYPE,
                'external_id'       => $results->decodeResponseJson()['results']['external_id'],
                'status'            => 1,
                'payment_method_id' => $paymentMethod->decodeResponseJson()['results']['id'],
                'currency'          => $currency
            ]
        );
    }
}
