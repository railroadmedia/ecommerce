<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaymentMethodServiceTest extends EcommerceTestCase
{
    /**
     * @var PaymentMethodService
     */
    protected $classBeingTested;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(PaymentMethodService::class);
    }

    public function test_store_credit_card_payment_method()
    {
        $fingerprint = $this->faker->randomNumber();
        $last4Digits = $this->faker->randomNumber(4);
        $cardHolderName = $this->faker->word;
        $companyName = $this->faker->creditCardType;
        $externalId = $this->faker->randomNumber();
        $expirationYear = 2020;
        $expirationMonth = $this->faker->randomNumber(1);
        $userId = rand();

        $paymentMethod = $this->classBeingTested->store(
            PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $expirationYear,
            $expirationMonth,
            $fingerprint,
            $last4Digits,
            $cardHolderName,
            $companyName,
            $externalId,
            null,
            '',
            '',
            $userId);
        $this->assertEquals([
            'id' => 1,
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method' => [
                'id' => 1,
                'type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint' => $fingerprint,
                'last_four_digits' => $last4Digits,
                'cardholder_name' => $cardHolderName,
                'company_name' => $companyName,
                'external_id' => $externalId,
                'external_provider' => ConfigService::$creditCard['external_provider'],
                'expiration_date' => Carbon::create(
                    $expirationYear,
                    $expirationMonth,
                    12,
                    0,
                    0,
                    0
                ),
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null

            ],
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null

        ], $paymentMethod);

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id' => 1,
                'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'method_id' => 1
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'id' => 1,
                'type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint' => $fingerprint,
                'last_four_digits' => $last4Digits,
                'cardholder_name' => $cardHolderName,
                'company_name' => $companyName,
                'external_id' => $externalId,
                'external_provider' => ConfigService::$creditCard['external_provider'],
                'expiration_date' => Carbon::create(
                    $expirationYear,
                    $expirationMonth,
                    12,
                    0,
                    0,
                    0
                ),
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableUserPaymentMethods,
            [
                'id' => 1,
                'payment_method_id' => 1,
                'user_id' => $userId
            ]
        );
    }

    public function test_store_paypal_payment_method()
    {
        $agreementId = $this->faker->randomNumber();
        $expressCheckoutToken = $this->faker->word;
        $addressId = $this->faker->randomNumber();
        $customerId = rand();
        $paymentMethod = $this->classBeingTested->store(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            null,
            null,
            '',
            '',
            '',
            '',
            null,
            $agreementId,
            $expressCheckoutToken,
            $addressId,
        null,
        $customerId
        );
        $this->assertEquals([
            'id' => 1,
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'method' => [
                'id' => 1,
                'agreement_id' => $agreementId,
                'express_checkout_token' => $expressCheckoutToken,
                'address_id' => $addressId,
                'expiration_date' => Carbon::now()->addYears(10),
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ],
            'created_on' => Carbon::now()->toDateTimeString(),
            'updated_on' => null

        ], $paymentMethod);

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'id' => 1,
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'method_id' => 1
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'id' => 1,
                'agreement_id' => $agreementId,
                'express_checkout_token' => $expressCheckoutToken,
                'address_id' => $addressId,
                'expiration_date' => Carbon::now()->addYears(10),
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCustomerPaymentMethods,
            [
                'id' => 1,
                'payment_method_id' => 1,
                'customer_id' => $customerId
            ]
        );
    }
}
