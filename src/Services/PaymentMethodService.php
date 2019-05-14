<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Events\PaymentMethods\PaymentMethodCreated;
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
            ->setPaymentGatewayName($gateway);

        $this->entityManager->persist($creditCard);
        $this->entityManager->flush(); // needed to link composite payment method

        $paymentMethod = $this->createPaymentMethod(
            $billingAddress,
            $creditCard,
            $currency ?? config('ecommerce.default_currency')
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
                $purchaser->getCustomerEntity(),
                $paymentMethod
            );

            $this->entityManager->persist($customerPaymentMethods);
        }

        $this->entityManager->flush();

        // no events for customer
        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId()) && $setUserDefaultPaymentMethod) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $purchaser->getId(),
                    $paymentMethod->getId()
                )
            );
        }

        event(new PaymentMethodCreated($paymentMethod));

        return $paymentMethod;
    }

    /**
     * Creates $purchaser paypal billing agreements and payment method entities
     * Sets the $makePrimary flag for $user or $customer payment method
     *
     * @param Purchaser $purchaser
     * @param Address $billingAddress
     * @param string $billingAgreementId
     * @param string $gateway
     * @param string $currency
     * @param bool $setUserDefaultPaymentMethod - default false
     *
     * @return PaymentMethod
     *
     * @throws Throwable
     */
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
            ->setExternalId($billingAgreementId)
            ->setPaymentGatewayName($gateway);

        $this->entityManager->persist($billingAgreement);
        $this->entityManager->flush(); // needed to link composite payment method

        $paymentMethod = $this->createPaymentMethod(
            $billingAddress,
            $billingAgreement,
            $currency ?? config('ecommerce.default_currency')
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
                $purchaser->getCustomerEntity(),
                $paymentMethod
            );

            $this->entityManager->persist($customerPaymentMethods);
        }

        $this->entityManager->flush();

        // no events for customer
        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId()) && $setUserDefaultPaymentMethod) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $purchaser->getUserObject()->getId(),
                    $paymentMethod->getId()
                )
            );
        }

        event(new PaymentMethodCreated($paymentMethod));

        return $paymentMethod;
    }

    /**
     * Creates payment method entity
     *
     * @param Address $billingAddress
     * @param CreditCard|PaypalBillingAgreement $method
     * @param string $currency
     *
     * @return PaymentMethod
     * @throws Exception
     */
    public function createPaymentMethod(
        Address $billingAddress,
        $method,
        string $currency
    ): PaymentMethod
    {
        $paymentMethod = new PaymentMethod();

        $paymentMethod
            ->setCurrency($currency)
            ->setBillingAddress($billingAddress);

        if ($method instanceof CreditCard) {
            $paymentMethod->setCreditCard($method);
        } elseif ($method instanceof PaypalBillingAgreement) {
            $paymentMethod->setPaypalBillingAgreement($method);
        } else {
            throw new Exception('Invalid payment method type on create.');
        }

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
     *
     * @throws Throwable
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