<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Events\SubscriptionEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionRenewed;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Ecommerce\Services\UserProductService;
use Throwable;

class PaypalWebhookController extends Controller
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * PaypalWebhookController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param PaymentRepository $paymentRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        PaymentRepository $paymentRepository,
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService
    )
    {
        $this->entityManager = $entityManager;
        $this->paymentRepository = $paymentRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
    }

    /**
     *
     * @param Request $request
     *
     * @return mixed
     *
     * @throws Throwable
     */
    public function process(Request $request)
    {
        if (
            $request->get('txn_type') == 'recurring_payment'
            && $request->get('payment_status') == 'Completed'
            && !empty($request->get('recurring_payment_id'))
            && !empty($request->get('txn_id'))
            && !empty($request->get('payment_gross'))
        ) {
            $subscription = $this->subscriptionRepository->findOneBy(
                ['paypalRecurringProfileId' => $request->get('recurring_payment_id')]
            );

            $payment = $this->paymentRepository->findOneBy(
                ['externalId' => $request->get('txn_id')]
            );

            if (empty($payment) && !empty($subscription)) {
                $payment = new Payment();

                $payment->setTotalDue($request->get('payment_gross'));
                $payment->setTotalPaid($request->get('payment_gross'));
                $payment->setTotalRefunded(0);
                $payment->setAttemptNumber(0);
                $payment->setConversionRate(1);
                $payment->setType(Payment::TYPE_PAYPAL_SUBSCRIPTION_RENEWAL);
                $payment->setExternalId($request->get('txn_id'));
                $payment->setExternalProvider(Payment::EXTERNAL_PROVIDER_PAYPAL);
                $payment->setGatewayName(config('ecommerce.brand'));
                $payment->setStatus(Payment::STATUS_PAID);
                $payment->setCurrency($request->get('mc_currency', ''));
                $payment->setCreatedAt(Carbon::createFromFormat('H:i:s M d, Y e', $request->get('payment_date')));

                $this->entityManager->persist($payment);

                $subscriptionPayment = new SubscriptionPayment();

                $subscriptionPayment->setSubscription($subscription);
                $subscriptionPayment->setPayment($payment);

                $this->entityManager->persist($subscriptionPayment);

                $nextBillDate = null;

                switch ($subscription->getIntervalType()) {
                    case config('ecommerce.interval_type_monthly'):
                        $nextBillDate =
                            $payment->getCreatedAt()
                                ->copy()
                                ->addMonths($subscription->getIntervalCount());
                        break;

                    case config('ecommerce.interval_type_yearly'):
                        $nextBillDate =
                            $payment->getCreatedAt()
                                ->copy()
                                ->addYears($subscription->getIntervalCount());
                        break;

                    case config('ecommerce.interval_type_daily'):
                        $nextBillDate =
                            $payment->getCreatedAt()
                                ->copy()
                                ->addDays($subscription->getIntervalCount());
                        break;

                    default:
                        error_log(
                            sprintf(
                                'Failed processing subscription interval type: "%s", for subscription with id: %s',
                                $subscription->getIntervalType(),
                                $subscription->getId()
                            )
                        );
                        return ResponseService::empty(200);
                        break;
                }

                $oldSubscription = clone($subscription);

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

                $this->entityManager->flush();

                event(new SubscriptionRenewed($subscription, $payment));
                event(new SubscriptionUpdated($oldSubscription, $subscription));
                event(new SubscriptionEvent($subscription->getId(), 'renewed'));

                $this->userProductService->updateSubscriptionProducts($subscription);
            }
        }

        return ResponseService::empty(200);
    }

}
