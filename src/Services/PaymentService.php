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
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\UserStripeCustomerIdRepository;

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
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var UserStripeCustomerIdRepository
     */
    private $userStripeCustomerIdRepository;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;

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
     * @param CreditCardRepository $creditCardRepository
     * @param UserStripeCustomerIdRepository $userStripeCustomerIdRepository
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PaypalBillingAgreementRepository $paypalBillingAgreementRepository
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param CurrencyService $currencyService
     * @param PaymentMethodService $paymentMethodService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        PaymentMethodRepository $paymentMethodRepository,
        CreditCardRepository $creditCardRepository,
        UserStripeCustomerIdRepository $userStripeCustomerIdRepository,
        StripePaymentGateway $stripePaymentGateway,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
        PayPalPaymentGateway $payPalPaymentGateway,
        CurrencyService $currencyService,
        PaymentMethodService $paymentMethodService
    )
    {
        $this->entityManager = $entityManager;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->creditCardRepository = $creditCardRepository;
        $this->userStripeCustomerIdRepository = $userStripeCustomerIdRepository;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->currencyService = $currencyService;
        $this->paymentMethodService = $paymentMethodService;
    }

    /**
     * @param string $gateway
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
     */
    public function chargeUsersExistingPaymentMethod(
        string $gateway,
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

        // credit cart
        if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_CREDIT_CARD) {
            $creditCard = $paymentMethod->getMethod();

            if (empty($creditCard)) {
                throw new PaymentFailedException('Credit card not found.');
            }

            $customer = $this->stripePaymentGateway->getCustomer($gateway, $creditCard->getExternalCustomerId());
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
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Throwable
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

        // for users
        if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {
            $stripeCustomer = null;

            $userStripeCustomerId = $this->userStripeCustomerIdRepository->getByUserId($purchaser->getId());

            // make a new stripe customer if none exist for the user
            if (empty($userStripeCustomerId)) {
                $stripeCustomer = $this->stripePaymentGateway->createCustomer(
                    $gateway,
                    $purchaser->getEmail()
                );

                $userStripeCustomerId = new UserStripeCustomerId();

                $userStripeCustomerId->setUser($purchaser->getUserObject());
                $userStripeCustomerId->setStripeCustomerId($stripeCustomer->id);

                $this->entityManager->persist($userStripeCustomerId);
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
            throw new PaymentFailedException('Error charging payment method');
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
     * @throws \Throwable
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
            throw new PaymentFailedException('Error charging payment method');
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

}