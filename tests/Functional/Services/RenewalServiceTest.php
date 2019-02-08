<?php

use Doctrine\ORM\EntityManager;
use Railroad\Ecommerce\Tests\EcommerceTestCase;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\RenewalService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Usora\Entities\User;
use Carbon\Carbon;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Token;

class RenewalServiceTest extends EcommerceTestCase
{
    protected function setUp()
    {
        parent::setUp();
    }

    public function test_service_credit_card()
    {
        $em = $this->app->make(EntityManager::class);

        $this->stripeExternalHelperMock->method('retrieveCustomer')
            ->willReturn(new Customer());
        $this->stripeExternalHelperMock->method('retrieveCard')
            ->willReturn(new Card());

        $charge = new Charge();
        $charge->id = $this->faker->word;

        $this->stripeExternalHelperMock->method('chargeCard')
            ->willReturn($charge);

        $user = new User();

        $user->setEmail($this->faker->email);
        $user->setPassword(
            $this->faker->shuffleString(
                $this->faker->bothify('???###???###???###???###')
            )
        );
        $user->setDisplayName($this->faker->name);

        $em->persist($user);
        $em->flush();

        $creditCard = new CreditCard();

        $creditCard
            ->setFingerprint($this->faker->word)
            ->setLastFourDigits($this->faker->randomNumber(4, true))
            ->setCompanyName($this->faker->word)
            ->setExpirationDate(Carbon::now())
            ->setPaymentGatewayName($this->getPaymentGateway('stripe'))
            ->setExternalId($this->faker->word)
            ->setCreatedAt(Carbon::now());

        $em->persist($creditCard);
        $em->flush();

        $paymentMethod = new PaymentMethod();

        $paymentMethod
            ->setMethodId($creditCard->getId())
            ->setMethodType(PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE)
            ->setCurrency($this->getCurrency())
            ->setCreatedAt(Carbon::now());

        $em->persist($paymentMethod);
        $em->flush();

        $product = new Product();

        $product
            ->setBrand($this->faker->word)
            ->setName($this->faker->word)
            ->setSku($this->faker->word)
            ->setPrice($this->faker->randomNumber(4))
            ->setType(ConfigService::$typeSubscription)
            ->setActive(true)
            ->setIsPhysical(false);

        $em->persist($product);
        $em->flush();

        $subscription = new Subscription();

        $subscription
            ->setBrand($this->faker->word)
            ->setType('subscription')
            ->setIsActive(true)
            ->setProduct($product)
            ->setUser($user)
            ->setStartDate(Carbon::now())
            ->setPaidUntil(Carbon::now()->subDay(1))
            ->setTotalPrice($this->faker->randomNumber(3))
            ->setCurrency($this->getCurrency())
            ->setIntervalType(ConfigService::$intervalTypeMonthly)
            ->setIntervalCount(1)
            ->setTotalCyclesPaid($this->faker->randomNumber(3))
            ->setPaymentMethod($paymentMethod)
            ->setCreatedAt(Carbon::now())
            ->setUpdatedAt(Carbon::now());

        $em->persist($subscription);
        $em->flush();

        $srv = $this->app->make(RenewalService::class);

        $srv->renew($subscription);

        $taxService = $this->app->make(TaxService::class);
        $currencyService = $this->app->make(CurrencyService::class);

        $priceWithVat = $taxService->priceWithVat(
            $subscription->getTotalPrice(),
            $paymentMethod->getBillingAddress()
        );

        $chargePrice = $currencyService->convertFromBase(
            $priceWithVat,
            $subscription->getCurrency()
        );

        // assert user product was created
        $this->assertDatabaseHas(
            ConfigService::$tablePayment,
            [
                'total_due' => $subscription->getTotalPrice(),
                'total_paid' => $chargePrice,
                'type' => ConfigService::$renewalPaymentType,
                'external_id' => $charge->id,
                'external_provider' => 'stripe',
                'status' => 'succeeded',
                'payment_method_id' => $paymentMethod->getId(),
                'currency' => $subscription->getCurrency(),
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );

        // assert user product was created
        $this->assertDatabaseHas(
            ConfigService::$tableSubscriptionPayment,
            [
                'subscription_id' => $subscription->getId(),
                'created_at' => Carbon::now()->toDateTimeString(),
            ]
        );
    }
}
