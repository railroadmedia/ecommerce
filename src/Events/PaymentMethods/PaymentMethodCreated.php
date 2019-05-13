<?php

namespace Railroad\Ecommerce\Events\PaymentMethods;

use Railroad\Ecommerce\Entities\PaymentMethod;

class PaymentMethodCreated
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

    /**
     * @return PaymentMethod
     */
    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }
}