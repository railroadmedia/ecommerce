<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Requests\OrderFulfilledRequest;
use Railroad\Ecommerce\Requests\OrderFulfillmentDeleteRequest;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Throwable;

class ShippingFulfillmentJsonController extends Controller
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var OrderItemFulfillmentRepository
     */
    private $orderItemFulfillmentRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * ShippingFulfillmentJsonController constructor.
     *
     * @param EcommerceEntityManager $entityManager
     * @param OrderItemFulfillmentRepository $orderItemFulfillmentRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        EcommerceEntityManager $entityManager,
        OrderItemFulfillmentRepository $orderItemFulfillmentRepository,
        PermissionService $permissionService
    )
    {
        $this->entityManager = $entityManager;
        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
        $this->permissionService = $permissionService;
    }

    /**
     * Pull shipping fulfillments. If the status it's set on the requests the results are filtered by status.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.fulfillments');

        $fulfillmentsAndBuilder = $this->orderItemFulfillmentRepository->indexByRequest($request);

        return ResponseService::fulfillment($fulfillmentsAndBuilder->getResults(), $fulfillmentsAndBuilder->getQueryBuilder())
            ->respond(200);
    }

    /**
     * Fulfilled order or order item. If the order_item_id it's set on the request only the order item it's fulfilled,
     * otherwise entire order it's fulfilled.
     *
     * @param OrderFulfilledRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function markShippingFulfilled(OrderFulfilledRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'fulfilled.fulfillment');

        $fulfillments = $this->orderItemFulfillmentRepository->getByOrderAndOrderItem(
            $request->get('order_id'),
            $request->get('order_item_id')
        );

        $found = false;

        foreach ($fulfillments as $fulfillment) {
            /**
             * @var $fulfillment OrderItemFulfillment
             */
            $found = true;

            $fulfillment->setStatus(config('ecommerce.fulfillment_status_fulfilled'))
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
     * @param OrderFulfillmentDeleteRequest $request
     *
     * @return JsonResponse
     *
     * @throws Throwable
     */
    public function delete(OrderFulfillmentDeleteRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.fulfillment');

        $fulfillments = $this->orderItemFulfillmentRepository->getByOrderAndOrderItem(
            $request->get('order_id'),
            $request->get('order_item_id')
        );

        foreach ($fulfillments as $fulfillment) {
            $this->entityManager->remove($fulfillment);
        }

        $this->entityManager->flush();

        return ResponseService::empty(204);
    }
}
