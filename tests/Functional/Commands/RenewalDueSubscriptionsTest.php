<?php

namespace Railroad\Ecommerce\Tests\Functional\Commands;

use Carbon\Carbon;
use Railroad\ActionLog\Services\ActionLogService;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
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
        $initialSubscriptions = [];

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
                    'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                    'subscription_interval_type' => 'month',
                    'subscription_interval_count' => 1,
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

            $subscriptionPrice = $this->faker->numberBetween(50, 100);
            $billingAddressEntity = new Address();

            $billingAddressEntity->setCountry($address['country']);
            $billingAddressEntity->setRegion($address['region']);
            $billingAddressEntity->setZip($address['zip']);

            $vat = $taxService->getTaxesDueForProductCost(
                $subscriptionPrice,
                $billingAddressEntity->toStructure()
            );

            $subscription = $this->fakeSubscription(
                [
                    'user_id' => $userId,
                    'type' => $this->faker->randomElement(
                        [Subscription::TYPE_SUBSCRIPTION, config('ecommerce.type_payment_plan')]
                    ),
                    'start_date' => Carbon::now()
                        ->subYear(2),
                    'paid_until' => Carbon::now()
                        ->subDay(1),
                    'is_active' => true,
                    'canceled_on' => null,
                    'product_id' => $product['id'],
                    'currency' => $currency,
                    'order_id' => $order['id'],
                    'brand' => config('ecommerce.brand'),
                    'interval_type' => config('ecommerce.interval_type_monthly'),
                    'interval_count' => 1,
                    'total_cycles_paid' => 1,
                    'total_cycles_due' => $this->faker->numberBetween(2, 5),
                    'payment_method_id' => $paymentMethod['id'],
                    'total_price' => $subscriptionPrice,
                    'tax' => $vat,
                ]
            );

            if ($subscription['type'] != config('ecommerce.type_payment_plan')) {
                $initialSubscriptions[] = $subscription;

                $paymentAmount = $currencyService->convertFromBase(
                    $subscription['total_price'] + $vat,
                    $currency
                );

                $expectedPaymentDues[$subscription['id']] = $paymentAmount;
            }
        }

        // add mobile subscription
        $creditCardMobileSub = $this->fakeCreditCard();

        $addressMobileSub = $this->fakeAddress(
            [
                'type' => Address::BILLING_ADDRESS_TYPE,
                'country' => 'Canada',
                'region' => $this->faker->word,
                'zip' => $this->faker->postcode
            ]
        );

        $paymentMethodMobileSub = $this->fakePaymentMethod(
            [
                'credit_card_id' => $creditCardMobileSub['id'],
                'currency' => $currency,
                'billing_address_id' => $addressMobileSub['id']
            ]
        );

        $productMobileSub = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        $orderMobileSub = $this->fakeOrder();

        $orderItemMobileSub = $this->fakeOrderItem(
            [
                'order_id' => $orderMobileSub['id'],
                'product_id' => $productMobileSub['id'],
                'quantity' => 1
            ]
        );

        $mobileSubscription = $this->fakeSubscription(
            [
                'user_id' => $userId,
                'type' => $this->faker->randomElement(
                    [Subscription::TYPE_APPLE_SUBSCRIPTION, Subscription::TYPE_GOOGLE_SUBSCRIPTION]
                ),
                'start_date' => Carbon::now()
                    ->subYear(2),
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'is_active' => true,
                'canceled_on' => null,
                'product_id' => $productMobileSub['id'],
                'currency' => $currency,
                'order_id' => $orderMobileSub['id'],
                'brand' => config('ecommerce.brand'),
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 1,
                'total_cycles_paid' => 1,
                'total_cycles_due' => $this->faker->numberBetween(2, 5),
                'payment_method_id' => $paymentMethod['id'],
                'total_price' => $subscriptionPrice,
                'tax' => $vat,
            ]
        );

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
                        ->addDays(config('ecommerce.days_before_access_revoked_after_expiry'))
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

            $this->assertDatabaseHas(
                'railactionlog_actions_log',
                [
                    'brand' => $initialSubscriptions[$i]['brand'],
                    'resource_name' => Subscription::class,
                    'resource_id' => $initialSubscriptions[$i]['id'],
                    'action_name' => Subscription::ACTION_RENEW,
                    'actor' => ActionLogService::ACTOR_COMMAND,
                    'actor_id' => null,
                    'actor_role' => ActionLogService::ROLE_COMMAND,
                ]
            );
        }

        // assert mobile subscription was not renewed
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $mobileSubscription['id'],
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'is_active' => 1,
            ]
        );
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

        $billingAddressEntity = new Address();

        $billingAddressEntity->setCountry($address['country']);
        $billingAddressEntity->setRegion($address['region']);
        $billingAddressEntity->setZip($address['zip']);

        $subscriptionPrice = $this->faker->numberBetween(50, 100);

        $vat = $taxService->getTaxesDueForProductCost(
            $subscriptionPrice,
            $billingAddressEntity->toStructure()
        );

        $subscription = $this->fakeSubscription(
            [
                'user_id' => $userId,
                'type' => Subscription::TYPE_SUBSCRIPTION,
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
                'total_price' => $subscriptionPrice,
                'tax' => $vat,
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

        $paymentAmount = $currencyService->convertFromBase(
            $subscription['total_price'] + $vat,
            $currency
        );

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
                    ->addDays(4)
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

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $subscription['brand'],
                'resource_name' => Subscription::class,
                'resource_id' => $subscription['id'],
                'action_name' => Subscription::ACTION_DEACTIVATED,
                'actor' => ActionLogService::ACTOR_COMMAND,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_COMMAND,
            ]
        );

        $this->assertDatabaseHas(
            'railactionlog_actions_log',
            [
                'brand' => $subscription['brand'],
                'resource_name' => Payment::class,
                'resource_id' => 1,
                'action_name' => Payment::ACTION_FAILED_RENEW,
                'actor' => ActionLogService::ACTOR_COMMAND,
                'actor_id' => null,
                'actor_role' => ActionLogService::ROLE_COMMAND,
            ]
        );
    }

    public function test_command_no_addresses()
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
        $initialSubscriptions = [];

        for ($i = 0; $i < 1; $i++) {

            $creditCard = $this->fakeCreditCard();

            $paymentMethod = $this->fakePaymentMethod(
                [
                    'credit_card_id' => $creditCard['id'],
                    'currency' => $currency,
                    'billing_address_id' => null
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
                    'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                    'subscription_interval_type' => 'month',
                    'subscription_interval_count' => 1,
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

            $subscriptionPrice = $this->faker->numberBetween(50, 100);

            $vat = 3.99;

            $subscription = $this->fakeSubscription(
                [
                    'user_id' => $userId,
                    'type' => $this->faker->randomElement(
                        [Subscription::TYPE_SUBSCRIPTION, config('ecommerce.type_payment_plan')]
                    ),
                    'start_date' => Carbon::now()
                        ->subYear(2),
                    'paid_until' => Carbon::now()
                        ->subDay(1),
                    'is_active' => true,
                    'canceled_on' => null,
                    'product_id' => $product['id'],
                    'currency' => $currency,
                    'order_id' => $order['id'],
                    'brand' => config('ecommerce.brand'),
                    'interval_type' => config('ecommerce.interval_type_monthly'),
                    'interval_count' => 1,
                    'total_cycles_paid' => 1,
                    'total_cycles_due' => $this->faker->numberBetween(2, 5),
                    'payment_method_id' => $paymentMethod['id'],
                    'total_price' => $subscriptionPrice,
                    'tax' => $vat,
                ]
            );

            if ($subscription['type'] != config('ecommerce.type_payment_plan')) {
                $initialSubscriptions[] = $subscription;

                $paymentAmount = $currencyService->convertFromBase(
                    $subscription['total_price'],
                    $currency
                );

                $expectedPaymentDues[$subscription['id']] = $paymentAmount;
            }
        }

        // add mobile subscription
        $creditCardMobileSub = $this->fakeCreditCard();

        $addressMobileSub = $this->fakeAddress(
            [
                'type' => Address::BILLING_ADDRESS_TYPE,
                'country' => 'Canada',
                'region' => $this->faker->word,
                'zip' => $this->faker->postcode
            ]
        );

        $paymentMethodMobileSub = $this->fakePaymentMethod(
            [
                'credit_card_id' => $creditCardMobileSub['id'],
                'currency' => $currency,
                'billing_address_id' => $addressMobileSub['id']
            ]
        );

        $productMobileSub = $this->fakeProduct(
            [
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION
            ]
        );

        $orderMobileSub = $this->fakeOrder();

        $orderItemMobileSub = $this->fakeOrderItem(
            [
                'order_id' => $orderMobileSub['id'],
                'product_id' => $productMobileSub['id'],
                'quantity' => 1
            ]
        );

        $mobileSubscription = $this->fakeSubscription(
            [
                'user_id' => $userId,
                'type' => $this->faker->randomElement(
                    [Subscription::TYPE_APPLE_SUBSCRIPTION, Subscription::TYPE_GOOGLE_SUBSCRIPTION]
                ),
                'start_date' => Carbon::now()
                    ->subYear(2),
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'is_active' => true,
                'canceled_on' => null,
                'product_id' => $productMobileSub['id'],
                'currency' => $currency,
                'order_id' => $orderMobileSub['id'],
                'brand' => config('ecommerce.brand'),
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 1,
                'total_cycles_paid' => 1,
                'total_cycles_due' => $this->faker->numberBetween(2, 5),
                'payment_method_id' => $paymentMethod['id'],
                'total_price' => $subscriptionPrice,
                'tax' => $vat,
            ]
        );

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
                        ->addDays(config('ecommerce.days_before_access_revoked_after_expiry'))
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

            $this->assertDatabaseHas(
                'railactionlog_actions_log',
                [
                    'brand' => $initialSubscriptions[$i]['brand'],
                    'resource_name' => Subscription::class,
                    'resource_id' => $initialSubscriptions[$i]['id'],
                    'action_name' => Subscription::ACTION_RENEW,
                    'actor' => ActionLogService::ACTOR_COMMAND,
                    'actor_id' => null,
                    'actor_role' => ActionLogService::ROLE_COMMAND,
                ]
            );
        }

        // assert mobile subscription was not renewed
        $this->assertDatabaseHas(
            'ecommerce_subscriptions',
            [
                'id' => $mobileSubscription['id'],
                'paid_until' => Carbon::now()
                    ->subDay(1),
                'is_active' => 1,
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

            $subscriptionPrice = $this->faker->numberBetween(50, 100);
            $billingAddressEntity = new Address();

            $billingAddressEntity->setCountry($address['country']);
            $billingAddressEntity->setRegion($address['region']);
            $billingAddressEntity->setZip($address['zip']);

            $vat = $taxService->getTaxesDueForProductCost(
                $subscriptionPrice,
                $billingAddressEntity->toStructure()
            );

            $subscription = $this->fakeSubscription(
                [
                    'user_id' => $userId,
                    'type' => $this->faker->randomElement(
                        [Subscription::TYPE_SUBSCRIPTION, config('ecommerce.type_payment_plan')]
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
                    'total_price' => $subscriptionPrice,
                    'tax' => $vat,
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

            $subscriptionPrice = $this->faker->numberBetween(50, 100);
            $billingAddressEntity = new Address();

            $billingAddressEntity->setCountry($address['country']);
            $billingAddressEntity->setRegion($address['region']);
            $billingAddressEntity->setZip($address['zip']);

            $vat = $taxService->getTaxesDueForProductCost(
                $subscriptionPrice,
                $billingAddressEntity->toStructure()
            );

            $subscription = $this->fakeSubscription(
                [
                    'user_id' => $userId,
                    'type' => $this->faker->randomElement(
                        [Subscription::TYPE_SUBSCRIPTION, config('ecommerce.type_payment_plan')]
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
                    'total_price' => $subscriptionPrice,
                    'tax' => $vat,
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

            $this->assertDatabaseHas(
                'railactionlog_actions_log',
                [
                    'brand' => $deactivatedSubscription['brand'],
                    'resource_name' => Subscription::class,
                    'resource_id' => $deactivatedSubscription['id'],
                    'action_name' => Subscription::ACTION_DEACTIVATED,
                    'actor' => ActionLogService::ACTOR_COMMAND,
                    'actor_id' => null,
                    'actor_role' => ActionLogService::ROLE_COMMAND,
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
                        ->addDays(5)
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

            $this->assertDatabaseHas(
                'railactionlog_actions_log',
                [
                    'brand' => $initialSubscriptions[$i]['brand'],
                    'resource_name' => Subscription::class,
                    'resource_id' => $initialSubscriptions[$i]['id'],
                    'action_name' => Subscription::ACTION_RENEW,
                    'actor' => ActionLogService::ACTOR_COMMAND,
                    'actor_id' => null,
                    'actor_role' => ActionLogService::ROLE_COMMAND,
                ]
            );
        }
    }
}
