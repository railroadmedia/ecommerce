<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\OrderItemRepository;

class OrderItemService
{
    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * OrderItemService constructor.
     * @param OrderItemRepository $orderItemRepository
     */
    public function __construct(OrderItemRepository $orderItemRepository)
    {
        $this->orderItemRepository = $orderItemRepository;
    }

    /** Call the method that save the order item in the database and return an array with the new created item
     * @param integer $orderId
     * @param integer $productId
     * @param integer $quantity
     * @param number $initialPrice
     * @param number $discount
     * @param number $tax
     * @param number $shippingCosts
     * @param number $totalPrice
     * @return array
     */
    public function store($orderId, $productId, $quantity, $initialPrice, $discount, $tax, $shippingCosts, $totalPrice)
    {
        $orderItemId = $this->orderItemRepository->create([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'initial_price' => $initialPrice,
            'discount' => $discount,
            'tax' => $tax,
            'shipping_costs' => $shippingCosts,
            'total_price' => $totalPrice,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        return $this->getById($orderItemId);
    }

    /** Get an order item based on the id
     * @param integer $id
     * @return array|null
     */
    public function getById($id)
    {
        return $this->orderItemRepository->getById($id);
    }

}