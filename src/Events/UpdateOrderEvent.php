<?php

namespace Railroad\Ecommerce\Events;

use Railroad\Ecommerce\Entities\Order;

class UpdateOrderEvent
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }
}
