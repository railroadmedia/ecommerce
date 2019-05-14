<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Refund;

class RefundTransformer extends TransformerAbstract
{
    public function transform(Refund $refund)
    {
        $this->defaultIncludes[] = 'payment';

        return [
            'id' => $refund->getId(),
            'payment_amount' => $refund->getPaymentAmount(),
            'refunded_amount' => $refund->getRefundedAmount(),
            'note' => $refund->getNote(),
            'external_id' => $refund->getExternalId(),
            'external_provider' => $refund->getExternalProvider(),
            'created_at' => $refund->getCreatedAt() ?
                $refund->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $refund->getUpdatedAt() ?
                $refund->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    public function includePayment(Refund $refund)
    {
        return $this->item(
            $refund->getPayment(),
            new PaymentTransformer(),
            'payment'
        );
    }
}
