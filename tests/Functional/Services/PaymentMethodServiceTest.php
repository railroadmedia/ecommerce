<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\PaymentGatewayFactory;
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


    CONST VALID_VISA_CARD_NUM = '4242424242424242';
    CONST VALID_EXPRESS_CHECKOUT_TOKEN = 'EC-07Y51763KD5814604';

    /**
     * @var PaymentMethodFactory
     */
    private $paymentMethodFactory;

    /**
     * @var PaymentGatewayFactory
     */
    private $paymentGatewayFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->classBeingTested = $this->app->make(PaymentMethodService::class);
        $this->paymentMethodFactory = $this->app->make(PaymentMethodFactory::class);
        $this->paymentGatewayFactory = $this->app->make(PaymentGatewayFactory::class);
    }

    public function test_user_store_credit_card_payment_method()
    {
        $fingerprint = self::VALID_VISA_CARD_NUM;
        $last4Digits = $this->faker->randomNumber(4);
        $cardHolderName = $this->faker->word;
        $companyName = $this->faker->creditCardType;
        $expirationYear = 2020;
        $expirationMonth = $this->faker->creditCardExpirationDate()->format('m');
        $userId = rand();
        $currency = $this->faker->currencyCode;
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->classBeingTested->store(
            PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $expirationYear,
            $expirationMonth,
            $fingerprint,
            $last4Digits,
            $cardHolderName,
            $companyName,
            '',
            null,
            $currency,
            $userId,
            null);

        $this->assertArraySubset([
            'method_type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            'method' => [
                'type' => PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
                'fingerprint' => $fingerprint,
                'last_four_digits' => $last4Digits,
                'cardholder_name' => $cardHolderName,
                'company_name' => $companyName,
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
            'updated_on' => null,
            'currency' => $currency,
            'user_id' => $userId,
            'customer_id' => null

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
                'last_four_digits' => $last4Digits,
                'cardholder_name' => $cardHolderName,
                'company_name' => $companyName,
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

    public function test_customer_store_paypal_payment_method()
    {
        $expressCheckoutToken = self::VALID_EXPRESS_CHECKOUT_TOKEN;
        $addressId = $this->faker->randomNumber();
        $customerId = rand();
        $currency = $this->faker->currencyCode;
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal', 'paypal_1');

        $paymentMethod = $this->classBeingTested->store(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            null,
            null,
            '',
            '',
            '',
            '',
            $expressCheckoutToken,
            $addressId,
            $currency,
            null,
            $customerId
        );

        $this->assertArraySubset([
            'id' => 1,
            'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
            'method' => [
                'id' => 1,
                'express_checkout_token' => $expressCheckoutToken,
                'address_id' => $addressId,
                'expiration_date' => Carbon::now()->addYears(10),
                'created_on' => Carbon::now()->toDateTimeString(),
                'updated_on' => null
            ],
            'created_on' => Carbon::now()->toDateTimeString(),
            'currency' => $currency,
            'updated_on' => null,
            'user_id' => null,
            'customer_id' => $customerId

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

    public function test_user_can_delete_its_payment_method()
    {
        $userId = rand();
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $this->faker->creditCardExpirationDate->format('Y'),
            $this->faker->creditCardExpirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            'EC-3WA8428214111473X',
            rand(),
            $userId,
            null);

        $this->assertTrue($this->classBeingTested->delete($paymentMethod['id'],$userId));
    }

    public function test_user_update_credit_card_expiration_date()
    {
        $userId = rand();
        $creditCardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

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
            $userId,
            null);

        $newExpirationYear = rand(2019, 2032);
        $newExpirationMonth = rand(10, 12);
        $updated = $this->classBeingTested->update(
            $paymentMethod['id'],
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD,
                'method_type' => $paymentMethod['method_type'],
                'payment_gateway' =>$paymentGateway['id'],
                'card_year' => $newExpirationYear,
                'card_month' => $newExpirationMonth
            ]);

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
        $cardExpirationDate = $this->faker->creditCardExpirationDate;
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe', 'stripe_1');

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
            $userId,
            null);

        $updated = $this->classBeingTested->update(
            $paymentMethod['id'],
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD,
                'method_type' => $paymentMethod['method_type'],
                'card_year' => $this->faker->creditCardExpirationDate->format('Y'),
                'card_month' => $this->faker->month,
                'card_fingerprint' => self::VALID_VISA_CARD_NUM,
                'card_number_last_four_digits' => $this->faker->randomNumber(4),
                'cardholder_name' => $this->faker->name,
                'company_name' => $this->faker->creditCardType,
                'external_id' => rand(),
                'payment_gateway' => $paymentGateway['id'],
                'user_id' => $userId,
                'customer_id' => null
            ]);
        $this->assertEquals($paymentMethod['id'] + 1, $updated['method']['id']);
    }

    public function test_user_update_to_paypal_payment_method()
    {
        $userId = rand();
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe','stripe_1');
        $expirationDate = $this->faker->creditCardExpirationDate;

        $paymentMethod = $this->paymentMethodFactory->store(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE,
            $paymentGateway['id'],
            $expirationDate->format('Y'),
            $expirationDate->format('m'),
            self::VALID_VISA_CARD_NUM,
            $this->faker->randomNumber(4),
            $this->faker->name,
            $this->faker->creditCardType,
            $this->faker->word,
            rand(),
            $userId,
            null);
        $paymentGatewayPaypal = $this->paymentGatewayFactory->store(ConfigService::$brand, 'paypal','paypal_1');

        $updated = $this->classBeingTested->update(
            $paymentMethod['id'], [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL,
                'method_type' => PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE,
                'agreement_id' => rand(),
                'express_checkout_token' => self::VALID_EXPRESS_CHECKOUT_TOKEN,
                'address_id' => rand(),
                'user_id' => $userId,
                'payment_gateway' => $paymentGatewayPaypal['id']
            ]
        );

        $this->assertEquals(PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE, $updated['method_type']);
    }

    public function test_admin_update_other_credit_card_expiration_date()
    {
        $paymentGateway = $this->paymentGatewayFactory->store(ConfigService::$brand, 'stripe','stripe_1');
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
            rand(),
            null);

        $newExpirationYear = $this->faker->creditCardExpirationDate->format('Y');
        $newExpirationMonth = $this->faker->creditCardExpirationDate->format('m');
        $updated = $this->classBeingTested->update(
            $paymentMethod['id'],
            [
                'update_method' => PaymentMethodService::UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD,
                'method_type' => $paymentMethod['method_type'],
                'card_year' => $newExpirationYear,
                'card_month' => $newExpirationMonth
            ]);

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
