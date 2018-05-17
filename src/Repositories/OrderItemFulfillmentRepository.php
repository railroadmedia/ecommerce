<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Repositories\RepositoryBase;

class OrderItemFulfillmentRepository extends RepositoryBase
{
    /**
     * @return Builder
     */
    public function query()
    {
        return $this->connection()->table(ConfigService::$tableOrderItemFulfillment);
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}