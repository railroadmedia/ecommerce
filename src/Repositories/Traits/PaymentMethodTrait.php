<?php

namespace Railroad\Ecommerce\Repositories\Traits;

trait PaymentMethodTrait
{
    /**
     * @param integer $productId
     * @return array
     */
    public function deleteByPaymentMethodId($paymentMethodId)
    {
        return $this->query()->where('payment_method_id', $paymentMethodId)->delete() > 0;
    }
}