<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\PaymentTaxes;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Entities\Structures\SubscriptionRenewal;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewFailed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\SubscriptionRenewException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\FailedBillingSubscriptionsRequest;
use Throwable;

class SubscriptionService
{
    const DEACTIVATION_MESSAGE = 'De-activated due to renewal payment fail.';
    const CANCELLED_DUE_TO_NO_PAYMENT_METHOD_MESSAGE = 'Canceled due to not having a payment method, or the payment method was deleted.';

    /**
     * @var CurrencyService
     */
    protected $currencyService;

    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

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
     * @var PaymentService
     */
    private $paymentService;

    /**
     * SubscriptionService constructor.
     *
     * @param CurrencyService $currencyService
     * @param EcommerceEntityManager $entityManager
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param SubscriptionRepository $subscriptionRepository
     * @param TaxService $taxService
     * @param UserProductService $userProductService
     * @param PaymentService $paymentService
     */
    public function __construct(
        CurrencyService $currencyService,
        EcommerceEntityManager $entityManager,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway,
        SubscriptionRepository $subscriptionRepository,
        TaxService $taxService,
        UserProductService $userProductService,
        PaymentService $paymentService
    )
    {
        $this->currencyService = $currencyService;
        $this->taxService = $taxService;
        $this->entityManager = $entityManager;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->paypalPaymentGateway = $payPalPaymentGateway;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
        $this->paymentService = $paymentService;
    }

    /**
     * @param FailedBillingSubscriptionsRequest $request
     *
     * @return ResultsQueryBuilderComposite
     */
    public function getFailedBilling(FailedBillingSubscriptionsRequest $request): ResultsQueryBuilderComposite
    {
        $subscriptionsAndBuilder = $this->subscriptionRepository->indexFailedBillingByRequest($request);

        if ($request->get('type') == Subscription::TYPE_SUBSCRIPTION) {
            $userIds = [];
            // gather subscriptions user ids
            foreach ($subscriptionsAndBuilder->getResults() as $subscription) {
                $userIds[] = $subscription->getUser()->getId();
            }

            // fetch active subscriptions of the user ids, filtered by request brands
            $activeSubscriptions = $this->subscriptionRepository->getActiveUserSubscriptions($userIds, $request);

            if (!empty($activeSubscriptions)) {
                /*
                // structure is:
                [
                    'user_id' => [
                        'brandOne' => $subscriptionOne,
                        'brandTwo' => $subscriptionTwo,
                    ]
                ]
                */
                $activeSubscriptionsMap = [];

                foreach ($activeSubscriptions as $subscription) {

                    $userId = $subscription->getUser()->getId();
                    $brand = $subscription->getBrand();

                    if (!isset($activeSubscriptionsMap[$userId])) {
                        $activeSubscriptionsMap[$userId] = [];
                    }

                    $activeSubscriptionsMap[$userId][$brand] = $subscription;
                }

                $filteredFailedSubscriptions = [];
                $filtered = false;

                foreach ($subscriptionsAndBuilder->getResults() as $subscription) {
                    $userId = $subscription->getUser()->getId();
                    $brand = $subscription->getBrand();

                    if (isset($activeSubscriptionsMap[$userId][$brand])) {
                        // if the user has an other active subscription, do not return this failed subscription
                        $filtered = true;
                    } else {
                        $filteredFailedSubscriptions[] = $subscription;
                    }
                }

                if ($filtered) {
                    // if any subscription was filtered out, return new ResultsQueryBuilderComposite
                    return new ResultsQueryBuilderComposite(
                        $filteredFailedSubscriptions,
                        $subscriptionsAndBuilder->getQueryBuilder()
                    );
                } // else return default result
            }
        }

        return $subscriptionsAndBuilder;
    }

