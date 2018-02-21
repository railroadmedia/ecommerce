<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;

class OrderRepository extends RepositoryBase
{
    /**
     * @return Builder
     */
    public function query()
    {
        return $this->connection()->table(ConfigService::$tableOrder);
    }

    public function getOrdersByConditions($conditions)
    {
        return $this->query()
            ->where($conditions)
            ->get()
            ->toArray();
    }
}