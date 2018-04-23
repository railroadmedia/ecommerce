<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;

class OrderItemFulfillmentService
{
    /**
     * @var OrderItemFulfillmentRepository
     */
    private $orderItemFulfillmentRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * OrderItemFulfillmentService constructor.
     * @param $orderItemFulfillmentRepository
     */
    public function __construct(OrderItemFulfillmentRepository $orderItemFulfillmentRepository,
                                OrderRepository $orderRepository)
    {
        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
        $this->orderRepository = $orderRepository;
    }

    /** For an order store in the database rows for all the physical order's products with status 'pending'.
     * If the order not exists return null.
     * @param integer $orderId
     * @return bool|null
     */
    public function store($orderId)
    {
        $order = $this->orderRepository->getOrderWithItemsById($orderId);

        if (is_null($order)) {
            return null;
        }

        foreach ($order as $orderItem) {
            if ($orderItem['is_physical'] == 1) {
                $this->orderItemFulfillmentRepository->create([
                    'order_id' => $orderItem['id'],
                    'order_item_id' => $orderItem['order_item_id'],
                    'status' => 'pending',
                    'created_on' => Carbon::now()->toDateTimeString()
                ]);
            }
        }
        return true;
    }

}