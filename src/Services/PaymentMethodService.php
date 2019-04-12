<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\CustomerPaymentMethodsRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Entities\CustomerPaymentMethods;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Stripe\Card;
use Stripe\Customer as StripeCustomer;
use Throwable;

class PaymentMethodService
{
    /**
     * @var CustomerPaymentMethodsRepository
     */
    private $customerPaymentMethodsRepository;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodsRepository;

    /**
     * PaymentMethodService constructor.
     *
     * @param CustomerPaymentMethodsRepository $customerPaymentMethodsRepository
     * @param EcommerceEntityManager $entityManager
     * @param UserPaymentMethodsRepository $userPaymentMethodsRepository
     */
    public function __construct(
        CustomerPaymentMethodsRepository $customerPaymentMethodsRepository,
        EcommerceEntityManager $entityManager,
        UserPaymentMethodsRepository $userPaymentMethodsRepository
    ) {

        $this->customerPaymentMethodsRepository = $customerPaymentMethodsRepository;
        $this->entityManager = $entityManager;
        $this->userPaymentMethodsRepository = $userPaymentMethodsRepository;
    }

    /**
     * Creates $purchaser credit card and payment method entities
     * Sets the $makePrimary flag for $user or $customer payment method
     *
     * @param User $user
     * @param Purchaser $purchaser
     * @param Address $billingAddress
     * @param Card $card
     * @param StripeCustomer $stripeCustomer
     * @param string $gateway
     * @param string $currency
     * @param bool $setUserDefaultPaymentMethod - default false
     *
     * @return PaymentMethod
     *
     * @throws Throwable
     */
    public function createCreditCardPaymentMethod(
        Purchaser $purchaser,
        Address $billingAddress,
        Card $card,
        StripeCustomer $stripeCustomer,
        string $gateway,
        string $currency,
        ?bool $setUserDefaultPaymentMethod = true
    ): PaymentMethod
    {
        $creditCard = new CreditCard();

        $creditCard
            ->setFingerprint($card->fingerprint)
            ->setLastFourDigits($card->last4)
            ->setCardholderName($card->name)
            ->setCompanyName($card->brand)
            ->setExpirationDate(
                Carbon::createFromDate($card->exp_year, $card->exp_month)
            )
            ->setExternalId($card->id)
            ->setExternalCustomerId($stripeCustomer->id)
            ->setPaymentGatewayName($gatewayName);

        $this->entityManager->persist($creditCard);
        $this->entityManager->flush(); // needed to link composite payment method

        $paymentMethod = $this->createPaymentMethod(
            $billingAddress,
            $creditCard->getId(),
            PaymentMethod::TYPE_CREDIT_CARD,
            $currency ?? ConfigService::$defaultCurrency
        );

        $this->entityManager->persist($paymentMethod);

        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {

            $userPaymentMethods = $this->createUserPaymentMethod(
                $purchaser->getUserObject(),
                $paymentMethod,
                $setUserDefaultPaymentMethod
            );

            $this->entityManager->persist($userPaymentMethods);
        }
        elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && !empty($purchaser->getEmail())) {

            $customerPaymentMethods = $this->createCustomerPaymentMethod(
                $purchaser->getUserObject(),
                $paymentMethod
            );

            $this->entityManager->persist($customerPaymentMethods);
        }

        $this->entityManager->flush();

        // no events for customer
        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId()) && $makePrimary) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $user->getId(),
                    $paymentMethod->getId()
                )
            );
        }

        return $paymentMethod;
    }

    public function createPayPalPaymentMethod(
        Purchaser $purchaser,
        Address $billingAddress,
        string $billingAgreementId,
        string $gateway,
        string $currency,
        ?bool $setUserDefaultPaymentMethod = true
    ): PaymentMethod
    {
        $billingAgreement = new PaypalBillingAgreement();

        $billingAgreement
            ->setExternalId($billingAgreementExternalId)
            ->setPaymentGatewayName($gateway);

        $this->entityManager->persist($billingAgreement);
        $this->entityManager->flush(); // needed to link composite payment method

        $paymentMethod = $this->createPaymentMethod(
            $billingAddress,
            $billingAgreement->getId(),
            PaymentMethod::TYPE_PAYPAL,
            $currency ?? ConfigService::$defaultCurrency
        );

        $this->entityManager->persist($paymentMethod);

        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {

            $userPaymentMethods = $this->createUserPaymentMethod(
                $purchaser->getUserObject(),
                $paymentMethod,
                $setUserDefaultPaymentMethod
            );

            $this->entityManager->persist($userPaymentMethods);
        }
        elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && !empty($purchaser->getEmail())) {

            $customerPaymentMethods = $this->createCustomerPaymentMethod(
                $purchaser->getUserObject(),
                $paymentMethod
            );

            $this->entityManager->persist($customerPaymentMethods);
        }

        $this->entityManager->flush();

        // no events for customer
        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId()) && $makePrimary) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $user->getId(),
                    $paymentMethod->getId()
                )
            );
        }

        return $paymentMethod;
    }

    /**
     * Creates payment method entity
     *
     * @param Address $billingAddress
     * @param int $methodId
     * @param string $methodType
     * @param string $currency
     *
     * @return PaymentMethod
     */
    public function createPaymentMethod(
        Address $billingAddress,
        int $methodId,
        string $methodType,
        string $currency
    ): PaymentMethod
    {
        $paymentMethod = new PaymentMethod();

        $paymentMethod
            ->setMethodId($methodId)
            ->setMethodType($methodType)
            ->setCurrency($currency)
            ->setBillingAddress($billingAddress);

        return $paymentMethod;
    }

    /**
     * Creates user payment method entity
     *
     * @param User $user
     * @param PaymentMethod $paymentMethod
     * @param bool $setUserDefaultPaymentMethod
     *
     * @return UserPaymentMethods
     */
    public function createUserPaymentMethod(
        User $user,
        PaymentMethod $paymentMethod,
        ?bool $setUserDefaultPaymentMethod = true
    ): UserPaymentMethods
    {
        $existingPrimaryMethod = $this->userPaymentMethodsRepository
                    ->getUserPrimaryPaymentMethod($user);

        if ($setUserDefaultPaymentMethod && $existingPrimaryMethod) {
            $existingPrimaryMethod->setIsPrimary(false);
        }

        $userPaymentMethods = new UserPaymentMethods();

        $userPaymentMethods
            ->setUser($user)
            ->setPaymentMethod($paymentMethod)
            ->setIsPrimary(
                ($existingPrimaryMethod == null) || $setUserDefaultPaymentMethod
            ); // if user has no other payment method, this should be primary

        return $userPaymentMethods;
    }

    /**
     * Creates customer payment method entity
     *
     * @param Customer $customer
     * @param PaymentMethod $paymentMethod
     *
     * @return CustomerPaymentMethods
     */
    public function createCustomerPaymentMethod(
        Customer $customer,
        PaymentMethod $paymentMethod
    ): CustomerPaymentMethods
    {
        $customerPaymentMethods = new CustomerPaymentMethods();

        $customerPaymentMethods
            ->setCustomer($customer)
            ->setPaymentMethod($paymentMethod)
            ->setIsPrimary(true);

        return $customerPaymentMethods;
    }
}