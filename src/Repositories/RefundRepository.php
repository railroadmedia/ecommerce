<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Repositories\RepositoryBase;

class RefundRepository extends RepositoryBase
{

    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableRefund);
    }
}