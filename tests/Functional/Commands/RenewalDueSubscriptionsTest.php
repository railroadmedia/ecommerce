<?php

namespace Railroad\Ecommerce\Tests\Functional\Commands;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;

class RenewalDueSubscriptionsTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_command()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);

        $currency = $this->getCurrency();

        $currencyService = $this->app->make(CurrencyService::class);
        $taxService = $this->app->make(TaxService::class);

        $expectedConversionRate = $currencyService->getRate($currency);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        $expectedPaymentTotalDues = [];

        for ($i = 0; $i < 10; $i++) {

            $creditCard = $this->fakeCreditCard();

            $address = $this->fakeAddress(
                [
                    'type' => Address::BILLING_ADDRESS_TYPE,
                    'country' => 'Canada',
                    'region' => $this->faker->word,
                    'zip' => $this->faker->postcode
                ]
            );

            $paymentMethod = $this->fakePaymentMethod(
                [
                    'credit_card_id' => $creditCard['id'],
                    'currency' => $currency,
                    'billing_address_id' => $address['id']
                ]
            );

            $payment = $this->fakePayment(
                [
                    'payment_method_id' => $paymentMethod['id'],
                    'currency' => $currency,
                    'total_due' => $this->faker->numberBetween(1, 100),
                ]
            );

            $product = $this->fakeProduct(
                [
                    'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
                ]
            );

            $order = $this->fakeOrder();

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                    'quantity' => 1
                ]
            );

            $subscription = $this->fakeSubscription(
                [
                    'user_id' => $userId,
                    'type' => $this->faker->randomElement(
                        [Product::TYPE_DIGITAL_SUBSCRIPTION, config('ecommerce.type_payment_plan')]
                    ),
                    'start_date' => Carbon::now()
                        ->subYear(2),
                    'paid_until' => Carbon::now()
                        ->subDay(1),
                    'product_id' => $product['id'],
                    'currency' => $currency,
                    'order_id' => $order['id'],
                    'brand' => config('ecommerce.brand'),
                    'interval_type' => config('ecommerce.interval_type_monthly'),
                    'interval_count' => 1,
                    'total_cycles_paid' => 1,
                    'total_cycles_due' => $this->faker->numberBetween(2, 5),
                    'payment_method_id' => $paymentMethod['id'],
                ]
            );

            if ($subscription['type'] != config('ecommerce.type_payment_plan')) {
                $initialSubscriptions[] = $subscription;

                $billingAddressEntity = new Address();

                $billingAddressEntity->setCountry($address['country']);
                $billingAddressEntity->setRegion($address['region']);
                $billingAddressEntity->setZip($address['zip']);

                $vat = $taxService->getTaxesDueForProductCost(
                    $subscription['total_price'],
                    $billingAddressEntity->toStructure()
                );

                $paymentAmount = $currencyService->convertFromBase(
                    $subscription['total_price'] + $vat,
                    $currency
                );

                $expectedPaymentDues[$subscription['id']] = $paymentAmount;
            }
        }

        $this->artisan('renewalDueSubscriptions');

        for ($i = 0; $i < count($initialSubscriptions); $i++) {
            $this->assertDatabaseHas(
                'ecommerce_subscriptions',
                [
                    'id' => $initialSubscriptions[$i]['id'],
                    'paid_until' => Carbon::now()
                        ->addMonth($initialSubscriptions[$i]['interval_count'])
                        ->startOfDay()
                        ->toDateTimeString(),
                    'is_active' => 1,
                    'total_cycles_paid' => $initialSubscriptions[$i]['total_cycles_paid'] + 1,
                    'updated_at' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );

            // assert user products assignation
            $this->assertDatabaseHas(
                'ecommerce_user_products',
                [
                    'user_id' => $initialSubscriptions[$i]['user_id'],
                    'product_id' => $initialSubscriptions[$i]['product_id'],
                    'quantity' => 1,
                    'expiration_date' => Carbon::now()
                        ->addMonth($initialSubscriptions[$i]['interval_count'])
                        ->startOfDay()
                        ->toDateTimeString(),
                ]
            );

            $this->assertDatabaseHas(
                'ecommerce_payments',
                [
                    'total_due' => round($expectedPaymentDues[$initialSubscriptions[$i]['id']], 2),
                    'total_paid' => round($expectedPaymentDues[$initialSubscriptions[$i]['id']], 2),
                    'total_refunded' => 0,
                    'conversion_rate' => $expectedConversionRate,
                    'type' => config('ecommerce.renewal_payment_type'),
                    'external_id' => $fakerCharge->id,
                    'external_provider' => 'stripe',
                    'status' => 'succeeded',
                    'message' => '',
                    'currency' => $currency,
                    'created_at' => Carbon::now()
                        ->toDateTimeString()
                ]
            );
        }
    }

    public function test_command_payment_fails()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);

        $currency = $this->getCurrency();

        $currencyService = $this->app->make(CurrencyService::class);
        $taxService = $this->app->make(TaxService::class);

        $expectedConversionRate = $currencyService->getRate($currency);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = $currency;
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willThrowException(new PaymentFailedException('No funds.'));

        $expectedPaymentTotalDues = [];

        $creditCard = $this->fakeCreditCard();

        $address = $this->fakeAddress(
            [
                'type' => Address::BILLING_ADDRESS_TYPE,
                'country' => 'Canada',
                'region' => $this->faker->word,
                'zip' => $this->faker->postcode
            ]
        );

        $paymentMethod = $this->fakePaymentMethod(
            [
                'credit_card_id' => $creditCard['id'],
                'currency' => $currency,
                'billing_address_id' => $address['id']
            ]
        );

        $product = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        $order = $this->fakeOrder();

        $orderItem = $this->fakeOrderItem(
            [
                'order_id' => $order['id'],
                'product_id' => $product['id'],
                'quantity' => 1
            ]
        );

        $subscription = $this->fakeSubscription(
            [
                'user_id' => $userId,
                'type' => $this->faker->randomElement(
                    [Product::TYPE_DIGITAL_SUBSCRIPTION, config('ecommerce.type_payment_plan')]
                ),
                'start_date' => Carbon::now()
                    ->subYear(2),
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'product_id' => $product['id'],
                'currency' => $currency,
                'order_id' => $order['id'],
                'brand' => config('ecommerce.brand'),
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 1,
                'total_cycles_paid' => 1,
                'total_cycles_due' => $this->faker->numberBetween(2, 5),
                'payment_method_id' => $paymentMethod['id'],
            ]
        );

        $userProduct = $this->fakeUserProduct(
            [
                'user_id' => $userId,
                'product_id' => $product['id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addDays(3)
                    ->toDateTimeString()
            ]
        );

        $initialSubscriptions[] = $subscription;

        $billingAddressEntity = new Address();

        $billingAddressEntity->setCountry($address['country']);
        $billingAddressEntity->setRegion($address['region']);
        $billingAddressEntity->setZip($address['zip']);

        $paymentAmount = $currencyService->convertFromBase(
            $subscription['total_price'],
            $currency
        );

        $expectedPaymentDues[$subscription['id']] = $paymentAmount;

        $this->artisan('renewalDueSubscriptions');

        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $subscription['id'],
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'is_active' => 0,
                'total_cycles_paid' => $subscription['total_cycles_paid'],
                'updated_at' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        // assert user products assignation
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $subscription['user_id'],
                'product_id' => $subscription['product_id'],
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->subDay(1)
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => round($paymentAmount, 2),
                'total_paid' => 0,
                'total_refunded' => 0,
                'conversion_rate' => $expectedConversionRate,
                'type' => config('ecommerce.renewal_payment_type'),
                'external_id' => null,
                'external_provider' => 'stripe',
                'status' => 'failed',
                'message' => 'Payment failed: No funds.',
                'currency' => $currency,
                'created_at' => Carbon::now()
                    ->toDateTimeString()
            ]
        );
    }

    public function test_ancient_subscriptions_deactivation()
    {
        $userId = $this->createAndLogInNewUser();
        $due = $this->faker->numberBetween(0, 1000);

        $currency = $this->getCurrency();

        $currencyService = $this->app->make(CurrencyService::class);
        $taxService = $this->app->make(TaxService::class);

        $expectedConversionRate = $currencyService->getRate($currency);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());
        $fakerCharge = new Charge();
        $fakerCharge->currency = 'cad';
        $fakerCharge->amount = $due;
        $fakerCharge->status = 'succeeded';
        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($fakerCharge);

        // ancient subscriptions
        for ($i = 0; $i < 2; $i++) {
            $creditCard = $this->fakeCreditCard();

            $address = $this->fakeAddress(
                [
                    'type' => Address::BILLING_ADDRESS_TYPE,
                    'country' => 'Canada',
                    'region' => $this->faker->word,
                    'zip' => $this->faker->postcode
                ]
            );

            $paymentMethod = $this->fakePaymentMethod(
                [
                    'credit_card_id' => $creditCard['id'],
                    'currency' => $currency,
                    'billing_address_id' => $address['id']
                ]
            );

            $payment = $this->fakePayment(
                [
                    'payment_method_id' => $paymentMethod['id'],
                    'currency' => $currency,
                    'total_due' => $this->faker->numberBetween(1, 100),
                ]
            );

            $product = $this->fakeProduct(
                [
                    'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
                ]
            );

            $order = $this->fakeOrder();

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                    'quantity' => 1
                ]
            );

            $subscription = $this->fakeSubscription(
                [
                    'user_id' => $userId,
                    'type' => $this->faker->randomElement(
                        [Product::TYPE_DIGITAL_SUBSCRIPTION, config('ecommerce.type_payment_plan')]
                    ),
                    'start_date' => Carbon::now()
                        ->subYear(2),
                    'paid_until' => Carbon::now()
                        ->subMonths((config('ecommerce.paypal.subscription_renewal_date') ?? 1) + 1),
                    'product_id' => $product['id'],
                    'currency' => $currency,
                    'order_id' => $order['id'],
                    'brand' => config('ecommerce.brand'),
                    'interval_type' => config('ecommerce.interval_type_monthly'),
                    'interval_count' => 1,
                    'total_cycles_paid' => 1,
                    'total_cycles_due' => $this->faker->numberBetween(2, 5),
                    'payment_method_id' => $paymentMethod['id'],
                    'is_active' => true,
                    'canceled_on' => null,
                ]
            );

            $oldSubscriptions[] = $subscription;

            $userProduct = $this->fakeUserProduct(
                [
                    'user_id' => $userId,
                    'product_id' => $product['id'],
                    'expiration_date' => $subscription['paid_until'],
                    'quantity' => 1
                ]
            );
        }

        for ($i = 0; $i < 10; $i++) {
            $creditCard = $this->fakeCreditCard();

            $address = $this->fakeAddress(
                [
                    'type' => Address::BILLING_ADDRESS_TYPE,
                    'country' => 'Canada',
                    'region' => $this->faker->word,
                    'zip' => $this->faker->postcode
                ]
            );

            $paymentMethod = $this->fakePaymentMethod(
                [
                    'credit_card_id' => $creditCard['id'],
                    'currency' => $currency,
                    'billing_address_id' => $address['id']
                ]
            );

            $payment = $this->fakePayment(
                [
                    'payment_method_id' => $paymentMethod['id'],
                    'currency' => $currency,
                    'total_due' => $this->faker->numberBetween(1, 100),
                ]
            );

            $product = $this->fakeProduct(
                [
                    'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
                ]
            );

            $order = $this->fakeOrder();

            $orderItem = $this->fakeOrderItem(
                [
                    'order_id' => $order['id'],
                    'product_id' => $product['id'],
                    'quantity' => 1
                ]
            );

            $subscription = $this->fakeSubscription(
                [
                    'user_id' => $userId,
                    'type' => $this->faker->randomElement(
                        [Product::TYPE_DIGITAL_SUBSCRIPTION, config('ecommerce.type_payment_plan')]
                    ),
                    'start_date' => Carbon::now()
                        ->subYear(2),
                    'paid_until' => Carbon::now()
                        ->subDay(1),
                    'product_id' => $product['id'],
                    'currency' => $currency,
                    'order_id' => $order['id'],
                    'brand' => config('ecommerce.brand'),
                    'interval_type' => config('ecommerce.interval_type_monthly'),
                    'interval_count' => 1,
                    'total_cycles_paid' => 1,
                    'total_cycles_due' => $this->faker->numberBetween(2, 5),
                    'payment_method_id' => $paymentMethod['id'],
                ]
            );

            if ($subscription['type'] != config('ecommerce.type_payment_plan')) {

                $initialSubscriptions[] = $subscription;

                $billingAddressEntity = new Address();

                $billingAddressEntity->setCountry($address['country']);
                $billingAddressEntity->setRegion($address['region']);
                $billingAddressEntity->setZip($address['zip']);

                $vat = $taxService->getTaxesDueForProductCost(
                    $subscription['total_price'],
                    $billingAddressEntity->toStructure()
                );

                $paymentAmount = $currencyService->convertFromBase(
                    $subscription['total_price'] + $vat,
                    $currency
                );

                $expectedPaymentDues[$subscription['id']] = $paymentAmount;
            }
        }

        $this->artisan('renewalDueSubscriptions');

        foreach ($oldSubscriptions as $deactivatedSubscription) {
            $this->assertDatabaseHas(
                'ecommerce_subscriptions',
                [
                    'id' => $deactivatedSubscription['id'],
                    'is_active' => false,
                    'updated_at' => Carbon::now()
                        ->toDateTimeString(),
                    'canceled_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );
        }

        for ($i = 0; $i < count($initialSubscriptions); $i++) {

            $this->assertDatabaseHas(
                'ecommerce_subscriptions',
                [
                    'id' => $initialSubscriptions[$i]['id'],
                    'paid_until' => Carbon::now()
                        ->addMonth($initialSubscriptions[$i]['interval_count'])
                        ->startOfDay()
                        ->toDateTimeString(),
                    'is_active' => 1,
                    'total_cycles_paid' => $initialSubscriptions[$i]['total_cycles_paid'] + 1,
                    'updated_at' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );

            // assert user products assignation
            $this->assertDatabaseHas(
                'ecommerce_user_products',
                [
                    'user_id' => $initialSubscriptions[$i]['user_id'],
                    'product_id' => $initialSubscriptions[$i]['product_id'],
                    'quantity' => 1,
                    'expiration_date' => Carbon::now()
                        ->addMonth($initialSubscriptions[$i]['interval_count'])
                        ->startOfDay()
                        ->toDateTimeString(),
                ]
            );

            $this->assertDatabaseHas(
                'ecommerce_payments',
                [
                    'total_due' => round($expectedPaymentDues[$initialSubscriptions[$i]['id']], 2),
                    'total_paid' => round($expectedPaymentDues[$initialSubscriptions[$i]['id']], 2),
                    'total_refunded' => 0,
                    'conversion_rate' => $expectedConversionRate,
                    'type' => config('ecommerce.renewal_payment_type'),
                    'external_id' => $fakerCharge->id,
                    'external_provider' => 'stripe',
                    'status' => 'succeeded',
                    'message' => '',
                    'currency' => $currency,
                    'created_at' => Carbon::now()
                        ->toDateTimeString()
                ]
            );
        }
    }
}
