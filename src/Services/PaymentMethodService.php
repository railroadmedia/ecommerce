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
use Railroad\Ecommerce\Entities\UserPaymentMethods;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
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
     * Creates $user or $customer credit card and payment method
     * Sets the $makePrimary flag for $user or $customer payment method
     *
     * @param User $user
     * @param string $fingerPrint
     * @param int $last4
     * @param string $cardHolderName
     * @param string $companyName
     * @param $expirationYear
     * @param $expirationMonth
     * @param string $externalId
     * @param string $externalCustomerId
     * @param string $gatewayName
     * @param Customer $customer
     * @param Address $billingAddress - default null
     * @param string $currency - default null
     * @param bool $makePrimary - default false
     *
     * @return PaymentMethod
     *
     * @throws Throwable
     */
    public function createUserCreditCard(
        ?User $user,
        $fingerPrint,
        $last4,
        $cardHolderName,
        $companyName,
        $expirationYear,
        $expirationMonth,
        $externalId,
        $externalCustomerId,
        $gatewayName,
        ?Customer $customer,
        ?Address $billingAddress = null,
        $currency = null,
        $makePrimary = false
    ): PaymentMethod {

        $creditCard = new CreditCard();

        $creditCard
            ->setFingerprint($fingerPrint)
            ->setLastFourDigits($last4)
            ->setCardholderName($cardHolderName)
            ->setCompanyName($companyName)
            ->setExpirationDate(
                Carbon::createFromDate($expirationYear, $expirationMonth)
            )
            ->setExternalId($externalId)
            ->setExternalCustomerId($externalCustomerId)
            ->setPaymentGatewayName($gatewayName);

        $this->entityManager->persist($creditCard);
        $this->entityManager->flush(); // needed to link composite payment method

        $paymentMethod = new PaymentMethod();

        $paymentMethod
            ->setMethodId($creditCard->getId())
            ->setMethodType(PaymentMethod::TYPE_CREDIT_CARD)
            ->setCurrency($currency ?? ConfigService::$defaultCurrency)
            ->setBillingAddress($billingAddress);

        $this->entityManager->persist($paymentMethod);

        if ($user) {
            $primary = $this->userPaymentMethodsRepository
                    ->getUserPrimaryPaymentMethod($user);

            if ($makePrimary && $primary) {
                $primary->setIsPrimary(false);
            }

            $userPaymentMethods = new UserPaymentMethods();

            $userPaymentMethods
                ->setUser($user)
                ->setPaymentMethod($paymentMethod)
                ->setIsPrimary(($primary == null) || $makePrimary); // if user has no other payment method, this should be primary

            $this->entityManager->persist($userPaymentMethods);

        } elseif ($customer) {
            $customerPaymentMethods = new CustomerPaymentMethods();

            $customerPaymentMethods
                ->setCustomer($customer)
                ->setPaymentMethod($paymentMethod)
                ->setIsPrimary(true);

            $this->entityManager->persist($customerPaymentMethods);
        }

        $this->entityManager->flush();

        // no events for customer
        if ($user && $makePrimary) {
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
     * Creates $user paypal billing agreement and payment method
     * Sets the $makePrimary flag for $user payment method
     *
     * @param User $user
     * @param string $billingAgreementExternalId
     * @param Address $billingAddress
     * @param string $paymentGatewayName
     * @param Customer|null $customer
     * @param string $currency - default null
     * @param bool $makePrimary - default false
     *
     * @return PaymentMethod
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function createPayPalBillingAgreement(
        User $user,
        $billingAgreementExternalId,
        Address $billingAddress,
        $paymentGatewayName,
        ?Customer $customer,
        $currency = null,
        $makePrimary = false
    ): PaymentMethod {

        $billingAgreement = new PaypalBillingAgreement();

        $billingAgreement
            ->setExternalId($billingAgreementExternalId)
            ->setPaymentGatewayName($paymentGatewayName);

        $this->entityManager->persist($billingAgreement);
        $this->entityManager->flush(); // needed to link composite payment method

        $paymentMethod = new PaymentMethod();

        $paymentMethod
            ->setMethodId($billingAgreement->getId())
            ->setMethodType(PaymentMethod::TYPE_PAYPAL)
            ->setCurrency($currency ?? ConfigService::$defaultCurrency)
            ->setBillingAddress($billingAddress);

        $this->entityManager->persist($paymentMethod);

        if ($user) {
            $primary = $this->userPaymentMethodsRepository
                ->getUserPrimaryPaymentMethod($user);

            if ($makePrimary && $primary) {
                $primary->setIsPrimary(false);
            }

            $userPaymentMethods = new UserPaymentMethods();

            $userPaymentMethods
                ->setUser($user)
                ->setPaymentMethod($paymentMethod)
                ->setIsPrimary(($primary == null) || $makePrimary); // if user has no other payment method, this should be primary

            $this->entityManager->persist($userPaymentMethods);

        } elseif ($customer) {
            $customerPaymentMethods = new CustomerPaymentMethods();

            $customerPaymentMethods
                ->setCustomer($customer)
                ->setPaymentMethod($paymentMethod)
                ->setIsPrimary(true);

            $this->entityManager->persist($customerPaymentMethods);
        }

        $this->entityManager->flush();

        // no events for customer
        if ($user && $makePrimary) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $user->getId(),
                    $paymentMethod->getId()
                )
            );
        }

        return $paymentMethod;
    }
}