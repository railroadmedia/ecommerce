<?php

namespace Railroad\Ecommerce\Events;

class PaypalPaymentMethodEvent
{
    /**
     * @var int
     */
    protected $paymentMethodId;

    /**
     * Create a new event instance.
     *
     * @param int $paymentMethodId
     */
    public function __construct($paymentMethodId)
    {
        $this->paymentMethodId = $paymentMethodId;
    }

    /**
     * @return int
     */
    public function getPaymentMethodId()
    {
        return $this->paymentMethodId;
    }
}
