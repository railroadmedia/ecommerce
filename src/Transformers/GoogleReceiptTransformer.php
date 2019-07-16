<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\GoogleReceipt;

class GoogleReceiptTransformer extends TransformerAbstract
{
    public function transform(GoogleReceipt $googleReceipt)
    {
        return [
            'id' => $googleReceipt->getId(),
            'purchase_token' => $googleReceipt->getPurchaseToken(),
            'package_name' => $googleReceipt->getPackageName(),
            'product_id' => $googleReceipt->getProductId(),
            'email' => $googleReceipt->getEmail(),
            'brand' => $googleReceipt->getBrand(),
            'valid' => $googleReceipt->getValid(),
            'validation_error' => $googleReceipt->getValidationError(),
            'created_at' => $googleReceipt->getCreatedAt() ?
                $googleReceipt->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $googleReceipt->getUpdatedAt() ?
                $googleReceipt->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }
}
