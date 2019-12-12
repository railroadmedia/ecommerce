<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\PaymentTaxes;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\SubscriptionRenewException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Throwable;

class RenewalService
{
    const DEACTIVATION_MESSAGE = 'De-activated due to payments failing.';

    /**
     * @var CreditCardRepository
     */
    protected $creditCardRepository;

    /**
     * @var CurrencyService
     */
    protected $currencyService;

    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

    /**
     * @var PaypalBillingAgreementRepository
     */
    protected $paypalRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    protected $subscriptionPaymentRepository;

    /**
     * @var StripePaymentGateway
     */
    protected $stripePaymentGateway;

    /**
     * @var PayPalPaymentGateway
     */
    protected $paypalPaymentGateway;

    /**
     * @var TaxService
     */
    protected $taxService;

    /**
     * @var UserProductService
     */
    protected $userProductService;

    /**
     * RenewalService constructor.
     *
     * @param CreditCardRepository $creditCardRepository
     * @param CurrencyService $currencyService
     * @param EcommerceEntityManager $entityManager
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PaypalBillingAgreementRepository $paypalBillingAgreementRepository
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param TaxService $taxService
     * @param UserProductService $userProductService
     */
    public function __construct(
        CreditCardRepository $creditCardRepository,
        CurrencyService $currencyService,
        EcommerceEntityManager $entityManager,
        StripePaymentGateway $stripePaymentGateway,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
        PayPalPaymentGateway $payPalPaymentGateway,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        TaxService $taxService,
        UserProductService $userProductService
    )
    {
        $this->creditCardRepository = $creditCardRepository;
        $this->currencyService = $currencyService;
        $this->taxService = $taxService;
        $this->entityManager = $entityManager;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->paypalRepository = $paypalBillingAgreementRepository;
        $this->paypalPaymentGateway = $payPalPaymentGateway;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->userProductService = $userProductService;
    }

