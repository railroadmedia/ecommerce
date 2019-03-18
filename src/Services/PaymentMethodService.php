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

    CONST PAYPAL_PAYMENT_METHOD_TYPE      = 'paypal';
    CONST CREDIT_CARD_PAYMENT_METHOD_TYPE = 'credit-card';

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
            ->setMethodType(self::CREDIT_CARD_PAYMENT_METHOD_TYPE)
            ->setCurrency($currency ?? ConfigService::$defaultCurrency)
            ->setBillingAddress($billingAddress);

        $this->entityManager->persist($paymentMethod);

        if ($user) {

            $primary = $this->userPaymentMethodsRepository
                    ->getUserPrimaryPaymentMethod($user);

            if ($makePrimary && $primary) {
                /**
                 * @var $primary \Railroad\Ecommerce\Entities\UserPaymentMethods
                 */
                $primary->setIsPrimary(false);
            }

            $userPaymentMethods = new UserPaymentMethods();

            $userPaymentMethods
                ->setUser($user)
                ->setPaymentMethod($paymentMethod)
                ->setIsPrimary(($primary == null) || $makePrimary); // if user has no other payment method, this should be primary

            $this->entityManager->persist($userPaymentMethods);

        } elseif ($customer) {

            $primary = $this->customerPaymentMethodsRepository
                    ->getCustomerPrimaryPaymentMethod($customer);

            if ($makePrimary && $primary) {
                /**
                 * @var $primary \Railroad\Ecommerce\Entities\CustomerPaymentMethods
                 */
                $primary->setIsPrimary(false);
            }

            $customerPaymentMethods = new CustomerPaymentMethods();

            $customerPaymentMethods
                ->setCustomer($customer)
                ->setPaymentMethod($paymentMethod)
                ->setIsPrimary(($primary == null) || $makePrimary); // if user has no other payment method, this should be primary

            $this->entityManager->persist($customerPaymentMethods);
        }

        $this->entityManager->flush();

        if ($user && $makePrimary) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $user->getId(),
                    $paymentMethod->getId()
                )
            );
        } // no events for customer

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
     * @param string $currency - default null
     * @param bool $makePrimary - default false
     *
     * @return PaymentMethod
     *
     * @throws Throwable
     */
    public function createPayPalBillingAgreement(
        User $user,
        $billingAgreementExternalId,
        Address $billingAddress,
        $paymentGatewayName,
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
            ->setMethodType(self::PAYPAL_PAYMENT_METHOD_TYPE)
            ->setCurrency($currency ?? ConfigService::$defaultCurrency)
            ->setBillingAddress($billingAddress);

        $this->entityManager->persist($paymentMethod);

        $primary = $this->userPaymentMethodsRepository
                ->getUserPrimaryPaymentMethod($user);

        if ($makePrimary && $primary) {
            /**
             * @var $primary \Railroad\Ecommerce\Entities\UserPaymentMethods
             */
            $primary->setIsPrimary(false);
        }

        $userPaymentMethods = new UserPaymentMethods();

        $userPaymentMethods
            ->setUser($user)
            ->setPaymentMethod($paymentMethod)
            ->setIsPrimary(($primary == null) || $makePrimary); // if user has no other payment method, this should be primary

        $this->entityManager->persist($userPaymentMethods);

        $this->entityManager->flush();

        if ($makePrimary) {
            event(
                new UserDefaultPaymentMethodEvent(
                    $user->getId(),
                    $paymentMethod->getId()
                )
            );
        } // no events for customer

        return $paymentMethod;
    }
}