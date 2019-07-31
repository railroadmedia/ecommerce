<?php

namespace Railroad\Ecommerce\Events;

use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;

class MobileOrderEvent
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
     * @var Subscription
     */
    protected $subscription;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     * @param Payment|null $payment
     * @param Subscription|null $subscription
     */
    public function __construct(
        Order $order,
        ?Payment $payment,
        ?Subscription $subscription
    )
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->subscription = $subscription;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    /**
     * @return Subscription|null
     */
    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }
}
