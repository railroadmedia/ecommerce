<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\ShippingCostsWeightRange;

class ShippingCostsWeightRangeTransformer extends TransformerAbstract
{
    /**
     * @param ShippingCostsWeightRange $shippingCost
     *
     * @return array
     */
    public function transform(ShippingCostsWeightRange $shippingCost)
    {
        return [
            'id' => $shippingCost->getId(),
            'min' => $shippingCost->getMin(),
            'max' => $shippingCost->getMax(),
            'price' => $shippingCost->getPrice(),
            'created_at' => $shippingCost->getCreatedAt() ?
                $shippingCost->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $shippingCost->getUpdatedAt() ?
                $shippingCost->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }
}
