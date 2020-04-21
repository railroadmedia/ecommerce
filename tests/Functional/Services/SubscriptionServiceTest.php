<?php

use Carbon\Carbon;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\SubscriptionRenewal;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\SubscriptionService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;

class SubscriptionServiceTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_renew_credit_card()
    {
        $em = $this->app->make(EcommerceEntityManager::class);

        $em->getMetadataFactory()
            ->getCacheDriver()
            ->deleteAll();

        $stripeCustomer = new Customer();
        $stripeCustomer->id = rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($stripeCustomer);
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $charge = new Charge();
        $charge->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($charge);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $creditCard = new CreditCard();

        $creditCard->setFingerprint($this->faker->word);
        $creditCard->setLastFourDigits($this->faker->randomNumber(4, true));
        $creditCard->setCompanyName($this->faker->word);
        $creditCard->setExpirationDate(Carbon::now());
        $creditCard->setPaymentGatewayName($this->getPaymentGateway('stripe'));
        $creditCard->setExternalId($this->faker->word);
        $creditCard->setCreatedAt(Carbon::now());

        $em->persist($creditCard);
        $em->flush();

        $country = 'Canada';
        $region = 'alberta';

        $address = new Address();
        $address->setCountry($country);
        $address->setRegion($region);
        $address->setType(Address::BILLING_ADDRESS_TYPE);

        $em->persist($address);
        $em->flush();

        $paymentMethod = new PaymentMethod();

        $paymentMethod->setCreditCard($creditCard);
        $paymentMethod->setBillingAddress($address);
        $paymentMethod->setCurrency($this->getCurrency());
        $paymentMethod->setCreatedAt(Carbon::now());

        $em->persist($paymentMethod);
        $em->flush();

        $product = new Product();

        $product->setBrand($this->faker->word);
        $product->setName($this->faker->word);
        $product->setSku($this->faker->word);
        $product->setPrice($this->faker->randomNumber(4));
        $product->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $product->setActive(true);
        $product->setIsPhysical(false);
        $product->setPrice(123.58);
        $product->setAutoDecrementStock(false);

        $em->persist($product);
        $em->flush();

        $taxService = $this->app->make(TaxService::class);
        $currencyService = $this->app->make(CurrencyService::class);

        $expectedTaxRateProduct = config('ecommerce.product_tax_rate')[strtolower($country)][strtolower($region)];
        $expectedTaxRateShipping = config('ecommerce.shipping_tax_rate')[strtolower($country)][strtolower($region)];

        $subscriptionTaxes = $taxService->getTaxesDueForProductCost(
            $product->getPrice(),
            $paymentMethod->getBillingAddress()->toStructure()
        );

        $subscription = new Subscription();

        $subscription->setBrand($this->faker->word);
        $subscription->setType('subscription');
        $subscription->setIsActive(true);
        $subscription->setStopped(false);
        $subscription->setProduct($product);
        $subscription->setUser($user);
        $subscription->setStartDate(Carbon::now());
        $subscription->setPaidUntil(Carbon::now()->subDay(1));
        $subscription->setTotalPrice(round($product->getPrice() + $subscriptionTaxes, 2));
        $subscription->setTax(round($subscriptionTaxes, 2));
        $subscription->setCurrency($paymentMethod->getCurrency());
        $subscription->setIntervalType(config('ecommerce.interval_type_monthly'));
        $subscription->setIntervalCount(1);
        $subscription->setTotalCyclesPaid($this->faker->randomNumber(3));
        $subscription->setRenewalAttempt(1);
        $subscription->setPaymentMethod($paymentMethod);
        $subscription->setCreatedAt(Carbon::now());
        $subscription->setUpdatedAt(Carbon::now());

        $em->persist($subscription);
        $em->flush();

        $srv = $this->app->make(SubscriptionService::class);

        $srv->renew($subscription);

        $chargePrice = $currencyService->convertFromBase(
            $subscription->getTotalPrice(),
            $subscription->getCurrency()
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $chargePrice,
                'total_paid' => $chargePrice,
                'total_refunded' => 0,
                'attempt_number' => 1,
                'type' => config('ecommerce.renewal_payment_type'),
                'external_id' => $charge->id,
                'external_provider' => 'stripe',
                'status' => 'succeeded',
                'payment_method_id' => $paymentMethod->getId(),
                'currency' => $subscription->getCurrency(),
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => $subscription->getId(),
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // assert user products assignation
        $this->assertDatabaseHas(
            'ecommerce_user_products',
            [
                'user_id' => $user->getId(),
                'product_id' => $product->getId(),
                'quantity' => 1,
                'expiration_date' => Carbon::now()
                    ->addMonth($subscription->getIntervalCount())
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry', 5))
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );

        $this->assertDatabaseHas(
            'ecommerce_payment_taxes',
            [
                'country' => $country,
                'region' => $region,
                'product_rate' => $expectedTaxRateProduct,
                'shipping_rate' => $expectedTaxRateShipping,
                'product_taxes_paid' => round($subscriptionTaxes, 2),
                'shipping_taxes_paid' => 0,
            ]
        );
    }

    public function test_renew_deleted_payment_method()
    {
        $em = $this->app->make(EcommerceEntityManager::class);

        $em->getMetadataFactory()
            ->getCacheDriver()
            ->deleteAll();

        $stripeCustomer = new Customer();
        $stripeCustomer->id = rand();

        $this->stripeExternalHelperMock->method('createCustomer')
            ->willReturn($stripeCustomer);
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $charge = new Charge();
        $charge->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($charge);

        $userProvider = $this->app->make(UserProviderInterface::class);

        $email = $this->faker->email;
        $password = $this->faker->shuffleString(
            $this->faker->bothify('???###???###???###???###')
        );

        $user = $userProvider->createUser($email, $password);

        $creditCard = new CreditCard();

        $creditCard->setFingerprint($this->faker->word);
        $creditCard->setLastFourDigits($this->faker->randomNumber(4, true));
        $creditCard->setCompanyName($this->faker->word);
        $creditCard->setExpirationDate(Carbon::now());
        $creditCard->setPaymentGatewayName($this->getPaymentGateway('stripe'));
        $creditCard->setExternalId($this->faker->word);
        $creditCard->setCreatedAt(Carbon::now());

        $em->persist($creditCard);
        $em->flush();

        $country = 'Canada';
        $region = 'alberta';

        $address = new Address();
        $address->setCountry($country);
        $address->setRegion($region);
        $address->setType(Address::BILLING_ADDRESS_TYPE);

        $em->persist($address);
        $em->flush();

        $paymentMethod = new PaymentMethod();

        $paymentMethod->setCreditCard($creditCard);
        $paymentMethod->setBillingAddress($address);
        $paymentMethod->setCurrency($this->getCurrency());
        $paymentMethod->setCreatedAt(Carbon::now());
        $paymentMethod->setDeletedAt(Carbon::now());

        $em->persist($paymentMethod);
        $em->flush();

        $product = new Product();

        $product->setBrand($this->faker->word);
        $product->setName($this->faker->word);
        $product->setSku($this->faker->word);
        $product->setPrice($this->faker->randomNumber(4));
        $product->setType(Product::TYPE_DIGITAL_SUBSCRIPTION);
        $product->setActive(true);
        $product->setIsPhysical(false);
        $product->setPrice(123.58);
        $product->setAutoDecrementStock(false);

        $em->persist($product);
        $em->flush();

        $taxService = $this->app->make(TaxService::class);
        $currencyService = $this->app->make(CurrencyService::class);

        $expectedTaxRateProduct = config('ecommerce.product_tax_rate')[strtolower($country)][strtolower($region)];
        $expectedTaxRateShipping = config('ecommerce.shipping_tax_rate')[strtolower($country)][strtolower($region)];

        $subscriptionTaxes = $taxService->getTaxesDueForProductCost(
            $product->getPrice(),
            $paymentMethod->getBillingAddress()->toStructure()
        );

        $subscription = new Subscription();

        $subscription->setBrand($this->faker->word);
        $subscription->setType('subscription');
        $subscription->setIsActive(true);
        $subscription->setStopped(false);
        $subscription->setProduct($product);
        $subscription->setUser($user);
        $subscription->setStartDate(Carbon::now());
        $subscription->setPaidUntil(Carbon::now()->subDay(1));
        $subscription->setTotalPrice(round($product->getPrice() + $subscriptionTaxes, 2));
        $subscription->setTax(round($subscriptionTaxes, 2));
        $subscription->setCurrency($paymentMethod->getCurrency());
        $subscription->setIntervalType(config('ecommerce.interval_type_monthly'));
        $subscription->setIntervalCount(1);
        $subscription->setTotalCyclesPaid($this->faker->randomNumber(3));
        $subscription->setRenewalAttempt(1);
//        $subscription->setPaymentMethod($paymentMethod);
        $subscription->setCreatedAt(Carbon::now());
        $subscription->setUpdatedAt(Carbon::now());

        $em->persist($subscription);
        $em->flush();

        $srv = $this->app->make(SubscriptionService::class);

        try {
            $srv->renew($subscription);
        } catch (Throwable $throwable) {
            $this->assertEquals(
                "Subscription with ID: " . $subscription->getId() . " does not have an attached payment method.",
                $throwable->getMessage()
            );
        }

        $chargePrice = $currencyService->convertFromBase(
            $subscription->getTotalPrice(),
            $subscription->getCurrency()
        );

        $this->assertDatabaseMissing(
            'ecommerce_payments',
            [
                'id' => 1,
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_subscription_payments',
            [
                'subscription_id' => $subscription->getId(),
            ]
        );

        // assert user products assignation
        $this->assertDatabaseMissing(
            'ecommerce_user_products',
            [
                'user_id' => $user->getId(),
                'product_id' => $product->getId(),
            ]
        );

        $this->assertDatabaseMissing(
            'ecommerce_payment_taxes',
            [
                'id' => 1,
            ]
        );

        // assert subscription was cancelled
        $this->assertDatabaseHas('ecommerce_subscriptions', [
            'canceled_on' => Carbon::now()->toDateTimeString(),
            'is_active' => false,
        ]);
    }

    public function test_get_subscriptions_renewal()
    {
        $renewalConfig = config('ecommerce.subscriptions_renew_cycles');

        // userOne, brandOne, subscriptionOne canceled, subscriptionTwo active
        // expected subscriptionTwo
        $userOne = $this->fakeUser();
        $brandOne = $this->faker->word;

        $productOne = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'brand' => $brandOne,
        ]);

        $subscriptionOne = $this->fakeSubscription([
            'product_id' => $productOne['id'],
            'user_id' => $userOne['id'],
            'brand' => $brandOne,
            'is_active' => 0,
            'stopped' => 0,
            'start_date' => Carbon::now()
                ->subMonths(5),
            'canceled_on' => Carbon::now()
                ->subMonths(3),
        ]);

        $productTwo = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'brand' => $brandOne,
        ]);

        $subscriptionTwo = $this->fakeSubscription([
            'product_id' => $productTwo['id'],
            'user_id' => $userOne['id'],
            'brand' => $brandOne,
            'is_active' => 1,
            'stopped' => 0,
            'start_date' => Carbon::now()
                ->subMonths(2),
            'paid_until' => Carbon::now()
                ->addDays(3)
                ->startOfDay(),
            'canceled_on' => null,
        ]);

        $idString = $brandOne . $userOne['id'];
        $expectedSubscriptionsRenewal = new SubscriptionRenewal(md5($idString));

        $expectedSubscriptionsRenewal->setUserId($userOne['id']);
        $expectedSubscriptionsRenewal->setBrand($brandOne);
        $expectedSubscriptionsRenewal->setSubscriptionId($subscriptionTwo['id']);
        $expectedSubscriptionsRenewal->setSubscriptionType(
            $subscriptionTwo['interval_type'] . '_' . $subscriptionTwo['interval_count']
        );
        $expectedSubscriptionsRenewal->setSubscriptionState(Subscription::STATE_ACTIVE);
        $expectedSubscriptionsRenewal->setNextRenewalDue($subscriptionTwo['paid_until']);

        $expectedSubscriptionsRenewals[] = $expectedSubscriptionsRenewal;

        // userTwo
        //     subscriptionThree of brandTwo suspended - normal renew
        //     subscriptionFour of brandThree stopped
        //     subscriptionFive of brandThree active
        // expected subscriptionThree, subscriptionFive
        $userTwo = $this->fakeUser();
        $brandTwo = $this->faker->word;

        $productThree = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'brand' => $brandTwo,
        ]);

        $subscriptionThree = $this->fakeSubscription([
            'product_id' => $productThree['id'],
            'user_id' => $userTwo['id'],
            'brand' => $brandTwo,
            'is_active' => 0,
            'stopped' => 0,
            'start_date' => Carbon::now()
                ->subMonths(5),
            'paid_until' => Carbon::now()
                ->subDays(13)
                ->startOfDay(),
            'canceled_on' => null,
        ]);

        $idString = $brandTwo . $userTwo['id'];
        $expectedSubscriptionsRenewal = new SubscriptionRenewal(md5($idString));

        $expectedSubscriptionsRenewal->setUserId($userTwo['id']);
        $expectedSubscriptionsRenewal->setBrand($brandTwo);
        $expectedSubscriptionsRenewal->setSubscriptionId($subscriptionThree['id']);
        $expectedSubscriptionsRenewal->setSubscriptionType(
            $subscriptionThree['interval_type'] . '_' . $subscriptionThree['interval_count']
        );
        $expectedSubscriptionsRenewal->setSubscriptionState(Subscription::STATE_SUSPENDED);
        $expectedSubscriptionsRenewal->setNextRenewalDue($subscriptionThree['paid_until']);

        $expectedSubscriptionsRenewals[] = $expectedSubscriptionsRenewal;

        $brandThree = $this->faker->word;

        $productFour = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'brand' => $brandThree,
        ]);

        $subscriptionFour = $this->fakeSubscription([
            'product_id' => $productFour['id'],
            'user_id' => $userTwo['id'],
            'brand' => $brandThree,
            'is_active' => 1,
            'stopped' => 1,
            'start_date' => Carbon::now()
                ->subMonths(3),
            'paid_until' => Carbon::now()
                ->subMonths(2)
                ->subDays(2),
            'canceled_on' => null,
        ]);

        $productFive = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'brand' => $brandThree,
        ]);

        $subscriptionFive = $this->fakeSubscription([
            'product_id' => $productFive['id'],
            'user_id' => $userTwo['id'],
            'brand' => $brandThree,
            'is_active' => 1,
            'stopped' => 0,
            'start_date' => Carbon::now()
                ->subMonths(2),
            'paid_until' => Carbon::now()
                ->addDays(14)
                ->startOfDay(),
            'canceled_on' => null,
        ]);

        $idString = $brandThree . $userTwo['id'];
        $expectedSubscriptionsRenewal = new SubscriptionRenewal(md5($idString));

        $expectedSubscriptionsRenewal->setUserId($userTwo['id']);
        $expectedSubscriptionsRenewal->setBrand($brandThree);
        $expectedSubscriptionsRenewal->setSubscriptionId($subscriptionFive['id']);
        $expectedSubscriptionsRenewal->setSubscriptionType(
            $subscriptionFive['interval_type'] . '_' . $subscriptionFive['interval_count']
        );
        $expectedSubscriptionsRenewal->setSubscriptionState(Subscription::STATE_ACTIVE);
        $expectedSubscriptionsRenewal->setNextRenewalDue($subscriptionFive['paid_until']);

        $expectedSubscriptionsRenewals[] = $expectedSubscriptionsRenewal;

        // userThree, brandFour, subscriptionSix of brandFour suspended, renew cycle 4
        // expected subscriptionSix
        $userThree = $this->fakeUser();
        $brandFour = $this->faker->word;

        $productSix = $this->fakeProduct([
            'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
            'brand' => $brandFour,
        ]);

        $subscriptionSix = $this->fakeSubscription([
            'product_id' => $productSix['id'],
            'user_id' => $userThree['id'],
            'brand' => $brandFour,
            'is_active' => 0,
            'stopped' => 0,
            'renewal_attempt' => 4,
            'start_date' => Carbon::now()
                ->subMonths(5),
            'paid_until' => Carbon::now()
                ->subDays(13)
                ->startOfDay(),
            'canceled_on' => null,
        ]);

        $idString = $brandFour . $userThree['id'];
        $expectedSubscriptionsRenewal = new SubscriptionRenewal(md5($idString));

        $expectedSubscriptionsRenewal->setUserId($userThree['id']);
        $expectedSubscriptionsRenewal->setBrand($brandFour);
        $expectedSubscriptionsRenewal->setSubscriptionId($subscriptionSix['id']);
        $expectedSubscriptionsRenewal->setSubscriptionType(
            $subscriptionSix['interval_type'] . '_' . $subscriptionSix['interval_count']
        );
        $expectedSubscriptionsRenewal->setSubscriptionState(Subscription::STATE_SUSPENDED);
        $expectedSubscriptionsRenewal->setNextRenewalDue(
            $subscriptionSix['paid_until']->copy()
                ->addHours($renewalConfig[4])
        );

        $expectedSubscriptionsRenewals[] = $expectedSubscriptionsRenewal;

        $srv = $this->app->make(SubscriptionService::class);

        $subscriptionsRenewals = $srv->getSubscriptionsRenewalForUsers(
            [
                $userOne['id'],
                $userTwo['id'],
                $userThree['id'],
            ]
        );

        $this->assertEquals($expectedSubscriptionsRenewals, $subscriptionsRenewals);
    }
}
