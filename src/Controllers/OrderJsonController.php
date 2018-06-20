<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Responses\JsonResponse;

class OrderJsonController extends Controller
{
    /**
     * @var \Railroad\Ecommerce\Repositories\OrderRepository
     */
    private $orderRepository;

    /**
     * OrderJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /** Soft delete order
     * @param int $orderId
     * @return \Railroad\Ecommerce\Responses\JsonResponse
     */
    public function deleteOrder($orderId)
    {
        $order = $this->orderRepository->read($orderId);

        throw_if(
            is_null($order),
            new NotFoundException('Delete failed, order not found with id: ' . $orderId)
        );

        $this->orderRepository->delete($orderId);

        return new JsonResponse(null, 204);
    }
}