<?php

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class PaymentServiceTest extends EcommerceTestCase
{
    public function test_charge_users_existing_payment_method_credit_card()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $externalId = 'card_' . $this->faker->password;
        $externalCustomerId = 'cus_' . $this->faker->password;
        $cardExpirationYear = 2019;
        $cardExpirationMonth = 12;
        $cardExpirationDate = Carbon::createFromDate(
                $cardExpirationYear,
                $cardExpirationMonth
            )
            ->toDateTimeString();

        $creditCard = $this->fakeCreditCard([
            'fingerprint' => $this->faker->word,
            'last_four_digits' => $this->faker->randomNumber(4),
            'cardholder_name' => $this->faker->name,
            'company_name' => $this->faker->creditCardType,
            'expiration_date' => $cardExpirationDate,
            'external_id' => $externalId,
            'external_customer_id' => $externalCustomerId,
            'payment_gateway_name' => $brand
        ]);

        $billingAddress = $this->fakeAddress([
            'user_id' => $userId,
            'first_name' => null,
            'last_name' => null,
            'street_line_1' => null,
            'street_line_2' => null,
            'city' => null,
            'type' => ConfigService::$billingAddressType,
            'zip' => $this->faker->postcode,
            'state' => $this->faker->word,
            'country' => 'Canada',
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $creditCard['id'],
            'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
            'currency' => $currency,
            'billing_address_id' => $billingAddress['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn($fakerCustomer);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $creditCard['company_name'];
        $fakerCard->last4 = $creditCard['last_four_digits'];
        $fakerCard->exp_year = $cardExpirationYear;
        $fakerCard->exp_month = $cardExpirationMonth;
        $fakerCard->id = $externalId;
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn($fakerCard);

        $chargeId = $this->faker->word;

        $fakerCharge = new Charge();
        $fakerCharge->id = $chargeId;
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $this->faker->numberBetween(100, 200);
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $paymentAmountInBaseCurrency = $this->faker->randomFloat(2, 50, 100);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedConversionRate = $currencyService->getRate($currency);
        $expectedPaymentTotalDue = $currencyService->convertFromBase($paymentAmountInBaseCurrency, $currency);

        $paymentService = $this->app->make(PaymentService::class);

        $paymentService->chargeUsersExistingPaymentMethod(
            $brand,
            $paymentMethod['id'],
            $currency,
            $paymentAmountInBaseCurrency,
            $userId,
            Payment::TYPE_INITIAL_ORDER
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $chargeId,
                'external_provider' => Payment::EXTERNAL_PROVIDER_STRIPE,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'payment_method_id' => $paymentMethod['id'],
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_charge_users_existing_payment_method_paypal()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        ConfigService::$brand = $brand;

        $billingAddress = $this->fakeAddress([
            'user_id' => $userId,
            'first_name' => null,
            'last_name' => null,
            'street_line_1' => null,
            'street_line_2' => null,
            'city' => null,
            'type' => ConfigService::$billingAddressType,
            'zip' => $this->faker->postcode,
            'state' => $this->faker->word,
            'country' => 'Canada',
        ]);

        $billingAgreementExternalId = 'B-' . $this->faker->password;

        $paypalAgreement = $this->fakePaypalBillingAgreement([
            'external_id' => $billingAgreementExternalId,
            'payment_gateway_name' => $brand,
        ]);

        $paymentMethod = $this->fakePaymentMethod([
            'method_id' => $paypalAgreement['id'],
            'method_type' => PaymentMethod::TYPE_PAYPAL,
            'currency' => $currency,
            'billing_address_id' => $billingAddress['id']
        ]);

        $userPaymentMethod = $this->fakeUserPaymentMethod([
            'user_id' => $userId,
            'payment_method_id' => $paymentMethod['id'],
            'is_primary' => true
        ]);

        $transactionId = rand(1, 100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        $paymentAmountInBaseCurrency = $this->faker->randomFloat(2, 50, 100);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedConversionRate = $currencyService->getRate($currency);
        $expectedPaymentTotalDue = $currencyService->convertFromBase($paymentAmountInBaseCurrency, $currency);

        $paymentService = $this->app->make(PaymentService::class);

        $paymentService->chargeUsersExistingPaymentMethod(
            $brand,
            $paymentMethod['id'],
            $currency,
            $paymentAmountInBaseCurrency,
            $userId,
            Payment::TYPE_INITIAL_ORDER
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $transactionId,
                'external_provider' => Payment::EXTERNAL_PROVIDER_PAYPAL,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'payment_method_id' => $paymentMethod['id'],
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_charge_new_credit_cart_payment_method_existing_stripe_customer()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';

        $cardExpirationYear = 2019;
        $cardExpirationMonth = 12;
        $cardExpirationDate = Carbon::createFromDate(
            $cardExpirationYear,
            $cardExpirationMonth
        );

        $externalId = 'card_' . $this->faker->word;
        $externalCustomerId = 'cus_' . $this->faker->word;

        $creditCard = $this->fakeCreditCard(
            [
                'fingerprint' => $this->faker->word,
                'last_four_digits' => $this->faker->randomNumber(4),
                'cardholder_name' => $this->faker->name,
                'company_name' => $this->faker->creditCardType,
                'expiration_date' => $cardExpirationDate,
                'external_id' => $externalId,
                'external_customer_id' => $externalCustomerId,
                'payment_gateway_name' => $brand
            ]
        );

        $billingAddressData = $this->fakeAddress(
            [
                'user_id' => $userId,
                'first_name' => null,
                'last_name' => null,
                'street_line_1' => null,
                'street_line_2' => null,
                'city' => null,
                'type' => ConfigService::$billingAddressType,
                'zip' => $this->faker->postcode,
                'state' => $this->faker->word,
                'country' => 'Canada',
            ]
        );

        $paymentMethod = $this->fakePaymentMethod(
            [
                'method_id' => $creditCard['id'],
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'currency' => $this->faker->word,
                'billing_address_id' => $billingAddressData['id'],
            ]
        );

        $userPaymentMethod = $this->fakeUserPaymentMethod(
            [
                'user_id' => $userId,
                'payment_method_id' => $paymentMethod['id'],
                'is_primary' => true
            ]
        );

        $userStripeCustomerId = $this->fakeUserStripeCustomerId(
            [
                'user_id' => $userId,
                'stripe_customer_id' => $externalCustomerId
            ]
        );

        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $fakerCustomer->id = $externalCustomerId;

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn($fakerCustomer);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $creditCard['company_name'];
        $fakerCard->last4 = $creditCard['last_four_digits'];
        $fakerCard->exp_year = $cardExpirationYear;
        $fakerCard->exp_month = $cardExpirationMonth;
        $fakerCard->id = $externalId;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $paymentAmountInBaseCurrency = $this->faker->randomFloat(2, 50, 100);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedConversionRate = $currencyService->getRate($currency);
        $expectedPaymentTotalDue = $currencyService->convertFromBase($paymentAmountInBaseCurrency, $currency);

        $chargeId = $this->faker->word;

        $fakerCharge = new Charge();
        $fakerCharge->id = $chargeId;
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $expectedPaymentTotalDue;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $purchaser = new Purchaser();

        $purchaser->setId($userId);
        $purchaser->setEmail($this->faker->email);
        $purchaser->setType(Purchaser::USER_TYPE);
        $purchaser->setBrand($brand);

        $billingAddress = new Address();

        $billingAddress->setZip($billingAddressData['zip'])
            ->setState($this->faker->word)
            ->setCountry($billingAddressData['country'])
            ->setType(ConfigService::$billingAddressType);

        $stripeToken = $this->faker->word;

        $paymentService = $this->app->make(PaymentService::class);

        $paymentService->chargeNewCreditCartPaymentMethod(
            $purchaser,
            $billingAddress,
            $brand,
            $currency,
            $paymentAmountInBaseCurrency,
            $stripeToken,
            Payment::TYPE_INITIAL_ORDER,
            false
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $fakerCard->fingerprint,
                'last_four_digits' => $fakerCard->last4,
                'cardholder_name' => null,
                'company_name' => $fakerCard->brand,
                'expiration_date' => Carbon::createFromDate(
                    $fakerCard->exp_year,
                    $fakerCard->exp_month
                )
                    ->toDateTimeString(),
                'external_id' => $fakerCard->id,
                'external_customer_id' => $externalCustomerId,
                'payment_gateway_name' => $brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'currency' => $currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $chargeId,
                'external_provider' => Payment::EXTERNAL_PROVIDER_STRIPE,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_charge_new_credit_cart_payment_method_new_stripe_customer()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        $country = 'Canada';

        $cardExpirationYear = 2019;
        $cardExpirationMonth = 12;
        $cardExpirationDate = Carbon::createFromDate(
            $cardExpirationYear,
            $cardExpirationMonth
        );

        $externalId = 'card_' . $this->faker->word;
        $externalCustomerId = 'cus_' . $this->faker->word;

        $fakerCustomer = new Customer();
        $fakerCustomer->email = $this->faker->email;
        $fakerCustomer->id = $externalCustomerId;

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($fakerCustomer);

        $fakerToken = new Token();
        $this->stripeExternalHelperMock->method('retrieveToken')
            ->willReturn($fakerToken);

        $fakerCard = new Card();
        $fakerCard->fingerprint = $this->faker->word;
        $fakerCard->brand = $this->faker->creditCardType;
        $fakerCard->last4 = $this->faker->randomNumber(4, true);
        $fakerCard->exp_year = $cardExpirationYear;
        $fakerCard->exp_month = $cardExpirationMonth;
        $fakerCard->id = $externalId;
        $this->stripeExternalHelperMock->method('createCard')
            ->willReturn($fakerCard);

        $paymentAmountInBaseCurrency = $this->faker->randomFloat(2, 50, 100);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedConversionRate = $currencyService->getRate($currency);
        $expectedPaymentTotalDue = $currencyService->convertFromBase($paymentAmountInBaseCurrency, $currency);

        $chargeId = $this->faker->word;

        $fakerCharge = new Charge();
        $fakerCharge->id = $chargeId;
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $expectedPaymentTotalDue;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $purchaser = new Purchaser();

        $purchaser->setId($userId);
        $purchaser->setEmail($this->faker->email);
        $purchaser->setType(Purchaser::USER_TYPE);
        $purchaser->setBrand($brand);

        $billingAddress = new Address();

        $billingAddress->setZip($this->faker->postcode)
            ->setState($this->faker->word)
            ->setCountry($country)
            ->setType(ConfigService::$billingAddressType);

        $stripeToken = $this->faker->word;

        $paymentService = $this->app->make(PaymentService::class);

        $paymentService->chargeNewCreditCartPaymentMethod(
            $purchaser,
            $billingAddress,
            $brand,
            $currency,
            $paymentAmountInBaseCurrency,
            $stripeToken,
            Payment::TYPE_INITIAL_ORDER,
            false
        );

        $this->assertDatabaseHas(
            ConfigService::$tableCreditCard,
            [
                'fingerprint' => $fakerCard->fingerprint,
                'last_four_digits' => $fakerCard->last4,
                'cardholder_name' => null,
                'company_name' => $fakerCard->brand,
                'expiration_date' => Carbon::createFromDate(
                    $fakerCard->exp_year,
                    $fakerCard->exp_month
                )
                    ->toDateTimeString(),
                'external_id' => $fakerCard->id,
                'external_customer_id' => $externalCustomerId,
                'payment_gateway_name' => $brand,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethod::TYPE_CREDIT_CARD,
                'currency' => $currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $chargeId,
                'external_provider' => Payment::EXTERNAL_PROVIDER_STRIPE,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }

    public function test_charge_new_paypal_payment_method()
    {
        $userId = $this->createAndLogInNewUser();

        $currency = $this->getCurrency();
        $brand = 'drumeo';
        $country = 'Canada';

        $billingAgreementId = rand(1,100);

        $this->paypalExternalHelperMock->method('confirmAndCreateBillingAgreement')
            ->willReturn($billingAgreementId);

        $transactionId = rand(1,100);

        $this->paypalExternalHelperMock->method('createReferenceTransaction')
            ->willReturn($transactionId);

        $paymentAmountInBaseCurrency = $this->faker->randomFloat(2, 50, 100);

        $currencyService = $this->app->make(CurrencyService::class);

        $expectedConversionRate = $currencyService->getRate($currency);
        $expectedPaymentTotalDue = $currencyService->convertFromBase($paymentAmountInBaseCurrency, $currency);

        $purchaser = new Purchaser();

        $purchaser->setId($userId);
        $purchaser->setEmail($this->faker->email);
        $purchaser->setType(Purchaser::USER_TYPE);
        $purchaser->setBrand($brand);

        $billingAddress = new Address();

        $billingAddress->setZip($this->faker->postcode)
            ->setState($this->faker->word)
            ->setCountry($country)
            ->setType(ConfigService::$billingAddressType);

        $payPalToken = $this->faker->word;

        $paymentService = $this->app->make(PaymentService::class);

        $paymentService->chargeNewPayPalPaymentMethod(
            $purchaser,
            $billingAddress,
            $brand,
            $currency,
            $paymentAmountInBaseCurrency,
            $payPalToken,
            Payment::TYPE_INITIAL_ORDER,
            false
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePaypalBillingAgreement,
            [
                'external_id' => $billingAgreementId,
                'payment_gateway_name' => $brand,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePaymentMethod,
            [
                'method_type' => PaymentMethod::TYPE_PAYPAL,
                'currency' => $currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => round($expectedPaymentTotalDue, 2),
                'total_paid' => round($expectedPaymentTotalDue, 2),
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => Payment::TYPE_INITIAL_ORDER,
                'external_id' => $transactionId,
                'external_provider' => Payment::EXTERNAL_PROVIDER_PAYPAL,
                'status' => Payment::STATUS_PAID,
                'message' => null,
                'currency' => $currency,
                'created_at' => Carbon::now()->toDateTimeString()
            ]
        );
    }
}