    /**
     * @param Subscription $subscription
     *
     * @return Payment|null
     *
     * @throws Throwable
     */
    public function renew(Subscription $subscription): ?Payment
    {
        if ($subscription->getType() == Subscription::TYPE_APPLE_SUBSCRIPTION ||
            $subscription->getType() == Subscription::TYPE_GOOGLE_SUBSCRIPTION) {

            throw new SubscriptionRenewException(
                'Subscription made by mobile application may not be renewed by web application'
            );
        }

        $oldSubscription = clone($subscription);

        // check for payment plan if the user have already paid all the cycles
        if (($subscription->getType() == config('ecommerce.type_payment_plan')) &&
            ((int)$subscription->getTotalCyclesPaid() >= (int)$subscription->getTotalCyclesDue())) {
            return null;
        }

        /** @var $paymentMethod PaymentMethod */
        $paymentMethod = $subscription->getPaymentMethod();

        $payment = new Payment();

        /** @var $address Address */
        if (!empty($paymentMethod->getBillingAddress())) {
            $address = $paymentMethod->getBillingAddress()->toStructure();
        } else {
            $address = new Address();
        }

        $currency = $paymentMethod->getCurrency();

        // support for legacy tax
        $subscriptionPricePerPayment = round($subscription->getTotalPrice() - $subscription->getTax(), 2);

        $taxes = $this->taxService->getTaxesDueTotal(
            $subscriptionPricePerPayment,
            0,
            $address
        );

        $chargePrice = $this->currencyService->convertFromBase(
            round($subscriptionPricePerPayment + $taxes, 2),
            $currency
        );

        $exceptionToThrow = null;

        if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_CREDIT_CARD) {

            try {

                // todo: revamp tax system
//                $totalTaxDue = $this->taxService->getTaxesDueTotal(
//                    $subscription->getTotalPrice(),
//                    0,
//                    !empty($paymentMethod->getBillingAddress()) ?
//                        $paymentMethod->getBillingAddress()
//                            ->toStructure() : null
//                );

                /** @var $method CreditCard */
                $method = $paymentMethod->getCreditCard();

                $customer = $this->stripePaymentGateway->getCustomer(
                    $method->getPaymentGatewayName(),
                    $method->getExternalCustomerId()
                );

                $card = $this->stripePaymentGateway->getCard(
                    $customer,
                    $method->getExternalId(),
                    $method->getPaymentGatewayName()
                );

                $charge = $this->stripePaymentGateway->chargeCustomerCard(
                    $method->getPaymentGatewayName(),
                    $chargePrice,
                    $currency,
                    $card,
                    $customer,
                    ''
                );

                $payment->setTotalPaid($chargePrice);
                $payment->setExternalProvider('stripe');
                $payment->setExternalId($charge->id);
                $payment->setGatewayName(
                    $paymentMethod->getMethod()
                        ->getPaymentGatewayName()
                );
                $payment->setStatus('succeeded');
                $payment->setMessage('');
                $payment->setCurrency($currency);
                $payment->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency]);

            } catch (Exception $exception) {

                $payment->setTotalPaid(0);
                $payment->setExternalProvider('stripe');
                $payment->setExternalId($charge->id ?? null);
                $payment->setGatewayName(
                    $paymentMethod->getMethod()
                        ->getPaymentGatewayName()
                );
                $payment->setStatus('failed');
                $payment->setMessage($exception->getMessage());
                $payment->setCurrency($currency);
                $payment->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency] ?? 0);

                $subscription->setFailedPayment($payment);

                $exceptionToThrow = $exception;
            }
        }
        elseif ($paymentMethod->getMethodType() == PaymentMethod::TYPE_PAYPAL) {

            try {

                // todo: revamp tax system
//                $totalTaxDue = $this->taxService->getTaxesDueTotal(
//                    $subscription->getTotalPrice(),
//                    0,
//                    !empty($paymentMethod->getBillingAddress()) ?
//                        $paymentMethod->getBillingAddress()
//                            ->toStructure() : null
//                );

                /** @var $method PaypalBillingAgreement */
                $method = $paymentMethod->getMethod();

                $transactionId = $this->paypalPaymentGateway->chargeBillingAgreement(
                    $method->getPaymentGatewayName(),
                    $chargePrice,
                    $currency,
                    $method->getExternalId(),
                    ''
                );

                $payment->setTotalPaid($chargePrice);
                $payment->setExternalProvider('paypal');
                $payment->setExternalId($transactionId);
                $payment->setGatewayName(
                    $paymentMethod->getMethod()
                        ->getPaymentGatewayName()
                );
                $payment->setStatus('succeeded');
                $payment->setMessage('');
                $payment->setCurrency($currency);
                $payment->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency]);

            } catch (Exception $exception) {

                $payment->setTotalPaid(0);
                $payment->setExternalProvider('paypal');
                $payment->setExternalId($transactionId ?? null);
                $payment->setGatewayName(
                    $paymentMethod->getMethod()
                        ->getPaymentGatewayName()
                );
                $payment->setStatus('failed');
                $payment->setMessage($exception->getMessage());
                $payment->setCurrency($currency);
                $payment->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency] ?? 0);

                $subscription->setFailedPayment($payment);

                $exceptionToThrow = $exception;
            }
        }
        else {
            $payment->setTotalPaid(0);
            $payment->setExternalProvider('unknown');
            $payment->setExternalId($transactionId ?? null);
            $payment->setGatewayName(
                $paymentMethod->getMethod()
                    ->getPaymentGatewayName()
            );
            $payment->setStatus('failed');
            $payment->setMessage('Invalid payment method.');
            $payment->setCurrency($currency);
            $payment->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency] ?? 0);

            $subscription->setFailedPayment($payment);
        }

        // save payment data in DB
        $payment->setTotalDue($chargePrice);
        $payment->setTotalRefunded(0);
        $payment->setType(config('ecommerce.renewal_payment_type'));
        $payment->setPaymentMethod($paymentMethod);
        $payment->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        $this->entityManager->flush();

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment->setSubscription($subscription);
        $subscriptionPayment->setPayment($payment);

        $this->entityManager->persist($subscriptionPayment);

        $this->entityManager->flush();

        if ($payment->getTotalPaid() > 0) {

            $nextBillDate = null;

            switch ($subscription->getIntervalType()) {
                case config('ecommerce.interval_type_monthly'):
                    $nextBillDate =
                        Carbon::now()
                            ->addMonths($subscription->getIntervalCount());
                    break;

                case config('ecommerce.interval_type_yearly'):
                    $nextBillDate =
                        Carbon::now()
                            ->addYears($subscription->getIntervalCount());
                    break;

                case config('ecommerce.interval_type_daily'):
                    $nextBillDate =
                        Carbon::now()
                            ->addDays($subscription->getIntervalCount());
                    break;

                default:
                    throw new Exception("Subscription interval type not configured");
                    break;
            }


            $subscription->setIsActive(true);
            $subscription->setCanceledOn(null);
            $subscription->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1);
            $subscription->setPaidUntil(
                $nextBillDate ? $nextBillDate->startOfDay() :
                    Carbon::now()
                        ->addMonths(1)
            );
            $subscription->setFailedPayment(null);
            $subscription->setUpdatedAt(Carbon::now());

            event(new SubscriptionRenewed($subscription, $payment));
            event(new SubscriptionUpdated($oldSubscription, $subscription));

            $paymentTaxes = new PaymentTaxes();

            $paymentTaxes->setPayment($payment);
            $paymentTaxes->setCountry($address->getCountry());
            $paymentTaxes->setRegion($address->getRegion());
            $paymentTaxes->setProductRate(
                $this->taxService->getProductTaxRate($address)
            );
            $paymentTaxes->setShippingRate(
                $this->taxService->getShippingTaxRate($address)
            );

            $productTaxDue = $this->taxService->getTaxesDueForProductCost(
                $subscriptionPricePerPayment,
                $address
            );

            $paymentTaxes->setProductTaxesPaid(round($productTaxDue, 2));
            $paymentTaxes->setShippingTaxesPaid(0);

            $this->entityManager->persist($paymentTaxes);

            $this->entityManager->flush();

            event(new SubscriptionEvent($subscription->getId(), 'renewed'));

        }
        else {

            $qb = $this->subscriptionPaymentRepository->createQueryBuilder('sp');

            $qb->select('count(p)')
                ->join('sp.payment', 'p')
                ->where(
                    $qb->expr()
                        ->eq('sp.subscription', ':subscription')
                )
                ->andWhere(
                    $qb->expr()
                        ->in('p.status', ':statuses')
                )
                ->setParameter('subscription', $subscription)
                ->setParameter('statuses', ['0', 'failed']);

            $failedPaymentsCount =
                $qb->getQuery()
                    ->getSingleScalarResult();

            if ($failedPaymentsCount >= config('ecommerce.paypal.failed_payments_before_de_activation') ?? 1) {

                $subscription->setIsActive(false);
                $subscription->setUpdatedAt(Carbon::now());
                $subscription->setNote(self::DEACTIVATION_MESSAGE);

                $this->entityManager->flush();

                event(new SubscriptionUpdated($oldSubscription, $subscription));

                event(
                    new SubscriptionEvent($subscription->getId(), 'deactivated')
                );
            }

            $this->userProductService->updateSubscriptionProducts($subscription);

            throw PaymentFailedException::createFromException(
                $exceptionToThrow,
                $payment
            );
        }

        $this->userProductService->updateSubscriptionProducts($subscription);

        return $payment;
    }
}