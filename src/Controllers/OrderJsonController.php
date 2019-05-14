<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\OrderPayment;
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
     * @var EntityRepository
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

    // todo: refactor query logic to repository

    /**
     * Pull orders between two dates
     *
     * @param Request $request
     *
     * @return Fractal
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.orders');

        $orderBy = $request->get('order_by_column', 'created_at');
        if (strpos($orderBy, '_') !== false || strpos($orderBy, '-') !== false) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = 'o.' . $orderBy;
        $first = ($request->get('page', 1) - 1) * $request->get('limit', 100);

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->orderRepository->createQueryBuilder('o');

        $qb->select(['o', 'oi', 'ba', 'sa', 'p'])
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'p')
            ->leftJoin('o.billingAddress', 'ba')
            ->leftJoin('o.shippingAddress', 'sa')
            ->where(
                $qb->expr()
                    ->in('o.brand', ':brands')
            )
            ->setParameter(
                'brands',
                $request->get('brands', [config('ecommerce.available_brands')])
            )
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'))
            ->setMaxResults($request->get('limit', 15))
            ->setFirstResult($first);

        if ($request->has('start-date')) {
            $startDate = Carbon::parse($request->get('start-date'));
        }

        if ($request->has('end-date')) {
            $endDate = Carbon::parse($request->get('end-date'));
        }

        if (isset($startDate) && isset($endDate)) {
            $qb->andWhere(
                    $qb->expr()
                        ->between('o.createdAt', ':startDate', ':endDate')
                )
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        if ($request->has('user_id')) {
            $qb->andWhere('o.user = :userId')
                ->setParameter('userId', $request->get('user_id'));
        }

        $orders =
            $qb->getQuery()
                ->getResult();

        return ResponseService::order($orders, $qb);
    }

    // todo: refactor query logic to repository

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

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->orderRepository->createQueryBuilder('o');

        $qb->select(['o', 'oi', 'ba', 'sa', 'p'])
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'p')
            ->leftJoin('o.billingAddress', 'ba')
            ->leftJoin('o.shippingAddress', 'sa')
            ->where(
                $qb->expr()
                    ->in('o.id', ':orderId')
            )
            ->setParameter('orderId', $orderId);

        $order =
            $qb->getQuery()
                ->getOneOrNullResult();

        throw_if(
            is_null($order),
            new NotFoundException('Pull failed, order not found with id: ' . $orderId)
        );

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->paymentRepository->createQueryBuilder('p');

        $qb->select(['p'])
            ->join(
                OrderPayment::class,
                'op',
                Join::WITH,
                $qb->expr()
                    ->eq(true, true)
            )
            ->join('op.payment', 'py')
            ->where(
                $qb->expr()
                    ->eq('op.order', ':order')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('p.id', 'py.id')
            )
            ->setParameter('order', $order);

        $payments =
            $qb->getQuery()
                ->getResult();

        $refunds = [];

        if (count($payments)) {
            /**
             * @var $qb QueryBuilder
             */
            $qb = $this->refundRepository->createQueryBuilder('r');

            $qb->select(['r'])
                ->where(
                    $qb->expr()
                        ->in('r.payment', ':payments')
                )
                ->setParameter('payments', $payments);

            $refunds =
                $qb->getQuery()
                    ->getResult();
        }

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->subscriptionRepository->createQueryBuilder('s');

        $qb->select(['s'])
            ->where(
                $qb->expr()
                    ->eq('s.order', ':order')
            )
            ->setParameter('order', $order);

        $subscriptionItems =
            collect(
                $qb->getQuery()
                    ->getResult()
            );

        $subscriptions = $subscriptionItems->filter(
                function (Subscription $subscription) {
                    return $subscription->getType() == Product::TYPE_SUBSCRIPTION;
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

        $this->entityManager->flush();

        return ResponseService::order($order);
    }
}
