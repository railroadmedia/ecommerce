<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Refund;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\OrderUpdateRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;

class OrderJsonController extends BaseController
{
    /**
     * @var EntityManager
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
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * OrderJsonController constructor.
     *
     * @param EntityManager $entityManager
     * @param JsonApiHydrator $jsonApiHydrator
     * @param PermissionService $permissionService
     */
    public function __construct(
        EntityManager $entityManager,
        JsonApiHydrator $jsonApiHydrator,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->orderRepository = $this->entityManager
                                    ->getRepository(Order::class);
        $this->permissionService = $permissionService;
    }

    /**
     * Pull orders between two dates
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.orders');

        $orderBy = $request->get('order_by_column', 'created_at');
        if (
            strpos($orderBy, '_') !== false
            || strpos($orderBy, '-') !== false
        ) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = 'o.' . $orderBy;
        $first = ($request->get('page', 1) - 1) * $request->get('limit', 100);

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->orderRepository->createQueryBuilder('o');

        $qb
            ->select(['o', 'oi', 'u', 'ba', 'sa'])
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('o.user', 'u')
            ->leftJoin('o.billingAddress', 'ba')
            ->leftJoin('o.shippingAddress', 'sa')
            ->where($qb->expr()->in('o.brand', ':brands'))
            ->setParameter(
                'brands',
                $request->get('brands', [ConfigService::$availableBrands])
            )
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'))
            ->setMaxResults($request->get('limit', 100))
            ->setFirstResult($first);

        if ($request->has('start-date')) {
            $startDate = Carbon::parse($request->get('start-date'));
        }


        if ($request->has('end-date')) {
            $endDate = Carbon::parse($request->get('end-date'));
        }

        if (isset($startDate) && isset($endDate)) {
            $qb
                ->andWhere($qb->expr()->between('o.createdAt', ':startDate', ':endDate'))
                ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate);
        }

        if ($request->has('user_id')) {
            $qb
                ->where('IDENTITY(o.user)', ':userId')
                ->setParameter('userId', $request->get('user_id'));
        }

        $orders = $qb->getQuery()->getResult();

        return ResponseService::order($orders, $qb);
    }

    /**
     * Show order
     *
     * @param int $orderId
     * @return JsonResponse
     */
    public function show($orderId)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.orders');

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->orderRepository->createQueryBuilder('o');

        $qb
            ->select(['o', 'oi', 'u', 'ba', 'sa'])
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('o.user', 'u')
            ->leftJoin('o.billingAddress', 'ba')
            ->leftJoin('o.shippingAddress', 'sa')
            ->where($qb->expr()->in('o.id', ':orderId'))
            ->setParameter('orderId', $orderId);

        $order = $qb->getQuery()->getOneOrNullResult();

        throw_if(
            is_null($order),
            new NotFoundException('Pull failed, order not found with id: ' . $orderId)
        );

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->entityManager
                        ->getRepository(Payment::class)
                        ->createQueryBuilder('p');

        $qb
            ->select(['p'])
            ->join(
                OrderPayment::class,
                'op',
                Join::WITH,
                $qb->expr()->eq(true, true)
            )
            ->join('op.payment', 'py')
            ->where($qb->expr()->eq('op.order', ':order'))
            ->andWhere($qb->expr()->eq('p.id', 'py.id'))
            ->setParameter('order', $order);

        $payments = $qb->getQuery()->getResult();

        $refunds = [];

        if (count($payments)) {
            /**
             * @var $qb \Doctrine\ORM\QueryBuilder
             */
            $qb = $this->entityManager
                            ->getRepository(Refund::class)
                            ->createQueryBuilder('r');

            $qb
                ->select(['r'])
                ->where($qb->expr()->in('r.payment', ':payments'))
                ->setParameter('payments', $payments);

            $refunds = $qb->getQuery()->getResult();
        }

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->entityManager
                        ->getRepository(Subscription::class)
                        ->createQueryBuilder('s');

        $qb
            ->select(['s'])
            ->where($qb->expr()->eq('s.order', ':order'))
            ->setParameter('order', $order);

        $subscriptionItems = collect($qb->getQuery()->getResult());

        $subscriptions = $subscriptionItems
            ->filter(function(Subscription $subscription, $key) {
                return $subscription->getType() == ConfigService::$typeSubscription;
            })
            ->all();

        $paymentPlans = $subscriptionItems
            ->filter(function(Subscription $subscription, $key) {
                return $subscription->getType() == ConfigService::$paymentPlanType;
            })
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
     * @return JsonResponse
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
     * @param \Railroad\Ecommerce\Requests\OrderUpdateRequest $request
     * @return JsonResponse
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
