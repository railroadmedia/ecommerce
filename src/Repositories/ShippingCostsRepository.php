<?php

namespace Railroad\Ecommerce\Repositories;


use Railroad\Ecommerce\Services\ConfigService;

class ShippingCostsRepository extends RepositoryBase
{
    /**
     * @return mixed
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableShippingCostsWeightRange);
    }
}