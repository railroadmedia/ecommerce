<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\CreditCard;

class CreditCardTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = [];

    /**
     * @param CreditCard $creditCard
     *
     * @return array
     */
    public function transform(CreditCard $creditCard)
    {
        return [
            'id' => $creditCard->getId(),
            'fingerprint' => $creditCard->getFingerprint(),
            'last_four_digits' => sprintf('%04d', $creditCard->getLastFourDigits()),
            'cardholder_name' => $creditCard->getCardholderName(),
            'company_name' => $creditCard->getCompanyName(),
            'expiration_date' => $creditCard->getExpirationDate() ?
                $creditCard->getExpirationDate()
                    ->toDateTimeString() : null,
            'external_id' => $creditCard->getExternalId(),
            'external_customer_id' => $creditCard->getExternalCustomerId(),
            'payment_gateway_name' => $creditCard->getPaymentGatewayName(),
            'created_at' => $creditCard->getCreatedAt() ?
                $creditCard->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $creditCard->getUpdatedAt() ?
                $creditCard->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }
}