    /**
     * @param array $usersIds
     *
     * @return SubscriptionRenewal[]
     *
     * @throws Throwable
     *
     * @noinspection PhpUnused - method is called by website service
     */
    public function getSubscriptionsRenewalForUsers(array $usersIds): array
    {
        $subscriptions = $this->subscriptionRepository->getSubscriptionsForUsers($usersIds);

        $subs = [];

        foreach ($subscriptions as $subscription) {

            $brand = $subscription->getBrand();
            $userId = $subscription->getUser()->getId();

            if (!isset($subs[$brand])) {
                $subs[$brand] = [];
            }

            if (isset($subs[$brand][$userId])) {
                $otherUserSubscription = $subs[$brand][$userId];
                $subs[$brand][$userId] = $this->selectUserSubscription($subscription, $otherUserSubscription);
            } else {
                $subs[$brand][$userId] = $subscription;
            }
        }

        $subscriptions = null;
        $subscriptionsRenewals = [];

        foreach ($subs as $brandSubs) {
            foreach ($brandSubs as $subscription) {
                $subscriptionsRenewals[] = $this->getSubscriptionRenewal($subscription);
            }
        }

        return $subscriptionsRenewals;
    }

    /**
     * Selects a subscription to be displayed to an admin/support user
     *
     * @param Subscription $subscriptionOne
     * @param Subscription $subscriptionTwo
     *
     * @return Subscription
     */
    public function selectUserSubscription(
        Subscription $subscriptionOne,
        Subscription $subscriptionTwo
    ): Subscription
    {
        $subscriptionOneState = $subscriptionOne->getState();
        $subscriptionTwoState = $subscriptionTwo->getState();

        if ($subscriptionOneState == Subscription::STATE_ACTIVE) {
            return $subscriptionOne;
        }

        if ($subscriptionTwoState == Subscription::STATE_ACTIVE) {
            return $subscriptionTwo;
        }

        if (
            $subscriptionOneState == Subscription::STATE_SUSPENDED
            && (
                $subscriptionOneState != Subscription::STATE_SUSPENDED
                || $subscriptionOne->getPaidUntil() > $subscriptionTwo->getPaidUntil()
            )
        ) {
            return $subscriptionOne;
        }

        if ($subscriptionTwoState == Subscription::STATE_SUSPENDED) {
            return $subscriptionTwo;
        }

        return $subscriptionOne->getPaidUntil() > $subscriptionTwo->getPaidUntil()
            ? $subscriptionOne : $subscriptionTwo;
    }

    /**
     * Creates & populates a SubscriptionRenewal object with data from a subscription
     *
     * @param Subscription $subscription
     *
     * @return SubscriptionRenewal
     */
    public function getSubscriptionRenewal(Subscription $subscription): SubscriptionRenewal
    {
        $brand = $subscription->getBrand();
        $userId = $subscription->getUser()->getId();
        $idString = $brand . $userId;

        $subscriptionRenewal = new SubscriptionRenewal(md5($idString));

        $subscriptionRenewal->setUserId($userId);
        $subscriptionRenewal->setBrand($brand);
        $subscriptionRenewal->setSubscriptionId($subscription->getId());
        $subscriptionRenewal->setSubscriptionType(
            $subscription->getIntervalType() . '_' . $subscription->getIntervalCount()
        );
        $subscriptionRenewal->setSubscriptionState($subscription->getState());
        $subscriptionRenewal->setNextRenewalDue($this->getSubscriptionRenewalDueDate($subscription));

        return $subscriptionRenewal;
    }

