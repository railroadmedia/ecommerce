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
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;

class RenewalService
{
    /**
     * @var EntityRepository
     */
    protected $creditCardRepository;

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
        EntityManager $entityManager,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway
    ) {
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
                    $subscription->getTotalPricePerPayment(),
                    $subscription->getCurrency(),
                    $card,
                    $customer,
                    ''
                );

                $payment
                    ->setPaid($subscription->getTotalPricePerPayment())
                    ->setExternalProvider('stripe')
                    ->setExternalId($charge->id)
                    ->setStatus('succeeded')
                    ->setMessage('')
                    ->setCurrency($subscription->getCurrency());

            } catch (Exception $exception) {

                $payment
                    ->setPaid(0)
                    ->setExternalProvider('stripe')
                    ->setExternalId($charge->id ?? null)
                    ->setStatus('failed')
                    ->setMessage($exception->getMessage())
                    ->setCurrency($subscription->getCurrency());

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
                        $subscription->getTotalPricePerPayment(),
                        $subscription->getCurrency(),
                        $method->getExternalId(),
                        ''
                    );

                $payment
                    ->setPaid($subscription->getTotalPricePerPayment())
                    ->setExternalProvider('paypal')
                    ->setExternalId($transactionId)
                    ->setStatus('succeeded')
                    ->setMessage('')
                    ->setCurrency($subscription->getCurrency());

            } catch (Exception $exception) {

                $payment
                    ->setPaid(0)
                    ->setExternalProvider('paypal')
                    ->setExternalId($transactionId ?? null)
                    ->setStatus('failed')
                    ->setMessage($exception->getMessage())
                    ->setCurrency($subscription->getCurrency());

                $paymentException = $exception;
            }
        }

        // save payment data in DB
        $payment
            ->setDue($subscription->getTotalPricePerPayment())
            ->setType(ConfigService::$renewalPaymentType)
            ->setPaymentMethod($paymentMethod)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment
            ->setSubscription($subscription)
            ->setPayment($payment);

        $this->entityManager->persist($subscriptionPayment);

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
        }

        if ($payment->getPaid() > 0) {

            $subscription
                ->setIsActive(true)
                ->setCanceledOn(null)
                ->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1)
                ->setPaidUntil($nextBillDate->startOfDay())
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
                ->setParameter('statuses', [0, 'failed']); // inspect query

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

    /**
     * @param $subscriptionId
     * @return mixed
     * @throws Exception
     */
    public function renew_deprecated($subscriptionId)
    {
        /*
        $dueSubscription = $this->subscriptionRepository->read($subscriptionId);

        //check for payment plan if the user have already paid all the cycles
        if (($dueSubscription['type'] == ConfigService::$paymentPlanType) &&
            ((int)$dueSubscription['total_cycles_paid'] >= (int)$dueSubscription['total_cycles_due'])) {
            return false;
        }

        if ($dueSubscription['payment_method']['method_type'] ==
            PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {

            try {
                $customer = $this->stripePaymentGateway->getCustomer(
                    $dueSubscription['payment_method']['method']['payment_gateway_name'],
                    $dueSubscription['payment_method']['method']['external_customer_id']
                );

                $card = $this->stripePaymentGateway->getCard(
                    $customer,
                    $dueSubscription['payment_method']['method']['external_id'],
                    $dueSubscription['payment_method']['method']['payment_gateway_name']
                );

                $charge = $this->stripePaymentGateway->chargeCustomerCard(
                    $dueSubscription['payment_method']['method']['payment_gateway_name'],
                    $dueSubscription['total_price_per_payment'],
                    $dueSubscription['currency'],
                    $card,
                    $customer,
                    ''
                );

                $paymentData = [
                    'paid' => $dueSubscription['total_price_per_payment'],
                    'external_provider' => 'stripe',
                    'external_id' => $charge->id,
                    'status' => 'succeeded',
                    'message' => '',
                    'currency' => $dueSubscription['currency'],
                ];

            } catch (Exception $exception) {
                $paymentData = [
                    'paid' => 0,
                    'external_provider' => 'stripe',
                    'external_id' => $charge->id ?? null,
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                    'currency' => $dueSubscription['currency'],
                ];

                $paymentException = $exception;
            }

        } elseif ($dueSubscription['payment_method']['method_type'] ==
            PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {

            try {
                $transactionId = $this->paypalPaymentGateway->chargeBillingAgreement(
                    $dueSubscription['payment_method']['method']['payment_gateway_name'],
                    $dueSubscription['total_price_per_payment'],
                    $dueSubscription['currency'],
                    $dueSubscription['payment_method']['method']['external_id'],
                    ''
                );

                $paymentData = [
                    'paid' => $dueSubscription['total_price_per_payment'],
                    'external_provider' => 'paypal',
                    'external_id' => $transactionId,
                    'status' => 'succeeded',
                    'message' => '',
                    'currency' => $dueSubscription['currency'],
                ];

            } catch (Exception $exception) {
                $paymentData = [
                    'paid' => 0,
                    'external_provider' => 'paypal',
                    'external_id' => $transactionId ?? null,
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                    'currency' => $dueSubscription['currency'],
                ];

                $paymentException = $exception;
            }
        }

        //save payment data in DB
        $payment = $this->paymentRepository->create(
            array_merge(
                $paymentData,
                [
                    'due' => $dueSubscription['total_price_per_payment'],
                    'type' => ConfigService::$renewalPaymentType,
                    'payment_method_id' => $dueSubscription['payment_method']['id'],
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            )
        );

        $subscriptionPayment = $this->subscriptionPaymentRepository->create(
            [
                'subscription_id' => $dueSubscription['id'],
                'payment_id' => $payment['id'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        if ($dueSubscription['interval_type'] == ConfigService::$intervalTypeMonthly) {
            $nextBillDate =
                Carbon::now()
                    ->addMonths($dueSubscription['interval_count'])
                    ->startOfDay();
        } elseif ($dueSubscription['interval_type'] == ConfigService::$intervalTypeYearly) {
            $nextBillDate =
                Carbon::now()
                    ->addYears($dueSubscription['interval_count'])
                    ->startOfDay();
        } elseif ($dueSubscription['interval_type'] == ConfigService::$intervalTypeDaily) {
            $nextBillDate =
                Carbon::now()
                    ->addDays($dueSubscription['interval_count'])
                    ->startOfDay();
        }
        if ($dueSubscription['user_id']) {
            $subscriptionProducts =
                $this->getSubscriptionProducts($dueSubscription['order_id'], $dueSubscription['product_id']);
        }
        if ($paymentData['paid'] > 0) {
            $this->subscriptionRepository->update(
                $dueSubscription['id'],
                [
                    'is_active' => true,
                    'canceled_on' => null,
                    'total_cycles_paid' => $dueSubscription['total_cycles_paid'] + 1,
                    'paid_until' => $nextBillDate->toDateTimeString(),
                    'updated_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );
            event(new SubscriptionEvent($dueSubscription['id'], 'renewed'));
        } else {
            $subscriptionPayments =
                $this->subscriptionPaymentRepository->query()
                    ->where('subscription_id', $dueSubscription['id'])
                    ->get();
            $failedPayments =
                $this->paymentRepository->query()
                    ->whereIn('id', $subscriptionPayments->pluck('payment_id'))
                    ->whereIn('status', [0, 'failed'])
                    ->get();
            if (count($failedPayments) >= ConfigService::$failedPaymentsBeforeDeactivation ?? 1) {
                $this->subscriptionRepository->update(
                    $dueSubscription['id'],
                    [
                        'is_active' => false,
                        'note' => 'De-activated due to payments failing.',
                        'updated_on' => Carbon::now()
                            ->toDateTimeString(),
                    ]
                );
                $subscriptionProducts = $this->getSubscriptionProducts(
                    $dueSubscription['order_id'],
                    $dueSubscription['product_id']
                );
                event(new SubscriptionEvent($dueSubscription['id'], 'deactivated'));
            }

            throw $paymentException;
        }

        return $this->subscriptionRepository->read($subscriptionId);
        */
    }

    /**
     * Return subscription's products.
     *
     * @param null|int $subscriptionOrderId
     * @param null|int $subscriptionProductId
     * @return array|\Illuminate\Support\Collection
     */
    public function getSubscriptionProducts($subscriptionOrderId = null, $subscriptionProductId = null)
    {
        // tmp disabled
        /*
        $subscriptionProducts = [];

        if ($subscriptionProductId) {
            $subscriptionProducts[$subscriptionProductId] = 1;
        } elseif ($subscriptionOrderId) {
            $products =
                $this->orderItemRepository->query()
                    ->where('order_id', $subscriptionOrderId)
                    ->get();
            $subscriptionProducts = $products->pluck('quantity', 'product_id');
        }

        return $subscriptionProducts;
        */
    }
}