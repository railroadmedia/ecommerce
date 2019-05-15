<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Discount;

class DiscountTransformer extends TransformerAbstract
{
    public function transform(Discount $discount)
    {
        if (!empty($discount->getProduct())) {
            // product relation is nullable
            $this->defaultIncludes[] = 'product';
        }
        if ($discount->getDiscountCriterias()) {
            // product relation is nullable
            $this->defaultIncludes[] = 'discountCriterias';
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
            'note' => $discount->getNote(),
            'created_at' => $discount->getCreatedAt() ?
                $discount->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $discount->getUpdatedAt() ?
                $discount->getUpdatedAt()
                    ->toDateTimeString() : null,
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
        }
        else {
            return $this->item(
                $discount->getProduct(),
                new ProductTransformer(),
                'product'
            );
        }
    }

    public function includeDiscountCriterias(Discount $discount)
    {
        if ($discount->getDiscountCriterias()
                ->first() instanceof Proxy) {
            return $this->collection(
                $discount->getDiscountCriterias(),
                new EntityReferenceTransformer(),
                'discountCriterias'
            );
        }
        else {
            return $this->collection(
                $discount->getDiscountCriterias(),
                new DiscountCriteriaTransformer(true),
                'discountCriterias'
            );
        }
    }
}
