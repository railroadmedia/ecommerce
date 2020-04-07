<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\AppleReceipt;

class AppleReceiptTransformer extends TransformerAbstract
{
    /**
     * @param AppleReceipt $appleReceipt
     *
     * @return array
     */
    public function transform(AppleReceipt $appleReceipt)
    {
        return [
            'id' => $appleReceipt->getId(),
            'receipt' => $appleReceipt->getReceipt(),
            'email' => $appleReceipt->getEmail(),
            'brand' => $appleReceipt->getBrand(),
            'valid' => $appleReceipt->getValid(),
            'validation_error' => $appleReceipt->getValidationError(),
            'created_at' => $appleReceipt->getCreatedAt() ?
                $appleReceipt->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $appleReceipt->getUpdatedAt() ?
                $appleReceipt->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }
}
