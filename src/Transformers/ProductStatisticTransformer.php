<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\ProductStatistic;

class ProductStatisticTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    public function transform(ProductStatistic $productStatistic)
    {
        return [
            'id' => $productStatistic->getId(),
            'sku' => $productStatistic->getSku(),
            'total_quantity_sold' => $productStatistic->getTotalQuantitySold(),
            'total_sales' => $productStatistic->getTotalSales(),
            'total_renewal_sales' => $productStatistic->getTotalRenewalSales(),
        ];
    }
}
