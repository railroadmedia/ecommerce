<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Payment;

class PaymentTransformer extends TransformerAbstract
{
    public function transform(Payment $payment)
    {
        if ($payment->getPaymentMethod()) {
            $this->defaultIncludes[] = 'paymentMethod';
        }

        return [
            'id' => $payment->getId(),
            'total_due' => $payment->getTotalDue(),
            'total_paid' => $payment->getTotalPaid(),
            'total_refunded' => $payment->getTotalRefunded(),
            'conversion_rate' => $payment->getConversionRate(),
            'type' => $payment->getType(),
            'external_id' => $payment->getExternalId(),
            'external_provider' => $payment->getExternalProvider(),
            'gateway_name' => $payment->getGatewayName(),
            'status' => $payment->getStatus(),
            'message' => $payment->getMessage(),
            'currency' => $payment->getCurrency(),
            'note' => $payment->getNote(),
            'deleted_at' => $payment->getDeletedAt() ?
                $payment->getDeletedAt()
                    ->toDateTimeString() : null,
            'created_at' => $payment->getCreatedAt() ?
                $payment->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $payment->getUpdatedAt() ?
                $payment->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    public function includePaymentMethod(Payment $payment)
    {
        if ($payment->getPaymentMethod() instanceof Proxy) {
            return $this->item(
                $payment->getPaymentMethod(),
                new EntityReferenceTransformer(),
                'paymentMethod'
            );
        }
        else {
            return $this->item(
                $payment->getPaymentMethod(),
                new PaymentMethodTransformer(),
                'paymentMethod'
            );
        }
    }
}
