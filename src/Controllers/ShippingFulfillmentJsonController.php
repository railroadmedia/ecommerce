<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Responses\JsonPaginatedResponse;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;

class ShippingFulfillmentJsonController extends Controller
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
        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
        $this->permissionService              = $permissionService;
    }

    /** Pull shipping fulfillments. If the status it's set on the requests the results are filtered by status.
     * @param \Illuminate\Http\Request $request
     * @return \Railroad\Ecommerce\Responses\JsonPaginatedResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.fulfillments');

        $fulfillments = $this->orderItemFulfillmentRepository->query()
            ->whereIn('status', (array)$request->get('status',[ConfigService::$fulfillmentStatusPending, ConfigService::$fulfillmentStatusFulfilled]))
            ->limit($request->get('limit', 10))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 10))
            ->orderBy($request->get('order_by_column', 'created_on'), $request->get('order_by_direction', 'desc'))
            ->get();

        $fulfillmentsCount = $this->orderItemFulfillmentRepository->query()
            ->whereIn('status', (array($request->get('status',[ConfigService::$fulfillmentStatusPending, ConfigService::$fulfillmentStatusFulfilled]))))
            ->count();

        return new JsonPaginatedResponse(
            $fulfillments,
            $fulfillmentsCount,
            200);
    }
}