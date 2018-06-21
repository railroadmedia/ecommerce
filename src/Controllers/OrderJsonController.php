<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Permissions\Services\PermissionService;

class OrderJsonController extends Controller
{
    /**
     * @var \Railroad\Ecommerce\Repositories\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Railroad\Permissions\Services\PermissionService
     */
    private $permissionService;

    /**
     * OrderJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\OrderRepository $orderRepository
     * @param \Railroad\Permissions\Services\PermissionService $permissionService
     */
    public function __construct(OrderRepository $orderRepository, PermissionService $permissionService)
    {
        $this->orderRepository   = $orderRepository;
        $this->permissionService = $permissionService;
    }

    /** Soft delete order
     * @param int $orderId
     * @return \Railroad\Ecommerce\Responses\JsonResponse
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

        return new JsonResponse(null, 204);
    }
}