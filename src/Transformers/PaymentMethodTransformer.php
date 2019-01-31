<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\PaymentMethod;

class PaymentMethodTransformer extends TransformerAbstract
{
    public function transform(PaymentMethod $paymentMethod)
    {
        return [
            'id' => $paymentMethod->getId(),
            'method_id' => $paymentMethod->getMethodId(), // todo: review/update relation
            'method_type' => $paymentMethod->getMethodType(),
            'currency' => $paymentMethod->getCurrency(),
            'deleted_on' => $paymentMethod->getDeletedOn() ? $paymentMethod->getDeletedOn()->toDateTimeString() : null,
            'created_at' => $paymentMethod->getCreatedAt() ? $paymentMethod->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $paymentMethod->getUpdatedAt() ? $paymentMethod->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    // todo: add billing address relation
}
