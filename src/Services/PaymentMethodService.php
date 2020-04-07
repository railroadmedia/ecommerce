<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Entities\CustomerPaymentMethods;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Events\PaymentMethods\PaymentMethodCreated;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Stripe\Card;
use Stripe\Customer as StripeCustomer;
use Throwable;

class PaymentMethodService
{
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
     * @param EcommerceEntityManager $entityManager
     * @param UserPaymentMethodsRepository $userPaymentMethodsRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        UserPaymentMethodsRepository $userPaymentMethodsRepository
    )
    {
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

        $creditCard->setFingerprint($card->fingerprint);
        $creditCard->setLastFourDigits($card->last4);
        $creditCard->setCardholderName($card->name);
        $creditCard->setCompanyName($card->brand);
        $creditCard->setExpirationDate(
            Carbon::createFromDate($card->exp_year, $card->exp_month)
        );
        $creditCard->setExternalId($card->id);
        $creditCard->setExternalCustomerId($stripeCustomer->id);
        $creditCard->setPaymentGatewayName($gateway);

        $this->entityManager->persist($creditCard);
        $this->entityManager->flush(); // needed to link composite payment method

        $paymentMethod = $this->createPaymentMethod(
            $billingAddress,
            $creditCard,
            $currency ?? config('ecommerce.default_currency')
        );

        $this->entityManager->persist($paymentMethod);

        $identifiable = null;

        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {

            $identifiable = $purchaser->getUserObject();

            $userPaymentMethods = $this->createUserPaymentMethod(
                $identifiable,
                $paymentMethod,
                $setUserDefaultPaymentMethod
            );

            $this->entityManager->persist($userPaymentMethods);
        }
        elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && !empty($purchaser->getEmail())) {

            $identifiable = $purchaser->getCustomerEntity();

            $customerPaymentMethods = $this->createCustomerPaymentMethod(
                $identifiable,
                $paymentMethod
            );

            $this->entityManager->persist($customerPaymentMethods);
        }

        $this->entityManager->flush();

        // no events for customer
        if ($purchaser->getType() == Purchaser::USER_TYPE &&
            !empty($purchaser->getId()) &&
            $setUserDefaultPaymentMethod) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $purchaser->getId(), $paymentMethod->getId()
                )
            );
        }

        event(new PaymentMethodCreated($paymentMethod, $identifiable));

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

        $billingAgreement->setExternalId($billingAgreementId);
        $billingAgreement->setPaymentGatewayName($gateway);

        $this->entityManager->persist($billingAgreement);
        $this->entityManager->flush(); // needed to link composite payment method

        $paymentMethod = $this->createPaymentMethod(
            $billingAddress,
            $billingAgreement,
            $currency ?? config('ecommerce.default_currency')
        );

        $this->entityManager->persist($paymentMethod);

        $identifiable = null;

        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {

            $identifiable = $purchaser->getUserObject();

            $userPaymentMethods = $this->createUserPaymentMethod(
                $identifiable,
                $paymentMethod,
                $setUserDefaultPaymentMethod
            );

            $this->entityManager->persist($userPaymentMethods);
        }
        elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && !empty($purchaser->getEmail())) {

            $identifiable = $purchaser->getCustomerEntity();

            $customerPaymentMethods = $this->createCustomerPaymentMethod(
                $identifiable,
                $paymentMethod
            );

            $this->entityManager->persist($customerPaymentMethods);
        }

        $this->entityManager->flush();

        // no events for customer
        if ($purchaser->getType() == Purchaser::USER_TYPE &&
            !empty($purchaser->getId()) &&
            $setUserDefaultPaymentMethod) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $purchaser->getUserObject()
                        ->getId(), $paymentMethod->getId()
                )
            );
        }

        event(new PaymentMethodCreated($paymentMethod, $identifiable));

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

        $paymentMethod->setCurrency($currency);
        $paymentMethod->setBillingAddress($billingAddress);

        if ($method instanceof CreditCard) {
            $paymentMethod->setCreditCard($method);
        }
        elseif ($method instanceof PaypalBillingAgreement) {
            $paymentMethod->setPaypalBillingAgreement($method);
        }
        else {
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
        $existingPrimaryMethod = $this->userPaymentMethodsRepository->getUserPrimaryPaymentMethod($user);

        if ($setUserDefaultPaymentMethod && $existingPrimaryMethod) {
            $existingPrimaryMethod->setIsPrimary(false);
        }

        $userPaymentMethods = new UserPaymentMethods();

        $userPaymentMethods->setUser($user);
        $userPaymentMethods->setPaymentMethod($paymentMethod);
        $userPaymentMethods->setIsPrimary(
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

        $customerPaymentMethods->setCustomer($customer);
        $customerPaymentMethods->setPaymentMethod($paymentMethod);
        $customerPaymentMethods->setIsPrimary(true);

        return $customerPaymentMethods;
    }
}