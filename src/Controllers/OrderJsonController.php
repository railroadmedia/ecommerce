<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\RefundRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\OrderUpdateRequest;
use Railroad\Ecommerce\Services\JsonApiHydrator;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class OrderJsonController extends Controller
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var RefundRepository
     */
    private $refundRepository;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * OrderJsonController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param OrderRepository $orderRepository
     * @param PaymentRepository $paymentRepository
     * @param PermissionService $permissionService
     * @param RefundRepository $refundRepository
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        OrderRepository $orderRepository,
        PaymentRepository $paymentRepository,
        PermissionService $permissionService,
        RefundRepository $refundRepository,
        SubscriptionRepository $subscriptionRepository
    )
    {
        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->permissionService = $permissionService;
        $this->refundRepository = $refundRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Pull orders between two dates
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.orders');

        $ordersAndBuilder = $this->orderRepository->indexByRequest($request);

        return ResponseService::order($ordersAndBuilder->getResults(), $ordersAndBuilder->getQueryBuilder())
            ->respond(200);
    }

    /**
     * Show order
     *
     * @param int $orderId
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function show($orderId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.orders');

        $order = $this->orderRepository->getDecoratedOrder($orderId);

        throw_if(
            is_null($order),
            new NotFoundException('Pull failed, order not found with id: ' . $orderId)
        );

        $payments = $this->paymentRepository->getPaymentsByOrder($order);

        $refunds = [];

        if (count($payments)) {
            $refunds = $this->refundRepository->getPaymentsRefunds($payments);
        }

        $subscriptionItems = collect($this->subscriptionRepository->getOrderSubscriptions($order));

        $subscriptions = $subscriptionItems->filter(
                function (Subscription $subscription) {
                    return $subscription->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION;
                }
            )
            ->all();

        $paymentPlans = $subscriptionItems->filter(
                function (Subscription $subscription) {
                    return $subscription->getType() == config('ecommerce.type_payment_plan');
                }
            )
            ->all();

        return ResponseService::decoratedOrder(
            $order,
            $payments,
            $refunds,
            $subscriptions,
            $paymentPlans
        );
    }

    /**
     * Soft delete order
     *
     * @param int $orderId
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function delete($orderId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.order');

        $order = $this->orderRepository->find($orderId);

        throw_if(
            is_null($order),
            new NotFoundException(
                'Delete failed, order not found with id: ' . $orderId
            )
        );

        $order->setDeletedOn(Carbon::now());

        $this->entityManager->flush();

        return ResponseService::empty(204);
    }

    /**
     * Update order if exists in db and the user have rights to update it.
     * Return updated data in JSON format
     *
     * @param int $orderId
     * @param OrderUpdateRequest $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function update($orderId, OrderUpdateRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'edit.order');

        $order = $this->orderRepository->find($orderId);

        throw_if(
            is_null($order),
            new NotFoundException(
                'Update failed, order not found with id: ' . $orderId
            )
        );

        $this->jsonApiHydrator->hydrate($order, $request->onlyAllowed());

        if ($request->input('included')) {

            $orderItems = [];

            foreach ($order->getOrderItems() as $orderItem) {
                $orderItems[$orderItem->getId()] = $orderItem;
            }

            foreach ($request->input('included') as $data) {
                if ($data['type'] == 'orderItem') {
                    $orderItem = $orderItems[$data['id']];

                    $this->jsonApiHydrator->hydrate($orderItem, $data);
                }
            }
        }

        $this->entityManager->flush();

        return ResponseService::order($order);
    }
}
