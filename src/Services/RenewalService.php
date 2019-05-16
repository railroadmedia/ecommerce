<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;

class RenewalService
{
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
     * @var \Railroad\Ecommerce\Gateways\StripePaymentGateway
     */
    protected $stripePaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Gateways\PayPalPaymentGateway
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
     * @return Subscription
     *
     * @throws Exception
     */
    public function renew(Subscription $subscription)
    {
        // check for payment plan if the user have already paid all the cycles
        if (($subscription->getType() == config('ecommerce.type_payment_plan')) &&
            ((int)$subscription->getTotalCyclesPaid() >= (int)$subscription->getTotalCyclesDue())) {
            return $subscription;
        }

        /**
         * @var $paymentMethod \Railroad\Ecommerce\Entities\PaymentMethod
         */
        $paymentMethod = $subscription->getPaymentMethod();

        $payment = new Payment();

        $currency = $subscription->getCurrency();

        $chargePrice = null;

        if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_CREDIT_CARD) {

            try {

                $totalTaxDue = $this->taxService->getTaxesDueTotal(
                    $subscription->getTotalPrice(),
                    0,
                    !empty($paymentMethod->getBillingAddress()) ?
                        $paymentMethod->getBillingAddress()
                            ->toStructure() : null
                );

                $chargePrice = $this->currencyService->convertFromBase(
                    $subscription->getTotalPrice() + $totalTaxDue,
                    $currency
                );

                /**
                 * @var $method \Railroad\Ecommerce\Entities\CreditCard
                 */
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

                $payment->setTotalPaid($chargePrice)
                    ->setExternalProvider('stripe')
                    ->setExternalId($charge->id)
                    ->setGatewayName(
                        $paymentMethod->getMethod()
                            ->getPaymentGatewayName()
                    )
                    ->setStatus('succeeded')
                    ->setMessage('')
                    ->setCurrency($currency)
                    ->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency]);

            } catch (Exception $exception) {

                $payment->setTotalPaid(0)
                    ->setExternalProvider('stripe')
                    ->setExternalId($charge->id ?? null)
                    ->setGatewayName(
                        $paymentMethod->getMethod()
                            ->getPaymentGatewayName()
                    )
                    ->setStatus('failed')
                    ->setMessage($exception->getMessage())
                    ->setCurrency($currency)
                    ->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency] ?? 0);

                $paymentException = $exception;
            }
        }
        elseif ($paymentMethod->getMethodType() == PaymentMethod::TYPE_PAYPAL) {

            try {

                $totalTaxDue = $this->taxService->getTaxesDueTotal(
                    $subscription->getTotalPrice(),
                    0,
                    !empty($paymentMethod->getBillingAddress()) ?
                        $paymentMethod->getBillingAddress()
                            ->toStructure() : null
                );

                $chargePrice = $this->currencyService->convertFromBase(
                    $subscription->getTotalPrice() + $totalTaxDue,
                    $currency
                );

                /**
                 * @var $method \Railroad\Ecommerce\Entities\PaypalBillingAgreement
                 */
                $method = $paymentMethod->getMethod();

                $transactionId = $this->paypalPaymentGateway->chargeBillingAgreement(
                    $method->getPaymentGatewayName(),
                    $chargePrice,
                    $currency,
                    $method->getExternalId(),
                    ''
                );

                $payment->setTotalPaid($chargePrice)
                    ->setExternalProvider('paypal')
                    ->setExternalId($transactionId)
                    ->setGatewayName(
                        $paymentMethod->getMethod()
                            ->getPaymentGatewayName()
                    )
                    ->setStatus('succeeded')
                    ->setMessage('')
                    ->setCurrency($currency)
                    ->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency]);

            } catch (Exception $exception) {

                $payment->setTotalPaid(0)
                    ->setExternalProvider('paypal')
                    ->setExternalId($transactionId ?? null)
                    ->setGatewayName(
                        $paymentMethod->getMethod()
                            ->getPaymentGatewayName()
                    )
                    ->setStatus('failed')
                    ->setMessage($exception->getMessage())
                    ->setCurrency($currency)
                    ->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency] ?? 0);

                $paymentException = $exception;
            }
        }
        else {
            $payment->setTotalPaid(0)
                ->setExternalProvider('unknown')
                ->setExternalId($transactionId ?? null)
                ->setGatewayName(
                    $paymentMethod->getMethod()
                        ->getPaymentGatewayName()
                )
                ->setStatus('failed')
                ->setMessage('Invalid payment method.')
                ->setCurrency($currency)
                ->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency] ?? 0);
        }

        // save payment data in DB
        $payment->setTotalDue($chargePrice)
            ->setTotalRefunded(0)
            ->setType(config('ecommerce.renewal_payment_type'))
            ->setPaymentMethod($paymentMethod)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        $this->entityManager->flush();

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment->setSubscription($subscription)
            ->setPayment($payment);

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
                    throw new Exception("Subscription type not configured");
                    break;
            }

            $subscription->setIsActive(true)
                ->setCanceledOn(null)
                ->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1)
                ->setPaidUntil(
                    $nextBillDate ? $nextBillDate->startOfDay() :
                        Carbon::now()
                            ->addMonths(1)
                )
                ->setUpdatedAt(Carbon::now());

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

                $subscription->setIsActive(false)
                    ->setUpdatedAt(Carbon::now());
                $subscription->setNote('De-activated due to payments failing.');

                $this->entityManager->flush();

                event(
                    new SubscriptionEvent($subscription->getId(), 'deactivated')
                );
            }

            throw isset($paymentException) ? $paymentException : new Exception();
        }

        $this->userProductService->updateSubscriptionProducts($subscription);

        return $subscription;
    }
}