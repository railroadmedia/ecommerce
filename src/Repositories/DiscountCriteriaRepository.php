<?php

namespace Railroad\Ecommerce\Repositories;


use Railroad\Ecommerce\Repositories\Traits\ProductTrait;
use Railroad\Ecommerce\Services\ConfigService;

class DiscountCriteriaRepository extends RepositoryBase
{
    use ProductTrait;
    /**
     * @return Builder
     */
    public function query()
    {
        return $this->connection()->table(ConfigService::$tableDiscountCriteria);
    }
}