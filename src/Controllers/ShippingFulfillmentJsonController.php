<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Requests\OrderFulfilledRequest;
use Railroad\Ecommerce\Requests\OrderFulfillmentDeleteRequest;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;

class ShippingFulfillmentJsonController extends BaseController
{
    /**
     * @var \Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository
     */
    private $orderItemFulfillmentRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * ShippingFulfillmentJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository $orderItemFulfillmentRepository
     * @param \Railroad\Permissions\Services\PermissionService                $permissionService
     */
    public function __construct(
        OrderItemFulfillmentRepository $orderItemFulfillmentRepository,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
        $this->permissionService              = $permissionService;
    }

    /** Pull shipping fulfillments. If the status it's set on the requests the results are filtered by status.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.fulfillments');

        $fulfillments = $this->orderItemFulfillmentRepository->query()
            ->whereIn('status', (array) $request->get('status', [ConfigService::$fulfillmentStatusPending, ConfigService::$fulfillmentStatusFulfilled]))
            ->limit($request->get('limit', 10))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 10))
            ->orderBy($request->get('order_by_column', 'created_on'), $request->get('order_by_direction', 'desc'))
            ->get();

        $fulfillmentsCount = $this->orderItemFulfillmentRepository->query()
            ->whereIn('status', (array) $request->get('status', [ConfigService::$fulfillmentStatusPending, ConfigService::$fulfillmentStatusFulfilled]))
            ->count();

        return reply()->json($fulfillments, [
            'totalResults' => $fulfillmentsCount
        ]);
    }

    /** Fulfilled order or order item. If the order_item_id it's set on the request only the order item it's fulfilled,
     * otherwise entire order it's fulfilled.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function markShippingFulfilled(OrderFulfilledRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'fulfilled.fulfillment');

        //if the order item id it's set on the request we mark only the order item fulfilled,
        // otherwise the entire order it's fulfilled
        $fulfillmentsQuery = $this->orderItemFulfillmentRepository->query()
            ->where('order_id', $request->get('order_id'));
        if($request->has('order_item_id'))
        {
            $fulfillmentsQuery = $fulfillmentsQuery->where('order_item_id', $request->get('order_item_id'));
        }

        $updated = $fulfillmentsQuery
            ->update(
                [
                    'status'          => ConfigService::$fulfillmentStatusFulfilled,
                    'company'         => $request->get('shipping_company'),
                    'tracking_number' => $request->get('tracking_number'),
                    'fulfilled_on'    => Carbon::now()->toDateTimeString()
                ]);

        throw_if(
            ($updated === 0),
            new NotFoundException('Fulfilled failed.')
        );

        return reply()->json(null, [
            'code' => 201
        ]);
    }

    /** Delete order or order item fulfillment.
     *
     * @param \Railroad\Ecommerce\Requests\OrderFulfillmentDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(OrderFulfillmentDeleteRequest $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'delete.fulfillment');

        //if the order item id it's set on the request we delete only order item fulfillment,
        // otherwise the entire order fulfillment it's deleted
        $fulfillmentsQuery = $this->orderItemFulfillmentRepository->query()
            ->where('order_id', $request->get('order_id'))
            ->where('status', ConfigService::$fulfillmentStatusPending);
        if($request->has('order_item_id'))
        {
            $fulfillmentsQuery = $fulfillmentsQuery->where('order_item_id', $request->get('order_item_id'));
        }

        $fulfillmentsQuery->delete();

        return reply()->json(null, [
            'code' => 204
        ]);
    }
}