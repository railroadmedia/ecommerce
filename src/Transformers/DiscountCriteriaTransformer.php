<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\DiscountCriteria;

class DiscountCriteriaTransformer extends TransformerAbstract
{
    /**
     * @var bool
     */
    private $disableDiscountInclude;

    /**
     * DiscountCriteriaTransformer constructor.
     * @param bool $disableDiscountInclude
     */
    public function __construct($disableDiscountInclude = false)
    {
        $this->disableDiscountInclude = $disableDiscountInclude;
    }

    public function transform(DiscountCriteria $discountCriteria)
    {
        if ($discountCriteria->getDiscount() && !$this->disableDiscountInclude) {
            $this->defaultIncludes[] = 'discount';
        }

        if (!empty($discountCriteria->getProducts())) {
            // product relation is nullable
            $this->defaultIncludes[] = 'products';
        }

        return [
            'id' => $discountCriteria->getId(),
            'name' => $discountCriteria->getName(),
            'type' => $discountCriteria->getType(),
            'products_relation_type' => $discountCriteria->getProductsRelationType(),
            'min' => $discountCriteria->getMin(),
            'max' => $discountCriteria->getMax(),
            'created_at' => $discountCriteria->getCreatedAt() ?
                $discountCriteria->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $discountCriteria->getUpdatedAt() ?
                $discountCriteria->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    public function includeProducts(DiscountCriteria $discountCriteria)
    {
        return $this->collection(
            $discountCriteria->getProducts(),
            new ProductTransformer(),
            'product'
        );
    }

    public function includeDiscount(DiscountCriteria $discountCriteria)
    {
        return $this->item(
            $discountCriteria->getDiscount(),
            new DiscountTransformer(),
            'discount'
        );
    }
}
