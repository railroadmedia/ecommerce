<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotAllowedException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
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
use Railroad\Ecommerce\Services\PaymentMethodService;
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

    /**
     * @var \Railroad\Ecommerce\Gateways\StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var \Railroad\Ecommerce\Gateways\PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    //subscription interval type
    const INTERVAL_TYPE_DAILY   = 'day';
    const INTERVAL_TYPE_MONTHLY = 'month';
    const INTERVAL_TYPE_YEARLY  = 'year';

    /**
     * PaymentJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\PaymentRepository             $paymentRepository
     * @param \Railroad\Location\Services\LocationService                    $locationService
     * @param \Railroad\Ecommerce\Repositories\PaymentMethodRepository       $paymentMethodRepository
     * @param \Railroad\Permissions\Services\PermissionService               $permissionService
     * @param \Railroad\Ecommerce\Repositories\UserPaymentMethodsRepository  $userPaymentMethodsRepository
     * @param \Railroad\Ecommerce\Repositories\SubscriptionRepository        $subscriptionRepository
     * @param \Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param \Railroad\Ecommerce\Repositories\OrderRepository               $orderRepository
     * @param \Railroad\Ecommerce\Repositories\OrderPaymentRepository        $orderPaymentRepository
     * @param \Railroad\Ecommerce\Services\CurrencyService                   $currencyService
     * @param \Railroad\Ecommerce\Gateways\StripePaymentGateway              $stripePaymentGateway
     * @param \Railroad\Ecommerce\Gateways\PayPalPaymentGateway              $payPalPaymentGateway
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        LocationService $locationService,
        PaymentMethodRepository $paymentMethodRepository,
        PermissionService $permissionService,
        UserPaymentMethodsRepository $userPaymentMethodsRepository,
        SubscriptionRepository $subscriptionRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        OrderRepository $orderRepository,
        OrderPaymentRepository $orderPaymentRepository,
        CurrencyService $currencyService,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway
    ) {
        $this->paymentRepository                = $paymentRepository;
        $this->locationService                  = $locationService;
        $this->paymentMethodRepository          = $paymentMethodRepository;
        $this->permissionService                = $permissionService;
        $this->userPaymentMethodRepository      = $userPaymentMethodsRepository;
        $this->subscriptionRepository           = $subscriptionRepository;
        $this->subscriptionPaymentRepository    = $subscriptionPaymentRepository;
        $this->orderRepository                  = $orderRepository;
        $this->orderPaymentRepository           = $orderPaymentRepository;
        $this->currencyService                  = $currencyService;
        $this->stripePaymentGateway             = $stripePaymentGateway;
        $this->payPalPaymentGateway             = $payPalPaymentGateway;
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

        $paymentType =
            ($request->has('subscription_id')) ? (PaymentService::RENEWAL_PAYMENT_TYPE) :
                (PaymentService::ORDER_PAYMENT_TYPE);

        //manual payment
        if(is_null($paymentMethod))
        {
            $paymentData = [
                'due'               => $request->get('due'),
                'paid'              => $request->get('due'),
                'external_provider' => PaymentService::MANUAL_PAYMENT_TYPE,
                'status'            => true,
                'payment_method_id' => null,
                'currency'          => $currency,
                'created_on'        => Carbon::now()->toDateTimeString()
            ];
        }
        else if($paymentMethod['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE)
        {
            $customer = $this->stripePaymentGateway->getCustomer(
                $request->get('payment_gateway'),
                $paymentMethod['method']['external_customer_id']
            );
            $card     = $this->stripePaymentGateway->getCard(
                $customer,
                $paymentMethod['method']['external_id'],
                $request->get('payment_gateway')
            );

            $charge      = $this->stripePaymentGateway->chargeCustomerCard(
                $request->get('payment_gateway'),
                $request->get('due'),
                ($currency ?? $paymentMethod['currency']),
                $card,
                $customer,
                ''
            );
            $paymentData = [
                'paid'              => $charge->amount,
                'external_provider' => 'stripe',
                'external_id'       => $charge->id,
                'status'            => ($charge->status == 'succeeded') ? 1 : 0,
                'message'           => '',
                'currency'          => $charge->currency,
            ];
        }
        else if($paymentMethod['method_type'] == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE)
        {
            $transactionId = $this->payPalPaymentGateway->chargeBillingAgreement(
                $request->get('payment_gateway'),
                $request->get('due'),
                ($currency ?? $paymentMethod['currency']),
                $paymentMethod['method']['agreement_id'],
                ''
            );

            $paymentData = [
                'paid'              => $request->get('due'),
                'external_provider' => 'paypal',
                'external_id'       => $transactionId,
                'status'            => 1,
                'message'           => '',
                'currency'          => $currency ?? $paymentMethod['currency'],
            ];
        }

        //save payment data in DB
        $payment = $this->paymentRepository->create(
            array_merge(
                $paymentData, [
                    'due'               => $request->get('due'),
                    'type'              => $paymentType,
                    'payment_method_id' => $paymentMethod['id'],
                    'created_on'        => Carbon::now()->toDateTimeString()
                ]
            )
        );

        if($request->has('subscription_id'))
        {
            $subscriptionId = $request->get('subscription_id');
            $subscription   = $this->subscriptionRepository->read($subscriptionId);

            //update subscription total cycles paid and next bill date
            $this->subscriptionRepository->update(
                $subscriptionId,
                [
                    'total_cycles_paid' => $subscription['total_cycles_paid'] + 1,
                    'paid_until'        => $this->calculateNextBillDate(
                        $subscription['interval_type'],
                        $subscription['interval_count']
                    ),
                ]
            );
            $this->subscriptionPaymentRepository->create(
                [
                    'subscription_id' => $subscriptionId,
                    'payment_id'      => $payment['id'],
                    'created_on'      => Carbon::now()->toDateTimeString(),
                ]
            );
        }

        // Save the link between order and payment and save the paid amount on order row
        if($request->has('order_id'))
        {
            $this->orderPaymentRepository->create(
                [
                    'order_id'   => $request->get('order_id'),
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

        switch($intervalType)
        {
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