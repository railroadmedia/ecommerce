<?php

namespace Railroad\Ecommerce\Events\PaymentMethods;

use Railroad\Ecommerce\Entities\PaymentMethod;

class PaymentMethodUpdated
{
    /**
     * @var PaymentMethod
     */
    private $newPaymentMethod;
    /**
     * @var PaymentMethod
     */
    private $oldPaymentMethod;

    /**
     * @param PaymentMethod $newPaymentMethod
     * @param PaymentMethod $oldPaymentMethod
     */
    public function __construct(PaymentMethod $newPaymentMethod, PaymentMethod $oldPaymentMethod)
    {
        $this->newPaymentMethod = $newPaymentMethod;
        $this->oldPaymentMethod = $oldPaymentMethod;
    }
}