<?php

use Carbon\Carbon;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;

class RenewalServiceTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_service_credit_card()
    {
        $em = $this->app->make(EcommerceEntityManager::class);

        $em->getMetadataFactory()
            ->getCacheDriver()
            ->deleteAll();

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
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

        $paymentMethod = new PaymentMethod();

        $paymentMethod->setCreditCard($creditCard);
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

        $em->persist($product);
        $em->flush();

        $subscription = new Subscription();

        $subscription->setBrand($this->faker->word);
        $subscription->setType('subscription');
        $subscription->setIsActive(true);
        $subscription->setProduct($product);
        $subscription->setUser($user);
        $subscription->setStartDate(Carbon::now());
        $subscription->setPaidUntil(Carbon::now()->subDay(1));
        $subscription->setTotalPrice($this->faker->randomNumber(3));
        $subscription->setTax($this->faker->randomNumber(3));
        $subscription->setCurrency($this->getCurrency());
        $subscription->setIntervalType(config('ecommerce.interval_type_monthly'));
        $subscription->setIntervalCount(1);
        $subscription->setTotalCyclesPaid($this->faker->randomNumber(3));
        $subscription->setPaymentMethod($paymentMethod);
        $subscription->setCreatedAt(Carbon::now());
        $subscription->setUpdatedAt(Carbon::now());

        $em->persist($subscription);
        $em->flush();

        $srv = $this->app->make(RenewalService::class);

        $srv->renew($subscription);

        $taxService = $this->app->make(TaxService::class);
        $currencyService = $this->app->make(CurrencyService::class);

        $vat = $taxService->getTaxesDueForProductCost(
            $subscription->getTotalPrice(),
            $paymentMethod->getBillingAddress()
        );

        $chargePrice = $currencyService->convertFromBase(
            $vat + $subscription->getTotalPrice(),
            $subscription->getCurrency()
        );

        $this->assertDatabaseHas(
            'ecommerce_payments',
            [
                'total_due' => $chargePrice,
                'total_paid' => $chargePrice,
                'total_refunded' => 0,
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
                    ->startOfDay()
                    ->toDateTimeString(),
            ]
        );
    }
}
