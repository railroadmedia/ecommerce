<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\OrderUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;


class OrderJsonController extends BaseController
{
    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderPaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * @var \Railroad\Permissions\Services\RefundRepository
     */
    private $refundRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * OrderJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\AddressRepository $addressRepository
     * @param \Railroad\Ecommerce\Repositories\OrderPaymentRepository $orderPaymentRepository
     * @param \Railroad\Ecommerce\Repositories\OrderRepository $orderRepository
     * @param \Railroad\Ecommerce\Repositories\PaymentRepository $paymentRepository
     * @param \Railroad\Permissions\Services\PermissionService $permissionService
     * @param \Railroad\Permissions\Services\RefundRepository $refundRepository
     * @param \Railroad\Permissions\Services\SubscriptionRepository $subscriptionRepository
     */
    public function __construct(
        AddressRepository $addressRepository,
        OrderPaymentRepository $orderPaymentRepository,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        PermissionService $permissionService,
        RefundRepository $refundRepository,
        SubscriptionRepository $subscriptionRepository
    ) {
        parent::__construct();

        $this->addressRepository = $addressRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->orderRepository   = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->permissionService = $permissionService;
        $this->refundRepository = $refundRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /** Pull orders between two dates
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.orders');

        $orders = $this->orderRepository->query()
            ->whereIn('brand', $request->get('brands', [ConfigService::$brand]));

        if ($request->has('start-date')) {
            $startDate = Carbon::parse($request->get('start-date'));
        }


        if ($request->has('end-date')) {
            $endDate = Carbon::parse($request->get('end-date'));
        }

        if(isset($startDate) && isset($endDate)) {
            $orders->whereBetween('created_on', [$startDate, $endDate]);
        }

        if($request->has('user_id'))
        {
            $orders = $orders->where('user_id', $request->get('user_id'));
        }
        $orders = $orders->limit($request->get('limit', 100))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 100))
            ->orderBy($request->get('order_by_column', 'created_on'), $request->get('order_by_direction', 'desc'))
            ->get();

        $ordersCount = $this->orderRepository->query()
            ->whereIn('brand', $request->get('brands', [ConfigService::$brand]));
        if($request->has('user_id'))
        {
            $ordersCount = $ordersCount->where('user_id', $request->get('user_id'));
        }
        $ordersCount = $ordersCount->count();

        return reply()->json($orders, [
            'totalResults' => $ordersCount
        ]);
    }

    /** Show order
     *
     * @param int $orderId
     * @return JsonResponse
     */
    public function show($orderId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.orders');

        $order = $this->orderRepository->read($orderId);

        throw_if(
            is_null($order),
            new NotFoundException('Pull failed, order not found with id: ' . $orderId)
        );

        $order['items'] = array_values($order['items']);

        $order['addresses'] = $this->addressRepository
            ->query()
            ->whereIn(
                'id',
                [
                    $order['shipping_address_id'],
                    $order['billing_address_id']
                ]
            )
            ->get()
            ->all();

        $orderPayments = $this->orderPaymentRepository
            ->query()
            ->where('order_id', $orderId)
            ->get()
            ->pluck('payment_id')
            ->all();

        $order['payments'] = $this->paymentRepository
            ->query()
            ->whereIn('id', $orderPayments)
            ->get()
            ->all();

        $order['refunds'] = $this->refundRepository
            ->query()
            ->whereIn('payment_id', $orderPayments)
            ->get()
            ->all();

        $subscriptions = $this->subscriptionRepository
            ->query()
            ->where('order_id', $orderId)
            ->get();

        $order['subscriptions'] = $subscriptions
            ->filter(function($subscription, $key) {
                return $subscription->type == ConfigService::$typeSubscription;
            })
            ->all();

        $order['paymentPlans'] = $subscriptions
            ->filter(function($subscription, $key) {
                return $subscription->type == ConfigService::$paymentPlanType;
            })
            ->all();

        return reply()->json([$order]);
    }

    /** Soft delete order
     *
     * @param int $orderId
     * @return JsonResponse
     */
    public function delete($orderId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.order');

        $order = $this->orderRepository->read($orderId);
        throw_if(
            is_null($order),
            new NotFoundException('Delete failed, order not found with id: ' . $orderId)
        );

        $this->orderRepository->delete($orderId);

        return reply()->json(null, [
            'code' => 204
        ]);
    }

    /** Update order if exists in db and the user have rights to update it.
     * Return updated data in JSON format
     * @param  int                                               $orderId
     * @param \Railroad\Ecommerce\Requests\OrderUpdateRequest $request
     * @return JsonResponse
     */
    public function update($orderId, OrderUpdateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.order');

        $order = $this->orderRepository->read($orderId);

        throw_if(is_null($order),
            new NotFoundException('Update failed, order not found with id: ' . $orderId)
        );

        //update order with the data sent on the request
        $updatedOrder = $this->orderRepository->update(
            $orderId,
            array_merge(
                $request->only(
                    [
                        'due',
                        'tax',
                        'shipping_costs',
                        'paid',
                        'shipping_address_id',
                        'billing_address_id'
                    ]
                ), [
                'updated_on' => Carbon::now()->toDateTimeString()
            ])
        );
        return reply()->json($updatedOrder, [
            'code' => 201
        ]);
    }
}
