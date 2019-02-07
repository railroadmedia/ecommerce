<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Services\PaymentMethodService;

class RenewalService
{
    /**
     * @var EntityRepository
     */
    protected $creditCardRepository;

    /**
     * @var CurrencyService
     */
    protected $currencyService;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    protected $paypalRepository;

    /**
     * @var EntityRepository
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
     * RenewalService constructor.
     *
     * @param EntityManager $entityManager
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PayPalPaymentGateway $payPalPaymentGateway
     */
    public function __construct(
        CurrencyService $currencyService,
        EntityManager $entityManager,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway
    ) {
        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->paypalPaymentGateway = $payPalPaymentGateway;

        $this->creditCardRepository = $this->entityManager
                ->getRepository(CreditCard::class);

        $this->paypalRepository = $this->entityManager
                ->getRepository(PaypalBillingAgreement::class);

        $this->subscriptionPaymentRepository = $this->entityManager
                ->getRepository(SubscriptionPayment::class);
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
        if (
            ($subscription->getType() == ConfigService::$paymentPlanType) &&
            (
                (int)$subscription->getTotalCyclesPaid() >=
                (int)$subscription->getTotalCyclesDue()
            )
        ) {
            return $subscription;
        }

        /**
         * @var $paymentMethod \Railroad\Ecommerce\Entities\PaymentMethod
         */
        $paymentMethod = $subscription->getPaymentMethod();

        $payment = new Payment();

        $currency = $subscription->getCurrency();

        $chargePrice = $this->currencyService->convertFromBase(
            $subscription->getTotalPricePerPayment(),
            $currency
        );

        if (
            $paymentMethod->getMethodType() ==
            PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE
        ) {
            try {
                /**
                 * @var $method \Railroad\Ecommerce\Entities\CreditCard
                 */
                $method = $this->creditCardRepository->find(
                    $paymentMethod->getMethodId()
                );

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

                $payment
                    ->setTotalPaid($chargePrice)
                    ->setExternalProvider('stripe')
                    ->setExternalId($charge->id)
                    ->setStatus('succeeded')
                    ->setMessage('')
                    ->setCurrency($currency);

            } catch (Exception $exception) {

                $payment
                    ->setTotalPaid(0)
                    ->setExternalProvider('stripe')
                    ->setExternalId($charge->id ?? null)
                    ->setStatus('failed')
                    ->setMessage($exception->getMessage())
                    ->setCurrency($currency);

                $paymentException = $exception;
            }
        } elseif (
            $paymentMethod->getMethodType() ==
            PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE
        ) {

            try {
                /**
                 * @var $method \Railroad\Ecommerce\Entities\PaypalBillingAgreement
                 */
                $method = $this->paypalRepository->find(
                    $paymentMethod->getMethodId()
                );

                $transactionId = $this->paypalPaymentGateway
                    ->chargeBillingAgreement(
                        $method->getPaymentGatewayName(),
                        $chargePrice,
                        $currency,
                        $method->getExternalId(),
                        ''
                    );

                $payment
                    ->setTotalPaid($subscription->getTotalPricePerPayment())
                    ->setExternalProvider('paypal')
                    ->setExternalId($transactionId)
                    ->setStatus('succeeded')
                    ->setMessage('')
                    ->setCurrency($currency);

            } catch (Exception $exception) {

                $payment
                    ->setTotalPaid(0)
                    ->setExternalProvider('paypal')
                    ->setExternalId($transactionId ?? null)
                    ->setStatus('failed')
                    ->setMessage($exception->getMessage())
                    ->setCurrency($currency);

                $paymentException = $exception;
            }
        }

        // save payment data in DB
        $payment
            ->setTotalDue($subscription->getTotalPricePerPayment())
            ->setType(ConfigService::$renewalPaymentType)
            ->setPaymentMethod($paymentMethod)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        $this->entityManager->flush();

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment
            ->setSubscription($subscription)
            ->setPayment($payment);

        $this->entityManager->persist($subscriptionPayment);

        $this->entityManager->flush();

        if ($payment->getTotalPaid() > 0) {

            $nextBillDate = null;

            switch ($subscription->getIntervalType()) {
                case ConfigService::$intervalTypeMonthly:
                    $nextBillDate = Carbon::now()
                                ->addMonths($subscription->getIntervalCount());
                break;

                case ConfigService::$intervalTypeYearly:
                    $nextBillDate = Carbon::now()
                                ->addYears($subscription->getIntervalCount());
                break;

                case ConfigService::$intervalTypeDaily:
                    $nextBillDate = Carbon::now()
                                ->addDays($subscription->getIntervalCount());
                break;

                default:
                    throw new Exception("Subscription type not configured");
                break;
            }

            $subscription
                ->setIsActive(true)
                ->setCanceledOn(null)
                ->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1)
                ->setPaidUntil($nextBillDate ? $nextBillDate->startOfDay() : Carbon::now()->addMonths(1))
                ->setUpdatedAt(Carbon::now());

            $this->entityManager->flush();

            event(new SubscriptionEvent($subscription->getId(), 'renewed'));

        } else {

            $qb = $this->subscriptionPaymentRepository
                        ->createQueryBuilder('sp');

            $qb
                ->select('count(p)')
                ->join('sp.payment', 'p')
                ->where($qb->expr()->eq('sp.subscription', ':subscription'))
                ->andWhere($qb->expr()->in('p.status', ':statuses'))
                ->setParameter('subscription', $subscription)
                ->setParameter('statuses', ['0', 'failed']);

            $failedPaymentsCount = $qb->getQuery()->getSingleScalarResult();

            if (
                $failedPaymentsCount >=
                ConfigService::$failedPaymentsBeforeDeactivation ?? 1
            ) {

                $subscription
                    ->setIsActive(false)
                    ->setNote('De-activated due to payments failing.')
                    ->setUpdatedAt(Carbon::now());

                $this->entityManager->flush();

                event(
                    new SubscriptionEvent($subscription->getId(),'deactivated')
                );
            }

            throw $paymentException;
        }

        return $subscription;
    }
}