<?php

namespace Railroad\Ecommerce\Repositories\Traits;

trait ProductTrait
{
    /**
     * @param integer $productId
     * @return array
     */
    public function getByProductId($productId)
    {
        return $this->query()->where('product_id', $productId)->get()->toArray();
    }
}