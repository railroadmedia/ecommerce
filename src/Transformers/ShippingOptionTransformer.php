<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\ShippingOption;

class ShippingOptionTransformer extends TransformerAbstract
{
    /**
     * @param ShippingOption $shippingOption
     *
     * @return array
     */
    public function transform(ShippingOption $shippingOption)
    {
        $this->defaultIncludes[] = 'shippingCostsWeightRange';

        return [
            'id' => $shippingOption->getId(),
            'country' => $shippingOption->getCountry(),
            'active' => $shippingOption->getActive(),
            'priority' => $shippingOption->getPriority(),
            'created_at' => $shippingOption->getCreatedAt() ?
                $shippingOption->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $shippingOption->getUpdatedAt() ?
                $shippingOption->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    /**
     * @param ShippingOption $shippingOption
     *
     * @return Collection
     */
    public function includeShippingCostsWeightRange(
        ShippingOption $shippingOption
    )
    {
        return $this->collection(
            $shippingOption->getShippingCostsWeightRanges(),
            new ShippingCostsWeightRangeTransformer(),
            'shippingCostsWeightRange'
        );
    }
}
