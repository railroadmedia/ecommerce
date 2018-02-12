<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;

class ProductRepository extends RepositoryBase
{
    /**
     * @return Builder
     */
    public function query()
    {
        return parent::connection()->table(ConfigService::$tableProduct);
    }

    public function getActiveProductFromSku($productSku)
    {
        return $this->query()->where([
            'sku' => $productSku,
            'active' => 1
        ])->get()->first();
    }
}