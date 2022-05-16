<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;

class PaypalBillingAgreementTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    /**
     * @param PaypalBillingAgreement $paypalBillingAgreement
     *
     * @return array
     */
    public function transform(PaypalBillingAgreement $paypalBillingAgreement)
    {
        return [
            'id' => $paypalBillingAgreement->getId(),
            'external_id' => $paypalBillingAgreement->getExternalId(),
            'payment_gateway_name' => $paypalBillingAgreement->getPaymentGatewayName(),
            'created_at' => $paypalBillingAgreement->getCreatedAt() ?
                $paypalBillingAgreement->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $paypalBillingAgreement->getUpdatedAt() ?
                $paypalBillingAgreement->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }
}
