<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Discount;

class DiscountTransformer extends TransformerAbstract
{
    public function transform(Discount $discount)
    {
        if ($discount->getProduct()) {
            // product relation is nullable
            $this->defaultIncludes[] = 'product';
        }

        return [
            'id' => $discount->getId(),
            'name' => $discount->getName(),
            'description' => $discount->getDescription(),
            'type' => $discount->getType(),
            'amount' => $discount->getAmount(),
            'product_category' => $discount->getProductCategory(),
            'active' => $discount->getActive(),
            'visible' => $discount->getVisible(),
            'created_at' => $discount->getCreatedAt() ? $discount->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $discount->getUpdatedAt() ? $discount->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeProduct(Discount $discount)
    {
        if ($discount->getProduct() instanceof Proxy) {
            return $this->item(
                $discount->getProduct(),
                new EntityReferenceTransformer(),
                'product'
            );
        } else {
            return $this->item(
                $discount->getProduct(),
                new ProductTransformer(),
                'product'
            );
        }
    }
}
