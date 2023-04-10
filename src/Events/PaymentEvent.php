<?php

namespace Railroad\Ecommerce\Events;

use Railroad\Ecommerce\Entities\Payment;

class PaymentEvent
{
    /**
     * @var Payment
     */
    protected $payment;

    /**
     * Create a new event instance.
     *
     * @param Payment $payment
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * @return Payment
     */
    public function getPayment(): Payment
    {
        return $this->payment;
    }

}
