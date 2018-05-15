<?php

namespace Railroad\Ecommerce\Repositories\QueryBuilders;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\ProductService;

class ProductQueryBuilder extends QueryBuilder
{

    /**
     * @return $this
     */
    public function restrictActive()
    {
        if (!ProductService::$pullInactiveProducts) {
            $this->where(ConfigService::$tableProduct . '.active', 1);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function restrictBrand()
    {
        $this->where(ConfigService::$tableProduct . '.brand', ConfigService::$brand);

        return $this;
    }
}