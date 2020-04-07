<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Entities\UserStripeCustomerId;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\UserStripeCustomerIdRepository;
use Stripe\Customer;
use Throwable;

/**
 * todo: needs testing
 *
 * Class PaymentService
 * @package Railroad\Ecommerce\Services
 */
class PaymentService
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var UserStripeCustomerIdRepository
     */
    private $userStripeCustomerIdRepository;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var CurrencyService
     */
    private $currencyService;
    /**
     * @var PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * PaymentService constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param UserStripeCustomerIdRepository $userStripeCustomerIdRepository
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param CurrencyService $currencyService
     * @param PaymentMethodService $paymentMethodService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        PaymentMethodRepository $paymentMethodRepository,
        UserStripeCustomerIdRepository $userStripeCustomerIdRepository,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway,
        CurrencyService $currencyService,
        PaymentMethodService $paymentMethodService
    )
    {
        $this->entityManager = $entityManager;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->userStripeCustomerIdRepository = $userStripeCustomerIdRepository;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->currencyService = $currencyService;
        $this->paymentMethodService = $paymentMethodService;
    }

    /**
     * @param int $paymentMethodId
     * @param string $currency
     * @param float $paymentAmountInBaseCurrency
     * @param int $userId
     * @param string $paymentType
     *
     * @return Payment
     *
     * @throws ORMException
     * @throws PaymentFailedException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function chargeUsersExistingPaymentMethod(
        int $paymentMethodId,
        string $currency,
        float $paymentAmountInBaseCurrency,
        int $userId,
        string $paymentType
    )
    {
        // do currency conversion
        $conversionRate = $this->currencyService->getRate($currency);
        $convertedPaymentAmount = $this->currencyService->convertFromBase($paymentAmountInBaseCurrency, $currency);

        // get payment method
        $paymentMethod = $this->paymentMethodRepository->getUsersPaymentMethodById($userId, $paymentMethodId);

        if (empty($paymentMethod)) {
            throw new PaymentFailedException('Invalid Payment Method');
        }

        $externalPaymentId = null;
        $gateway = $paymentMethod->getMethod()->getPaymentGatewayName();

        // credit cart
        if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_CREDIT_CARD) {
            $creditCard = $paymentMethod->getMethod();

            if (empty($creditCard)) {
                throw new PaymentFailedException('Credit card not found.');
            }

            if (empty($creditCard->getExternalCustomerId()) && !empty($userId)) {
                $tempPurchaser = new Purchaser();
                $tempPurchaser->setId($userId);
                $tempPurchaser->setBrand($gateway);
                $tempPurchaser->setEmail(
                    $paymentMethod->getUserPaymentMethod()
                        ->getUser()
                        ->getEmail()
                );
                $tempPurchaser->setType(Purchaser::USER_TYPE);

                $customer = $this->getStripeCustomer($tempPurchaser, $gateway);
            }
            else {
                $customer = $this->stripePaymentGateway->getCustomer($gateway, $creditCard->getExternalCustomerId());
            }

            $card = $this->stripePaymentGateway->getCard($customer, $creditCard->getExternalId(), $gateway);

            $charge = $this->stripePaymentGateway->chargeCustomerCard(
                $gateway,
                $convertedPaymentAmount,
                $currency,
                $card,
                $customer
            );

            $externalPaymentId = $charge->id;
        }

        // paypal
        elseif ($paymentMethod->getMethodType() == PaymentMethod::TYPE_PAYPAL) {

            $payPalAgreement = $paymentMethod->getMethod();

            if (empty($payPalAgreement)) {
                throw new PaymentFailedException('PayPal agreement not found.');
            }

            $externalPaymentId = $this->payPalPaymentGateway->chargeBillingAgreement(
                $gateway,
                $convertedPaymentAmount,
                $currency,
                $payPalAgreement->getExternalId()
            );
        }

        // failure
        else {
            throw new PaymentFailedException('Invalid payment method.');
        }

        // payment failed
        if (empty($externalPaymentId)) {
            throw new PaymentFailedException('Could not recharge existing payment method.');
        }

        // store payment in database
        $payment = new Payment();

        $payment->setTotalDue($convertedPaymentAmount);
        $payment->setTotalPaid($convertedPaymentAmount);
        $payment->setTotalRefunded(0);
        $payment->setConversionRate($conversionRate);
        $payment->setType($paymentType);
        $payment->setExternalId($externalPaymentId);
        $payment->setExternalProvider($paymentMethod->getExternalProvider());
        $payment->setGatewayName(
            $paymentMethod->getMethod()
                ->getPaymentGatewayName()
        );
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setCurrency($currency);
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    /**
     * @param Purchaser $purchaser
     * @param Address $billingAddress
     * @param string $gateway
     * @param string $currency
     * @param float $paymentAmountInBaseCurrency
     * @param string $stripeToken
     * @param string $paymentType
     * @param bool $setAsDefault
     *
     * @return Payment
     *
     * @throws ORMException
     * @throws PaymentFailedException
     * @throws OptimisticLockException
     * @throws Throwable
     */
    public function chargeNewCreditCartPaymentMethod(
        Purchaser $purchaser,
        Address $billingAddress,
        string $gateway,
        string $currency,
        float $paymentAmountInBaseCurrency,
        string $stripeToken,
        string $paymentType,
        bool $setAsDefault = true
    )
    {
        // do currency conversion
        $conversionRate = $this->currencyService->getRate($currency);
        $convertedPaymentAmount = $this->currencyService->convertFromBase($paymentAmountInBaseCurrency, $currency);

        // first get the stripe customer
        $stripeCustomer = $this->getStripeCustomer($purchaser, $gateway);

        // make the stripe card
        $card = $this->stripePaymentGateway->createCustomerCard(
            $gateway,
            $stripeCustomer,
            $stripeToken
        );

        // charge the card
        $charge = $this->stripePaymentGateway->chargeCustomerCard(
            $gateway,
            $convertedPaymentAmount,
            $currency,
            $card,
            $stripeCustomer
        );

        // the charge was successful, store necessary data information
        // billing address
        $billingAddress = $this->setupBillingAddress($purchaser, $billingAddress);

        // payment method
        $paymentMethod = $this->paymentMethodService->createCreditCardPaymentMethod(
            $purchaser,
            $billingAddress,
            $card,
            $stripeCustomer,
            $gateway,
            $currency,
            $setAsDefault
        );

        if (empty($paymentMethod)) {
            throw new PaymentFailedException('Error creating payment method');
        }

        // store payment in database
        $payment = new Payment();

        $payment->setTotalDue($convertedPaymentAmount);
        $payment->setTotalPaid($convertedPaymentAmount);
        $payment->setTotalRefunded(0);
        $payment->setConversionRate($conversionRate);
        $payment->setType($paymentType);
        $payment->setExternalId($charge['id']);
        $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_STRIPE);
        $payment->setGatewayName(
            $paymentMethod->getMethod()
                ->getPaymentGatewayName()
        );
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setCurrency($currency);
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    /**
     * @param Purchaser $purchaser
     * @param Address $billingAddress
     * @param string $gateway
     * @param string $currency
     * @param string $stripeToken
     * @param bool $setAsDefault
     *
     * @return PaymentMethod
     *
     * @throws ORMException
     * @throws PaymentFailedException
     * @throws Throwable
     */
    public function createCreditCartPaymentMethod(
        Purchaser $purchaser,
        Address $billingAddress,
        string $gateway,
        string $currency,
        string $stripeToken,
        bool $setAsDefault = true
    ): PaymentMethod {
        $stripeCustomer = $this->getStripeCustomer($purchaser, $gateway);

        // make the stripe card
        $card = $this->stripePaymentGateway->createCustomerCard(
            $gateway,
            $stripeCustomer,
            $stripeToken
        );

        $billingAddress = $this->setupBillingAddress($purchaser, $billingAddress);

        // payment method
        $paymentMethod = $this->paymentMethodService->createCreditCardPaymentMethod(
            $purchaser,
            $billingAddress,
            $card,
            $stripeCustomer,
            $gateway,
            $currency,
            $setAsDefault
        );

        if (empty($paymentMethod)) {
            throw new PaymentFailedException('Error creating payment method');
        }

        return $paymentMethod;
    }

    /**
     * @param Purchaser $purchaser
     * @param Address $billingAddress
     *
     * @return Address
     *
     * @throws ORMException
     */
    public function setupBillingAddress(
        Purchaser $purchaser,
        Address $billingAddress
    ): Address {
        // billing address
        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {
            $user = $purchaser->getUserObject();

            $billingAddress->setUser($user);
        }
        elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && !empty($purchaser->getEmail())) {
            $customer = $purchaser->getCustomerEntity();

            $billingAddress->setCustomer($customer);
        }

        $billingAddress->setBrand($purchaser->getBrand());

        $this->entityManager->persist($billingAddress);

        return $billingAddress;
    }

    /**
     * @param Purchaser $purchaser
     * @param string $gateway
     *
     * @return Customer
     *
     * @throws Throwable
     */
    public function getStripeCustomer(
        Purchaser $purchaser,
        string $gateway
    ): Customer {
        $stripeCustomer = null;

        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {
            $stripeCustomer = null;

            $userStripeCustomerId = $this->userStripeCustomerIdRepository->getByUserId($purchaser->getId(), $gateway);

            // make a new stripe customer if none exist for the user
            if (empty($userStripeCustomerId)) {
                $stripeCustomer = $this->stripePaymentGateway->createCustomer(
                    $gateway,
                    $purchaser->getEmail()
                );

                $userStripeCustomerId = new UserStripeCustomerId();

                $userStripeCustomerId->setUser($purchaser->getUserObject());
                $userStripeCustomerId->setStripeCustomerId($stripeCustomer->id);
                $userStripeCustomerId->setPaymentGatewayName($gateway);

                $this->entityManager->persist($userStripeCustomerId);

                $this->entityManager->flush();
            }

            // otherwise use the users existing stripe customer
            else {
                $stripeCustomer = $this->stripePaymentGateway->getCustomer(
                    $gateway,
                    $userStripeCustomerId->getStripeCustomerId()
                );
            }
        }

        // for guest customers, we must always create a new stripe customer for guest orders
        elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && !empty($purchaser->getEmail())) {
            $stripeCustomer = $this->stripePaymentGateway->createCustomer(
                $gateway,
                $purchaser->getEmail()
            );
        }

        if (empty($stripeCustomer)) {
            throw new PaymentFailedException('Could not find or create customer.');
        }

        return $stripeCustomer;
    }

    /**
     * @param Purchaser $purchaser
     * @param Address $billingAddress
     * @param string $gateway
     * @param string $currency
     * @param float $paymentAmountInBaseCurrency
     * @param string $payPalToken
     * @param string $paymentType
     * @param bool $setAsDefault
     *
     * @return Payment
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PaymentFailedException
     * @throws Throwable
     */
    public function chargeNewPayPalPaymentMethod(
        Purchaser $purchaser,
        Address $billingAddress,
        string $gateway,
        string $currency,
        float $paymentAmountInBaseCurrency,
        string $payPalToken,
        string $paymentType,
        bool $setAsDefault = true
    )
    {
        // do currency conversion
        $conversionRate = $this->currencyService->getRate($currency);
        $convertedPaymentAmount = $this->currencyService->convertFromBase($paymentAmountInBaseCurrency, $currency);

        // get the agreement
        $billingAgreementId = $this->payPalPaymentGateway->createBillingAgreement(
            $gateway,
            $convertedPaymentAmount,
            $currency,
            $payPalToken
        );

        $transactionId = $this->payPalPaymentGateway->chargeBillingAgreement(
            $gateway,
            $convertedPaymentAmount,
            $currency,
            $billingAgreementId
        );

        // the charge was successful, store necessary data information
        $billingAddress = $this->setupBillingAddress($purchaser, $billingAddress);

        // payment method
        $paymentMethod = $this->paymentMethodService->createPayPalPaymentMethod(
            $purchaser,
            $billingAddress,
            $billingAgreementId,
            $gateway,
            $currency,
            $setAsDefault
        );

        if (empty($paymentMethod)) {
            throw new PaymentFailedException('Error creating payment method');
        }

        // store payment in database
        $payment = new Payment();

        $payment->setTotalDue($convertedPaymentAmount);
        $payment->setTotalPaid($convertedPaymentAmount);
        $payment->setTotalRefunded(0);
        $payment->setConversionRate($conversionRate);
        $payment->setType($paymentType);
        $payment->setExternalId($transactionId);
        $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_PAYPAL);
        $payment->setGatewayName(
            $paymentMethod->getMethod()
                ->getPaymentGatewayName()
        );
        $payment->setStatus(Payment::STATUS_PAID);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setCurrency($currency);
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    /**
     * @param Purchaser $purchaser
     * @param Address $billingAddress
     * @param string $gateway
     * @param string $currency
     * @param string $payPalToken
     * @param bool $setAsDefault
     *
     * @return PaymentMethod
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws PaymentFailedException
     * @throws Throwable
     */
    public function createPayPalPaymentMethod(
        Purchaser $purchaser,
        Address $billingAddress,
        string $gateway,
        string $currency,
        string $payPalToken,
        bool $setAsDefault = true
    ) {
        // get the agreement
        $billingAgreementId = $this->payPalPaymentGateway->createBillingAgreement(
            $gateway,
            0, // billing agreements do not have a value attached they can be charged whatever
            $currency,
            $payPalToken
        );

        $billingAddress = $this->setupBillingAddress($purchaser, $billingAddress);

        // payment method
        $paymentMethod = $this->paymentMethodService->createPayPalPaymentMethod(
            $purchaser,
            $billingAddress,
            $billingAgreementId,
            $gateway,
            $currency,
            $setAsDefault
        );

        if (empty($paymentMethod)) {
            throw new PaymentFailedException('Error creating payment method');
        }

        return $paymentMethod;
    }
}