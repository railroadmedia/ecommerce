<?php

namespace Railroad\Ecommerce\Events;

use Railroad\Ecommerce\Entities\Order;

class OrderEvent
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * Create a new event instance.
     *
     * @param $id
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
