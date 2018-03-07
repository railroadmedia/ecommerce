<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Controllers\PaymentMethodJsonController;
use PHPUnit\Framework\TestCase;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaymentMethodJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var PaymentMethodFactory
     */
    private $paymentMethodFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->paymentMethodFactory = $this->app->make(PaymentMethodFactory::class);
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
                "source" => "external_id",
                "detail" => "The external id field is required when method type is credit card.",
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
                "source" => "agreement_id",
                "detail" => "The agreement id field is required when method type is paypal.",
            ],
            [
                "source" => "express_checkout_token",
                "detail" => "The express checkout token field is required when method type is paypal.",
            ],
            [
                "source" => "address_id",
                "detail" => "The address id field is required when method type is paypal.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_store_method_type_required()
    {
        $results = $this->call('PUT', '/payment-method');

        $this->assertEquals(422, $results->getStatusCode());

        $this->assertEquals([
            [
                "source" => "method_type",
                "detail" => "The method type field is required.",
            ]], $results->decodeResponseJson()['errors']);
    }

    public function test_store_credit_card_payment_method()
    {
        $cardYear = $this->faker->creditCardExpirationDate->format('Y');
        $cardMonth = $this->faker->month;
        $cardFingerprint = $this->faker->word;
        $cardLast4 = $this->faker->randomNumber(4);
        $cardType = $this->faker->creditCardType;
        $externalId = $this->faker->numberBetween();

        $results = $this->call('PUT', '/payment-method', [
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'card_year' => $cardYear,
            'card_month' => $cardMonth,
            'card_fingerprint' => $cardFingerprint,
            'card_number_last_four_digits' => $cardLast4,
            'company_name' => $cardType,
            'external_id' => $externalId
        ]);

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertEquals([
            'id' => 1,
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null,
            'method' => [
                'id' => 1,
                'type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint' => $cardFingerprint,
                'last_four_digits' => $cardLast4,
                'cardholder_name' => '',
                'company_name' => $cardType,
                'external_id' => $externalId,
                'external_provider' => 'stripe',
                'expiration_date' => Carbon::create(
                    $cardYear,
                    $cardMonth,
                    12,
                    0,
                    0,
                    0
                ),
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
        ], $results->decodeResponseJson()['results']);
    }

    public function test_store_paypal_payment_method()
    {
        $agreement_id = $this->faker->word;
        $expressCheckoutToken = $this->faker->word;
        $addressId = $this->faker->numberBetween();
        $userId = rand();
        $customerId = null;

        $results = $this->call('PUT', '/payment-method', [
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'agreement_id' => $agreement_id,
            'express_checkout_token' => $expressCheckoutToken,
            'address_id' => $addressId,
            'user_id' => $userId,
            'customer_id' => $customerId
        ]);

        $this->assertEquals(200, $results->getStatusCode());

        $this->assertEquals([
            'id' => 1,
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null,
            'method' => [
                'id' => 1,
                'agreement_id' => $agreement_id,
                'express_checkout_token' => $expressCheckoutToken,
                'address_id' => $addressId,
                'expiration_date' => Carbon::now()->addYears(10),
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
        ], $results->decodeResponseJson()['results']);
    }

    public function test_update_payment_method_create_credit_card_validation()
    {
        $results = $this->call('PATCH', '/payment-method/' . rand(),
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE
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
            ],
            [
                "source" => "external_id",
                "detail" => "The external ID field is required when create a new credit card.",
            ]
        ], $results->decodeResponseJson()['errors']);
    }

    public function test_update_payment_method_update_credit_card_validation()
    {
        $results = $this->call('PATCH', '/payment-method/' . rand(),
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD,
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE
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
        $results = $this->call('PATCH', '/payment-method/' . rand(),
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL,
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE
            ]
        );

        $this->assertEquals(422, $results->getStatusCode());
        $this->assertEquals([
            [
                "source" => "agreement_id",
                "detail" => "The agreement id field is required when update payment method and use paypal.",
            ],
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

    public function test_update_payment_method_create_credit_card_response()
    {
        $cardYear = $this->faker->creditCardExpirationDate->format('Y');
        $cardMonth = $this->faker->month;
        $cardFingerprint = $this->faker->word;
        $cardLast4 = $this->faker->randomNumber(4);
        $cardType = $this->faker->creditCardType;
        $externalId = $this->faker->numberBetween();
        $cardHolderName = $this->faker->name;
        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE);

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card_year' => $cardYear,
                'card_month' => $cardMonth,
                'card_fingerprint' => $cardFingerprint,
                'card_number_last_four_digits' => $cardLast4,
                'cardholder_name' => $cardHolderName,
                'company_name' => $cardType,
                'external_id' => $externalId
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals([
            'id' => $paymentMethod['id'],
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'created_on' => $paymentMethod['created_on'],
            'updated_on' => Carbon::now()->toDateTimeString(),
            'method' => [
                'id' => $paymentMethod['method']['id'] + 1,
                'type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint' => $cardFingerprint,
                'last_four_digits' => $cardLast4,
                'cardholder_name' => $cardHolderName,
                'company_name' => $cardType,
                'external_id' => $externalId,
                'external_provider' => 'stripe',
                'expiration_date' => Carbon::create(
                    $cardYear,
                    $cardMonth,
                    12,
                    0,
                    0,
                    0
                ),
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
        ], $results->decodeResponseJson()['results']);
    }

    public function test_update_payment_method_update_credit_card_response()
    {
        $cardYear = $this->faker->creditCardExpirationDate->format('Y');
        $cardMonth = $this->faker->month;

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE);

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD,
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'card_year' => $cardYear,
                'card_month' => $cardMonth
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals([
            'id' => $paymentMethod['id'],
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'created_on' => $paymentMethod['created_on'],
            'updated_on' => Carbon::now()->toDateTimeString(),
            'method' => [
                'id' => $paymentMethod['method']['id'],
                'type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint' => $paymentMethod['method']['fingerprint'],
                'last_four_digits' => $paymentMethod['method']['last_four_digits'],
                'cardholder_name' => $paymentMethod['method']['cardholder_name'],
                'company_name' => $paymentMethod['method']['company_name'],
                'external_id' => $paymentMethod['method']['external_id'],
                'external_provider' => 'stripe',
                'expiration_date' => Carbon::create(
                    $cardYear,
                    $cardMonth,
                    12,
                    0,
                    0,
                    0
                ),
                'created_on' => $paymentMethod['created_on'],
                'updated_on' => Carbon::now()->toDateTimeString()
            ]
        ], $results->decodeResponseJson()['results']);
    }

    public function test_update_payment_method_use_paypal()
    {
        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE);
        $agreementId = rand();
        $expressCheckoutToken = $this->faker->word;
        $addressId = rand();

        $results = $this->call('PATCH', '/payment-method/' . $paymentMethod['id'],
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL,
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'agreement_id' => $agreementId,
                'express_checkout_token' => $expressCheckoutToken,
                'address_id' => $addressId
            ]
        );

        $this->assertEquals(201, $results->getStatusCode());
        $this->assertEquals([
            'id' => $paymentMethod['id'],
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'created_on' => $paymentMethod['created_on'],
            'updated_on' => Carbon::now()->toDateTimeString(),
            'method' => [
                'id' => $paymentMethod['method']['id'],
                'agreement_id' => $agreementId,
                'express_checkout_token' => $expressCheckoutToken,
                'address_id' => $addressId,
                'expiration_date' => Carbon::now()->addYears(10),
                'created_on' => $paymentMethod['created_on'],
                'updated_on' => Carbon::now()->toDateTimeString()
            ]
        ], $results->decodeResponseJson()['results']);
    }

    public function test_delete_payment_method_not_exist()
    {
        $randomId = rand();
        $results = $this->call('DELETE', '/payment-method/' . $randomId);

        $this->assertEquals(404, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Delete failed, payment method not found with id: " . $randomId,
            ]
            , $results->decodeResponseJson()['error']);
    }

    public function test_delete_payment_method_credit_card()
    {
        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE);
        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
                'method_type' => $paymentMethod['method_type'],
                'method_id' => 1
            ]
        );
        $this->assertDatabaseMissing(
            ConfigService::$tableCreditCard,
            [
                'id' => $paymentMethod['method']['id'],
                'method_type' => $paymentMethod['method_type'],
            ]
        );
    }

    public function test_delete_payment_method_paypal()
    {
        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE);
        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
                'method_type' => $paymentMethod['method_type'],
                'method_id' => 1
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
        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE);
        $currentUserId = rand();
        PaymentMethodRepository::$availableUserId = $currentUserId;

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);
        $this->assertEquals(404, $results->getStatusCode());
        $this->assertEquals(
            [
                "title" => "Not found.",
                "detail" => "Delete failed, payment method not found with id: " . $paymentMethod['id'],
            ]
            , $results->decodeResponseJson()['error']);
    }


    public function test_user_delete_own_payment_method_response()
    {
        $currentUserId = rand();
        PaymentMethodRepository::$availableUserId = $currentUserId;

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $this->faker->creditCardExpirationDate->format('Y'),
            $this->faker->month,
            $this->faker->word,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            rand(),
            null,
            '',
            null,
            $userId = $currentUserId,
            null);

        $results = $this->call('DELETE', '/payment-method/' . $paymentMethod['id']);

        $this->assertEquals(204, $results->getStatusCode());
        $this->assertDatabaseMissing(
            ConfigService::$tablePaymentMethod,
            [
                'id' => $paymentMethod['id'],
                'method_type' => $paymentMethod['method_type'],
                'method_id' => 1
            ]
        );
    }
}
