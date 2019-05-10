<?php

namespace Railroad\Ecommerce\Events\PaymentMethods;

use Railroad\Ecommerce\Entities\PaymentMethod;

class PaymentMethodDeleted
{
    /**
     * @var PaymentMethod
     */
    private $paymentMethod;

    /**
     * @param PaymentMethod $paymentMethod
     */
    public function __construct(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }
}