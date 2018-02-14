<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Repositories\QueryBuilders\ProductQueryBuilder;
use Railroad\Ecommerce\Services\ConfigService;

class ProductRepository extends RepositoryBase
{
    /**
     * @return Builder
     */
    public function query()
    {
        return (new ProductQueryBuilder(
            $this->connection(),
            $this->connection()->getQueryGrammar(),
            $this->connection()->getPostProcessor()
        ))
            ->from(ConfigService::$tableProduct);
    }

    /** Get the active products that meet the conditions
     * @param $conditions
     * @return mixed
     */
    public function getActiveProductsByConditions($conditions)
    {
        return $this->query()
            ->restrictBrand()
            ->restrictActive()
            ->where($conditions)
            ->get()
            ->toArray();
    }
}