<?php

namespace Railroad\Ecommerce\Events;

use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Payment;

class OrderEvent
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     * @param Payment $payment
     */
    public function __construct(Order $order, Payment $payment)
    {
        $this->order = $order;
        $this->payment = $payment;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return Payment
     */
    public function getPayment(): Payment
    {
        return $this->payment;
    }
}
