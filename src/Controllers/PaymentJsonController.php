<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\UserPaymentMethods;
// use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
// use Railroad\Ecommerce\Repositories\OrderRepository;
// use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
// use Railroad\Ecommerce\Repositories\PaymentRepository;
// use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
// use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Requests\PaymentCreateRequest;
use Railroad\Ecommerce\Requests\PaymentIndexRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Location\Services\LocationService;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;

class PaymentJsonController extends BaseController
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PaymentRepository
     */
    // private $paymentRepository;

    /**
     * @var \Railroad\Location\Services\LocationService
     */
    private $locationService;

    /**
     * @var PaymentMethodRepository
     */
    // private $paymentMethodRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var UserPaymentMethodsRepository
     */
    private $userPaymentMethodRepository;

    /**
     * @var SubscriptionRepository
     */
    // private $subscriptionRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    // private $subscriptionPaymentRepository;

    /**
     * @var OrderRepository
     */
    // private $orderRepository;

    /**
     * @var OrderPaymentRepository
     */
    // private $orderPaymentRepository;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var \Railroad\Ecommerce\Gateways\StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Gateways\PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    //subscription interval type
    const INTERVAL_TYPE_DAILY = 'day';
    const INTERVAL_TYPE_MONTHLY = 'month';
    const INTERVAL_TYPE_YEARLY = 'year';

    /**
     * PaymentJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\PaymentRepository $paymentRepository
     * @param \Railroad\Location\Services\LocationService $locationService
     * @param \Railroad\Ecommerce\Repositories\PaymentMethodRepository $paymentMethodRepository
     * @param \Railroad\Permissions\Services\PermissionService $permissionService
     * @param \Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository $userPaymentMethodsRepository
     * @param \Railroad\Ecommerce\Repositories\SubscriptionRepository $subscriptionRepository
     * @param \Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param \Railroad\Ecommerce\Repositories\OrderRepository $orderRepository
     * @param \Railroad\Ecommerce\Repositories\OrderPaymentRepository $orderPaymentRepository
     * @param \Railroad\Ecommerce\Services\CurrencyService $currencyService
     * @param \Railroad\Ecommerce\Gateways\StripePaymentGateway $stripePaymentGateway
     * @param \Railroad\Ecommerce\Gateways\PayPalPaymentGateway $payPalPaymentGateway
     */
    public function __construct(
        CurrencyService $currencyService,
        EntityManager $entityManager,
        // PaymentRepository $paymentRepository,
        LocationService $locationService,
        // PaymentMethodRepository $paymentMethodRepository,
        PermissionService $permissionService,
        // UserPaymentMethodsRepository $userPaymentMethodsRepository,
        // SubscriptionRepository $subscriptionRepository,
        // SubscriptionPaymentRepository $subscriptionPaymentRepository,
        // OrderRepository $orderRepository,
        // OrderPaymentRepository $orderPaymentRepository,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway
    )
    {
        parent::__construct();

        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;

        $this->userPaymentMethodsRepository = $this->entityManager
                                    ->getRepository(UserPaymentMethods::class);

        // $this->paymentRepository = $paymentRepository;
        $this->locationService = $locationService;
        // $this->paymentMethodRepository = $paymentMethodRepository;
        $this->permissionService = $permissionService;
        // $this->userPaymentMethodRepository = $userPaymentMethodsRepository;
        // $this->subscriptionRepository = $subscriptionRepository;
        // $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        // $this->orderRepository = $orderRepository;
        // $this->orderPaymentRepository = $orderPaymentRepository;
        $this->currencyService = $currencyService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
    }

    /**
     * @param Request $request
     * @throws NotAllowedException
     */
    public function index(PaymentIndexRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'list.payment');

        $query = $this->paymentRepository->query()
            ->limit($request->get('limit', 100))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 100))
            ->orderBy($request->get('order_by_column', 'created_on'), $request->get('order_by_direction', 'desc'));

        if (!empty($request->get('order_id'))) {
            $query
                ->select(ConfigService::$tablePayment . '.*')
                ->join(
                    ConfigService::$tableOrderPayment, ConfigService::$tableOrderPayment . '.payment_id',
                    '=',
                    ConfigService::$tablePayment . '.id'
                )
                ->where(ConfigService::$tableOrderPayment . '.order_id', $request->get('order_id'));
        }

        if (!empty($request->get('subscription_id'))) {
            $query
                ->select(ConfigService::$tablePayment . '.*')
                ->join(
                    ConfigService::$tableSubscriptionPayment, ConfigService::$tableSubscriptionPayment . '.payment_id',
                    '=',
                    ConfigService::$tablePayment . '.id'
                )
                ->where(ConfigService::$tableSubscriptionPayment . '.subscription_id', $request->get('subscription_id'));
        }

        return $query->get();

    }

    /**
     * Call the method that save a new payment and create the links with subscription or order if it's necessary.
     * Return a JsonResponse with the new created payment record, in JSON format
     *
     * @param PaymentCreateRequest $request
     *
     * @return JsonResponse
     *
     * @throws NotAllowedException
     * @throws \Throwable
     */
    public function store(PaymentCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.payment');

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userPaymentMethodsRepository->createQueryBuilder('p');

        $userPaymentMethod = $qb
            ->where($qb->expr()->eq('IDENTITY(p.paymentMethod)', ':id'))
            ->setParameter('id', $request->input('data.relationships.paymentMethod.data.id'))
            ->getQuery()
            ->getOneOrNullResult();

        $user = $userPaymentMethod->getUser();

        // if the logged in user it's not admin => can pay only with own payment method
        throw_if(
            (
                (!$this->permissionService->can(auth()->id(), 'create.payment.method')) &&
                (auth()->id() != ($user->getId() ?? 0))
            ),
            new NotAllowedException('This action is unauthorized.')
        );

        // if the currency not exist on the request and the payment it's manual,
        // get the currency with Location package, based on ip address
        $currency = $request->input('data.attributes.currency')
            ?? $this->currencyService->get();
        $gateway = $request->input('data.attributes.payment_gateway');

        $paymentMethod = $userPaymentMethod->getPaymentMethod();

        $paymentType = $request->input('data.relationships.subscription.data.id') ?
                            ConfigService::$renewalPaymentType :
                            ConfigService::$orderPaymentType;

        $paymentPrice = $request->input('data.attributes.due');

        $payment = new Payment();

        if (is_null($paymentMethod)) {

            $payment
                ->setTotalDue($paymentPrice)
                ->setTotalPaid($paymentPrice)
                ->setExternalProvider(ConfigService::$manualPaymentType)
                ->setStatus(true)
                ->setCurrency($currency)
                ->setCreatedAt(Carbon::now());

        } else if ($paymentMethod->getMethodType() == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
            try {

                /**
                 * @var $method \Railroad\Ecommerce\Entities\CreditCard
                 */
                $method = $this->entityManager
                                ->getRepository(CreditCard::class)
                                ->find($paymentMethod->getMethodId());

                $customer = $this->stripePaymentGateway->getCustomer(
                    $gateway,
                    $method->getExternalId()
                );

                $card = $this->stripePaymentGateway->getCard(
                    $customer,
                    $method->getExternalId(),
                    $gateway
                );

                $charge = $this->stripePaymentGateway->chargeCustomerCard(
                    $gateway,
                    $paymentPrice,
                    $currency,
                    $card,
                    $customer,
                    ''
                );

                $payment
                    ->setTotalPaid($paymentPrice)
                    ->setExternalProvider('stripe')
                    ->setExternalId($charge->id)
                    ->setStatus(($charge->status == 'succeeded') ? '1' : '0')
                    ->setMessage('')
                    ->setCurrency($charge->currency);

            } catch (\Exception $ex) {
                // todo - review and add exception handling
                throw $ex;
            }
        } else if ($paymentMethod->getMethodType() == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
            try {

                $transactionId = $this->payPalPaymentGateway->chargeBillingAgreement(
                    $gateway,
                    $paymentPrice,
                    $currency,
                    $method->getExternalId(),
                    ''
                );

                $payment
                    ->setTotalPaid($paymentPrice)
                    ->setExternalProvider('paypal')
                    ->setExternalId($transactionId)
                    ->setStatus('1')
                    ->setMessage('')
                    ->setCurrency($currency);

            } catch (\Exception $ex) {
                // todo - review and add exception handling
                throw $ex;
            }
        }

        $payment
            ->setTotalDue($paymentPrice)
            ->setType($paymentType)
            ->setConversionRate($this->currencyService->getRate($currency))
            ->setPaymentMethod($paymentMethod)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        if ($request->input('data.relationships.subscription.data.id')) {

            /**
             * @var $method \Railroad\Ecommerce\Entities\Subscription
             */
            $subscription = $this->entityManager
                            ->getRepository(Subscription::class)
                            ->find($request->input('data.relationships.subscription.data.id'));

            $subscription
                ->setTotalCyclesPaid($subscription->getTotalCyclesPaid() + 1)
                ->setPaidUntil(
                    $this->calculateNextBillDate(
                        $subscription['interval_type'],
                        $subscription['interval_count']
                    )
                );

            $subscriptionPayment = new SubscriptionPayment();

            $subscriptionPayment
                ->setSubscription($subscription)
                ->setPayment($payment);

            $this->entityManager->persist($subscriptionPayment);
        }

        // Save the link between order and payment and save the paid amount on order row
        if ($request->input('data.relationships.order.data.id')) {

            /**
             * @var $order \Railroad\Ecommerce\Entities\Order
             */
            $order = $this->entityManager
                            ->getRepository(Order::class)
                            ->find($request->input('data.relationships.order.data.id'));

            /*
            todo - DEVE-27 & DEVE-30
            pull all & sum (order associated payments * payment conversion rate)
            */

            $orderPayment = new OrderPayment();

            $orderPayment
                ->setOrder($order)
                ->setPayment($payment)
                ->setCreatedAt(Carbon::now());

            $this->entityManager->persist($orderPayment);
        }

        $this->entityManager->flush();

        return ResponseService::payment($payment);
    }

    /** Calculate next bill date for subscription
     *
     * @param $orderItem
     * @return \Carbon\Carbon
     */
    private function calculateNextBillDate($intervalType, $intervalCount)
    {
        $paidUntil = Carbon::now();

        switch ($intervalType) {
            case self::INTERVAL_TYPE_DAILY:
                $paidUntil->addDays($intervalCount);
                break;
            case self::INTERVAL_TYPE_MONTHLY:
                $paidUntil->addMonths($intervalCount);
                break;
            case self::INTERVAL_TYPE_YEARLY:
                $paidUntil->addYears($intervalCount);
                break;
        }

        return $paidUntil;
    }

    /** Soft delete a payment
     * @param $paymentId
     * @return JsonResponse
     */
    public function delete($paymentId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.payment');

        $payment = $this->paymentRepository->read($paymentId);
        throw_if(
            is_null($payment),
            new NotFoundException('Delete failed, payment not found with id: ' . $paymentId)
        );

        $this->paymentRepository->delete($paymentId);

        return reply()->json(null, [
            'code' => 204
        ]);
    }
}