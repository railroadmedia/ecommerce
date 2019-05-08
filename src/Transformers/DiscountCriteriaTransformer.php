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

        if ($discountCriteria->getProduct()) {
            // product relation is nullable
            $this->defaultIncludes[] = 'product';
        }

        return [
            'id' => $discountCriteria->getId(),
            'name' => $discountCriteria->getName(),
            'type' => $discountCriteria->getType(),
            'min' => $discountCriteria->getMin(),
            'max' => $discountCriteria->getMax(),
            'created_at' => $discountCriteria->getCreatedAt() ?
                                $discountCriteria->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $discountCriteria->getUpdatedAt() ?
                                $discountCriteria->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeProduct(DiscountCriteria $discountCriteria)
    {
        if ($discountCriteria->getProduct() instanceof Proxy) {
            return $this->item(
                $discountCriteria->getProduct(),
                new EntityReferenceTransformer(),
                'product'
            );
        } else {
            return $this->item(
                $discountCriteria->getProduct(),
                new ProductTransformer(),
                'product'
            );
        }
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
