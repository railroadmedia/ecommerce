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

    public function getByPaymentGatewayId($paymentGatewayId)
    {
        return $this->query()->where('payment_gateway_id', $paymentGatewayId)->get()->toArray();
    }
}