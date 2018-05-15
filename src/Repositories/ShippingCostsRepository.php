<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Repositories\RepositoryBase;

class ShippingCostsRepository extends RepositoryBase
{
    /**
     * @return mixed
     */
    public function query()
    {
        return $this->connection()->table(ConfigService::$tableShippingCostsWeightRange);
    }
}