    /**
     * Computes the date/time the subscription is due to renew
     *
     * @param Subscription $subscription
     *
     * @return DateTimeInterface|null
     */
    public function getSubscriptionRenewalDueDate(Subscription $subscription): ?DateTimeInterface
    {
        $subscriptionState = $subscription->getState();
        $renewalAttempt = $subscription->getRenewalAttempt() ?? 0;

        if (
            $subscriptionState == Subscription::STATE_STOPPED
            || $subscriptionState == Subscription::STATE_CANCELED
            || ($subscriptionState == Subscription::STATE_SUSPENDED && $renewalAttempt > 5)
        ) {
            return null;
        }

        $config = config('ecommerce.subscriptions_renew_cycles');

        $config[0] = 0; // initial renewal

        /** @var $renewalDueDate Carbon */
        $renewalDueDate = $subscription->getPaidUntil()
            ->copy();

        return isset($config[$renewalAttempt]) ?
            $renewalDueDate->addHours($config[$renewalAttempt]) : null;
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

        // check for payment plan if the user have already paid all the cycles, or is malformed
        if (($subscription->getType() == config('ecommerce.type_payment_plan'))) {

            if(is_null($subscription->getTotalCyclesDue())){
                $msg = $subscription->getId() . " is a payment plan that does not have total_cycles_due value set.";
                error_log($msg);
                throw new Exception($msg);
            }

            if ((int)$subscription->getTotalCyclesPaid() >= (int)$subscription->getTotalCyclesDue()) {
                throw new Exception("Cannot renew completed payment plan (total_cycles_paid is equal to or greater than total_cycles_due)");
            }
        }

        /** @var $paymentMethod PaymentMethod */
        $paymentMethod = $subscription->getPaymentMethod();

        // if there is no payment method cancel the subscription
        if (empty($paymentMethod)) {
            $subscription->setRenewalAttempt(($subscription->getRenewalAttempt() ?? 0) + 1);

            $subscription->setIsActive(false);
            $subscription->setCanceledOn(Carbon::now());
            $subscription->setUpdatedAt(Carbon::now());

            $this->entityManager->flush();

            event(new SubscriptionUpdated($oldSubscription, $subscription));

            event(
                new SubscriptionEvent($subscription->getId(), 'cancelled')
            );

            throw new Exception(
                "Subscription with ID: " . $subscription->getId() . " does not have an attached payment method."
            );
        }

        $payment = new Payment();
        $payment->setAttemptNumber($subscription->getRenewalAttempt());

        /** @var $address Address */
        if (!empty($paymentMethod->getBillingAddress())) {
            $address = $paymentMethod->getBillingAddress()->toStructure();
        } else {
            $address = new Address();
        }

        $currency = $paymentMethod->getCurrency();

        // all taxes for recurring payments are now calculated on the fly
        $subscriptionPricePerPayment = round($subscription->getTotalPrice(), 2);

        // if its a payment plan, remove the finance charge per payment so it doesn't get taxed
        if (!empty($subscription->getOrder()) && $subscription->getType() == Subscription::TYPE_PAYMENT_PLAN) {
            $taxes = $this->taxService->getTaxesDueTotal(
                $subscriptionPricePerPayment -
                round(($subscription->getOrder()->getFinanceDue() / $subscription->getTotalCyclesDue()), 2),
                0,
                $address
            );
        } else {
            $taxes = $this->taxService->getTaxesDueTotal(
                $subscriptionPricePerPayment,
                0,
                $address
            );
        }

        $chargePrice = $this->currencyService->convertFromBase(
            round($subscriptionPricePerPayment + $taxes, 2),
            $currency
        );

        $exceptionToThrow = null;

        if ($paymentMethod->getMethodType() == PaymentMethod::TYPE_CREDIT_CARD) {

            try {

                /** @var $method CreditCard */
                $method = $paymentMethod->getCreditCard();

                if (empty($method->getExternalCustomerId()) && !empty($subscription->getUser())) {
                    $tempPurchaser = new Purchaser();
                    $tempPurchaser->setId(
                        $subscription->getUser()
                            ->getId()
                    );
                    $tempPurchaser->setBrand(
                        $paymentMethod->getCreditCard()
                            ->getPaymentGatewayName()
                    );
                    $tempPurchaser->setEmail(
                        $subscription->getUser()
                            ->getEmail()
                    );
                    $tempPurchaser->setType(Purchaser::USER_TYPE);

                    $customer =
                        $this->paymentService->getStripeCustomer(
                            $tempPurchaser,
                            $paymentMethod->getCreditCard()
                                ->getPaymentGatewayName()
                        );
                } else {
                    $customer = $this->stripePaymentGateway->getCustomer(
                        $method->getPaymentGatewayName(),
                        $method->getExternalCustomerId()
                    );
                }

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
                $payment->setStatus(Payment::STATUS_PAID);
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
                $payment->setStatus(Payment::STATUS_FAILED);
                $payment->setMessage($exception->getMessage());
                $payment->setCurrency($currency);
                $payment->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency] ?? 0);

                $subscription->setFailedPayment($payment);

                $exceptionToThrow = $exception;
            }
        } elseif ($paymentMethod->getMethodType() == PaymentMethod::TYPE_PAYPAL) {

            try {

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
                $payment->setStatus(Payment::STATUS_PAID);
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
                $payment->setStatus(Payment::STATUS_FAILED);
                $payment->setMessage($exception->getMessage());
                $payment->setCurrency($currency);
                $payment->setConversionRate(config('ecommerce.default_currency_conversion_rates')[$currency] ?? 0);

                $subscription->setFailedPayment($payment);

                $exceptionToThrow = $exception;
            }
        } else {
            $payment->setTotalPaid(0);
            $payment->setExternalProvider('unknown');
            $payment->setExternalId($transactionId ?? null);
            $payment->setGatewayName(
                $paymentMethod->getMethod()
                    ->getPaymentGatewayName()
            );
            $payment->setStatus(Payment::STATUS_FAILED);
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

        // save to the order as well if its a payment plan
        if ($subscription->getType() == Subscription::TYPE_PAYMENT_PLAN &&
            empty($subscription->getProduct()) &&
            !empty($subscription->getOrder())) {

            // link the payment to the order
            $orderPayment = new OrderPayment();
            $orderPayment->setOrder($subscription->getOrder());
            $orderPayment->setPayment($payment);

            $this->entityManager->persist($orderPayment);

            // update the order total paid
            $subscription->getOrder()->setTotalPaid(
                $subscription->getOrder()->getTotalPaid() + $payment->getTotalPaid()
            );

            $this->entityManager->persist($subscription->getOrder());
        }

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
            $subscription->setRenewalAttempt(0);
            $subscription->setPaidUntil(
                $nextBillDate ? $nextBillDate->startOfDay() :
                    Carbon::now()
                        ->addMonths(1)
            );
            $subscription->setFailedPayment(null);
            $subscription->setUpdatedAt(Carbon::now());

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

            event(new SubscriptionRenewed($subscription, $payment));
            event(new SubscriptionUpdated($oldSubscription, $subscription));
            event(new SubscriptionEvent($subscription->getId(), 'renewed'));

        } else {

            $subscription->setRenewalAttempt(($subscription->getRenewalAttempt() ?? 0) + 1);

            $subscription->setIsActive(false);
            $subscription->setUpdatedAt(Carbon::now());

            $this->entityManager->flush();

            event(new SubscriptionUpdated($oldSubscription, $subscription));

            event(
                new SubscriptionEvent($subscription->getId(), 'deactivated')
            );

            event(new SubscriptionRenewFailed($subscription, $oldSubscription, $payment));

            throw PaymentFailedException::createFromException(
                $exceptionToThrow,
                $payment
            );
        }

        $this->userProductService->updateSubscriptionProducts($subscription);

        return $payment;
    }

    /**
     * @param $userId
     * @return bool
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function cancelUserSubscriptions($userId)
    {
        $activeSubscriptions = $this->subscriptionRepository->getActiveSubscriptionsByUserId($userId);

        foreach ($activeSubscriptions as $subscription)
        {
            $subscription->setIsActive(false);
            $subscription->setCanceledOn(Carbon::now());
            $subscription->setUpdatedAt(Carbon::now());

            $this->entityManager->flush();
        }

        return true;
    }
}