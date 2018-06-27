<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Requests\OrderUpdateRequest;
use Railroad\Ecommerce\Responses\JsonPaginatedResponse;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Permissions\Services\PermissionService;

class OrderJsonController extends BaseController
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
        parent::__construct();

        $this->orderRepository   = $orderRepository;
        $this->permissionService = $permissionService;
    }

    /** Pull orders between two dates
     *
     * @param \Illuminate\Http\Request $request
     * @return \Railroad\Ecommerce\Responses\JsonPaginatedResponse
     */
    public function index(Request $request)
    {
        $this->permissionService->canOrThrow(auth()->id(), 'pull.orders');

        if($request->has('start-date'))
        {
            $startDate = Carbon::createFromFormat('m/d/y', $request->get('start-date'))
                ->timezone('America/Los_Angeles');
        }
        else
        {
            $startDate = Carbon::now()->subDay();
        }

        if($request->has('end-date'))
        {
            $endDate = Carbon::createFromFormat('m/d/y', $request->get('end-date'))
                ->timezone('America/Los_Angeles');
        }
        else
        {
            $endDate = Carbon::now();
        }

        $orders = $this->orderRepository->query()
            ->whereIn('brand', $request->get('brand', [ConfigService::$brand]))
            ->whereBetween('created_on', [$startDate, $endDate]);

        if($request->has('user_id'))
        {
            $orders = $orders->where('user_id', $request->get('user_id'));
        }
        $orders = $orders->limit($request->get('limit', 100))
            ->skip(($request->get('page', 1) - 1) * $request->get('limit', 100))
            ->orderBy($request->get('order_by_column', 'created_on'), $request->get('order_by_direction', 'desc'))
            ->get();

        $ordersCount = $this->orderRepository->query()
            ->whereIn('brand', $request->get('brand', [ConfigService::$brand]));
        if($request->has('user_id'))
        {
            $ordersCount = $ordersCount->where('user_id', $request->get('user_id'));
        }
        $ordersCount = $ordersCount->count();

        return new JsonPaginatedResponse(
            $orders,
            $ordersCount,
            200);
    }

    /** Soft delete order
     *
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

    /** Update order if exists in db and the user have rights to update it.
     * Return updated data in JSON format
     * @param  int                                               $orderId
     * @param \Railroad\Ecommerce\Requests\OrderUpdateRequest $request
     * @return \Railroad\Ecommerce\Responses\JsonResponse
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
                        'paid'
                    ]
                ), [
                'updated_on' => Carbon::now()->toDateTimeString()
            ])
        );
        return new JsonResponse($updatedOrder, 201);
    }
}