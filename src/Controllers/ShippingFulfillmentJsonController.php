<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Requests\OrderFulfilledRequest;
use Railroad\Ecommerce\Requests\OrderFulfillmentDeleteRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;

class ShippingFulfillmentJsonController extends BaseController
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $orderItemFulfillmentRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * ShippingFulfillmentJsonController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param PermissionService $permissionService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->entityManager = $entityManager;

        $this->orderItemFulfillmentRepository = $this->entityManager
            ->getRepository(OrderItemFulfillment::class);

        $this->permissionService = $permissionService;
    }

    /**
     * Pull shipping fulfillments. If the status it's set on the requests the results are filtered by status.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.fulfillments');

        $statuses = (array) $request->get(
            'status',
            [
                ConfigService::$fulfillmentStatusPending,
                ConfigService::$fulfillmentStatusFulfilled
            ]
        );

        $first = ($request->get('page', 1) - 1) * $request->get('limit', 10);
        $orderBy = $request->get('order_by_column', 'created_at');
        if (
            strpos($orderBy, '_') !== false
            || strpos($orderBy, '-') !== false
        ) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = 'oif.' . $orderBy;

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->orderItemFulfillmentRepository->createQueryBuilder('oif');

        $fulfillments = $qb
            ->where($qb->expr()->in('oif.status', ':statuses'))
            ->setMaxResults($request->get('limit', 10))
            ->setFirstResult($first)
            ->orderBy($orderBy, $request->get('order_by_direction', 'desc'))
            ->setParameter('statuses', $statuses)
            ->getQuery()
            ->getResult();

        return ResponseService::fulfillment($fulfillments);
    }

    /**
     * Fulfilled order or order item. If the order_item_id it's set on the request only the order item it's fulfilled,
     * otherwise entire order it's fulfilled.
     *
     * @param OrderFulfilledRequest $request
     * @return JsonResponse
     */
    public function markShippingFulfilled(OrderFulfilledRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'fulfilled.fulfillment');

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->orderItemFulfillmentRepository->createQueryBuilder('oif');

        $qb
            ->where($qb->expr()->eq('IDENTITY(oif.order)', ':orderId'))
            ->setParameter('orderId', $request->get('order_id'));

        if ($request->has('order_item_id')) {
            $qb
                ->andWhere($qb->expr()->eq('IDENTITY(oif.orderItem)', ':orderItemId'))
                ->setParameter('orderItemId', $request->get('order_item_id'));
        }

        $fulfillments = $qb->getQuery()->getResult();

        $found = false;

        foreach ($fulfillments as $fulfillment) {

            $found = true;

            $fulfillment
                ->setStatus(ConfigService::$fulfillmentStatusFulfilled)
                ->setCompany($request->get('shipping_company'))
                ->setTrackingNumber($request->get('tracking_number'))
                ->setFulfilledOn(Carbon::now());
        }

        $this->entityManager->flush();

        throw_if(
            !$found,
            new NotFoundException('Fulfilled failed.')
        );

        return ResponseService::empty(201);
    }

    /**
     * Delete order or order item fulfillment.
     *
     * @param \Railroad\Ecommerce\Requests\OrderFulfillmentDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(OrderFulfillmentDeleteRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.fulfillment');

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->orderItemFulfillmentRepository->createQueryBuilder('oif');

        $qb
            ->where($qb->expr()->eq('IDENTITY(oif.order)', ':orderId'))
            ->andWhere($qb->expr()->eq('oif.status', ':status'))
            ->setParameter('orderId', $request->get('order_id'))
            ->setParameter('status', ConfigService::$fulfillmentStatusPending);

        if ($request->has('order_item_id')) {
            $qb
                ->andWhere($qb->expr()->eq('IDENTITY(oif.orderItem)', ':orderItemId'))
                ->setParameter('orderItemId', $request->get('order_item_id'));
        }

        $fulfillments = $qb->getQuery()->getResult();

        foreach ($fulfillments as $fulfillment) {
            $this->entityManager->remove($fulfillment);
        }

        $this->entityManager->flush();

        return ResponseService::empty(204);
    }
}
