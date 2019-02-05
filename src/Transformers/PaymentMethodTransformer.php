<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Transformers\AddressTransformer;
use Railroad\Ecommerce\Transformers\EntityReferenceTransformer;

class PaymentMethodTransformer extends TransformerAbstract
{
    public function transform(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->getBillingAddress()) {
            // user relation is nullable
            $this->defaultIncludes[] = 'billingAddress';
        }

        // ask if method should be included in response

        return [
            'id' => $paymentMethod->getId(),
            'method_id' => $paymentMethod->getMethodId(),
            'method_type' => $paymentMethod->getMethodType(),
            'currency' => $paymentMethod->getCurrency(),
            'deleted_on' => $paymentMethod->getDeletedOn() ? $paymentMethod->getDeletedOn()->toDateTimeString() : null,
            'created_at' => $paymentMethod->getCreatedAt() ? $paymentMethod->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $paymentMethod->getUpdatedAt() ? $paymentMethod->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeBillingAddress(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->getBillingAddress() instanceof Proxy) {
            return $this->item(
                $paymentMethod->getBillingAddress(),
                new EntityReferenceTransformer(),
                'address'
            );
        } else {
            return $this->item(
                $paymentMethod->getBillingAddress(),
                new AddressTransformer(),
                'address'
            );
        }
    }
}
