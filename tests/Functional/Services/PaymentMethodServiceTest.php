<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\PaymentMethodFactory;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class PaymentMethodServiceTest extends EcommerceTestCase
{
    /**
     * @var PaymentMethodService
     */
    protected $classBeingTested;

    /**
     * @var PaymentMethodFactory
     */
    protected $paymentMethodFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(PaymentMethodService::class);
        $this->paymentMethodFactory = $this->app->make(PaymentMethodFactory::class);
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
        PaymentMethodRepository::$availableUserId = $userId;

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
        PaymentMethodRepository::$availableUserId = null;
        PaymentMethodRepository::$availableCustomerId = $customerId;

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

    public function test_admin_create_user_payment_method()
    {
        PaymentMethodRepository::$availableUserId = null;
        PaymentMethodRepository::$availableCustomerId = null;
        $userId = rand();
        $agreementId = $this->faker->randomNumber();
        $expressCheckoutToken = $this->faker->word;
        $addressId = $this->faker->randomNumber();

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
            $userId,
            null
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

    public function test_admin_delete_payment_method()
    {
        PaymentMethodRepository::$availableUserId = null;
        PaymentMethodRepository::$availableCustomerId = null;
        $paymentMethod = $this->paymentMethodFactory->store();

        $this->assertTrue($this->classBeingTested->delete($paymentMethod['id']));
    }

    public function test_user_can_not_delete_other_payment_method()
    {
        PaymentMethodRepository::$availableUserId = rand();
        $paymentMethod = $this->paymentMethodFactory->store();

        $this->assertNull($this->classBeingTested->delete($paymentMethod['id']));
    }

    public function test_user_can_delete_its_payment_method()
    {
        $userId = rand();
        PaymentMethodRepository::$availableUserId = $userId;
        $paymentMethod = $this->paymentMethodFactory->store($this->faker->randomElement([PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE]),
            rand(2018, 2022),
            rand(01, 12),
            $this->faker->word,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            rand(),
            rand(),
            $this->faker->word,
            rand(),
            $userId,
            null);

        $this->assertTrue($this->classBeingTested->delete($paymentMethod['id']));
    }

    public function test_user_update_credit_card_expiration_date()
    {
        $userId = rand();
        PaymentMethodRepository::$availableUserId = $userId;
        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            rand(2018, 2022),
            rand(01, 12),
            $this->faker->word,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            rand(),
            rand(),
            $this->faker->word,
            rand(),
            $userId,
            null);

        $newExpirationYear = rand(2019, 2032);
        $newExpirationMonth = rand(10, 12);
        $updated = $this->classBeingTested->update(
            $paymentMethod['id'],
            'update-current-credit-card',
            $paymentMethod['method_type'],
            $newExpirationYear,
            $newExpirationMonth);

        $this->assertEquals(
            Carbon::create(
                $newExpirationYear,
                $newExpirationMonth,
                12,
                0,
                0,
                0
            ),
            $updated['method']['expiration_date']);
    }

    public function test_user_define_new_credit_card_payment_method()
    {
        $userId = rand();
        PaymentMethodRepository::$availableUserId = $userId;

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            rand(2018, 2022),
            rand(01, 12),
            $this->faker->word,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            rand(),
            rand(),
            $this->faker->word,
            rand(),
            $userId,
            null);

        $updated = $this->classBeingTested->update(
            $paymentMethod['id'],
            'create-credit-card',
            $paymentMethod['method_type'],
            rand(2018, 2022),
            rand(01, 12),
            $this->faker->word,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            rand(),
            rand(),
            $this->faker->word,
            rand(),
            $userId,
            null);

        $this->assertEquals($paymentMethod['id'] + 1, $updated['method']['id']);
    }

    public function test_user_update_to_paypal_payment_method()
    {
        $userId = rand();
        PaymentMethodRepository::$availableUserId = $userId;

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            rand(2018, 2022),
            rand(01, 12),
            $this->faker->word,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            rand(),
            rand(),
            $this->faker->word,
            rand(),
            $userId,
            null);

        $updated = $this->classBeingTested->update(
            $paymentMethod['id'],
            'use-paypal',
            PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            null,
            null,
            '',
            '',
            '',
            '',
            null,
            rand(),
            $this->faker->word,
            rand());

        $this->assertEquals(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE, $updated['method_type']);
    }

    public function test_admin_update_other_credit_card_expiration_date()
    {
        PaymentMethodRepository::$availableUserId = null;
        PaymentMethodRepository::$availableCustomerId = null;

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            rand(2018, 2022),
            rand(01, 12),
            $this->faker->word,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            rand(),
            rand(),
            $this->faker->word,
            rand(),
            rand(),
            null);

        $newExpirationYear = rand(2019, 2032);
        $newExpirationMonth = rand(10, 12);
        $updated = $this->classBeingTested->update(
            $paymentMethod['id'],
            'update-current-credit-card',
            $paymentMethod['method_type'],
            $newExpirationYear,
            $newExpirationMonth);

        $this->assertEquals(
            Carbon::create(
                $newExpirationYear,
                $newExpirationMonth,
                12,
                0,
                0,
                0
            ),
            $updated['method']['expiration_date']);
    }
}
