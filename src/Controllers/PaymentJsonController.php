<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Factories\GatewayFactory;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository;
use Railroad\Ecommerce\Requests\PaymentCreateRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\PaymentService;
use Railroad\Location\Services\LocationService;
use Railroad\Permissions\Services\PermissionService;

class PaymentJsonController extends Controller
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var \Railroad\Location\Services\LocationService
     */
    private $locationService;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var GatewayFactory
     */
    private $gatewayFactory;

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
    private $subscriptionRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    private $subscriptionPaymentRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderPaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var CurrencyService
     */
    private $currencyService;

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
     * @param \Railroad\Ecommerce\Factories\GatewayFactory $gatewayFactory
     * @param \Railroad\Permissions\Services\PermissionService $permissionService
     * @param \Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository $userPaymentMethodsRepository
     * @param \Railroad\Ecommerce\Repositories\SubscriptionRepository $subscriptionRepository
     * @param \Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param \Railroad\Ecommerce\Repositories\OrderRepository $orderRepository
     * @param \Railroad\Ecommerce\Repositories\OrderPaymentRepository $orderPaymentRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        LocationService $locationService,
        PaymentMethodRepository $paymentMethodRepository,
        GatewayFactory $gatewayFactory,
        PermissionService $permissionService,
        UserPaymentMethodsRepository $userPaymentMethodsRepository,
        SubscriptionRepository $subscriptionRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        OrderRepository $orderRepository,
        OrderPaymentRepository $orderPaymentRepository,
        CurrencyService $currencyService
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->locationService = $locationService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->gatewayFactory = $gatewayFactory;
        $this->permissionService = $permissionService;
        $this->userPaymentMethodRepository = $userPaymentMethodsRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->orderRepository = $orderRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->currencyService = $currencyService;
    }

    /** Call the method that save a new payment and create the links with subscription or order if it's necessary.
     * Return a JsonResponse with the new created payment record, in JSON format
     *
     * @param PaymentCreateRequest $request
     * @return JsonResponse
     */
    public function store(PaymentCreateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'create.payment');

        $userPaymentMethod = $this->userPaymentMethodRepository->query()->where(
            [
                'payment_method_id' => $request->get('payment_method_id'),
            ]
        )->first();

        // if the logged in user it's not admin => can pay only with own payment method
        throw_if(
            ((!$this->permissionService->is(auth()->id(), 'admin')) && (auth()->id() != $userPaymentMethod['user_id'])),
            new NotAllowedException('This action is unauthorized.')
        );

        // if the currency not exist on the request and the payment it's manual,
        // get the currency with Location package, based on ip address
        $currency = $request->get('currency') ?? $this->currencyService->get();

        $paymentMethod = $this->paymentMethodRepository->read($request->get('payment_method_id'));
        $gateway = $this->gatewayFactory->create($paymentMethod['method_type']);

        //charge payment
        $paymentData = $gateway->chargePayment(
            $request->get('due'),
            $request->get('paid'),
            $paymentMethod,
            ($currency ?? $paymentMethod['currency'])
        );

        //charge payment failed => throw proper exception
        if (!$paymentData['status']) {
            throw new NotFoundException('Payment failed.');
        }

        $paymentData['type'] =
            ($request->has('subscription_id')) ? (PaymentService::RENEWAL_PAYMENT_TYPE) :
                (PaymentService::ORDER_PAYMENT_TYPE);

        //save payment data in DB
        $payment = $this->paymentRepository->create($paymentData);

        if ($request->has('subscription_id')) {
            $subscriptionId = $request->get('subscription_id');
            $subscription = $this->subscriptionRepository->read($subscriptionId);

            //update subscription total cycles paid and next bill date
            $this->subscriptionRepository->update(
                $subscriptionId,
                [
                    'total_cycles_paid' => $subscription['total_cycles_paid'] + 1,
                    'paid_until' => $this->calculateNextBillDate(
                        $subscription['interval_type'],
                        $subscription['interval_count']
                    ),
                ]
            );
            $this->subscriptionPaymentRepository->create(
                [
                    'subscription_id' => $subscriptionId,
                    'payment_id' => $payment['id'],
                    'created_on' => Carbon::now()->toDateTimeString(),
                ]
            );
        }

        // Save the link between order and payment and save the paid amount on order row
        if ($request->has('order_id')) {
            $this->orderPaymentRepository->create(
                [
                    'order_id' => $request->get('order_id'),
                    'payment_id' => $payment['id'],
                    'created_on' => Carbon::now()->toDateTimeString(),
                ]
            );

            $this->orderRepository->update(
                $request->get('order_id'),
                [
                    'paid' => $request->get('paid'),
                ]
            );
        }

        return new JsonResponse($payment, 200);
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
}