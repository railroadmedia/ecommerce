<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\Traits\ProductTrait;
use Railroad\Ecommerce\Services\ConfigService;

class OrderItemRepository extends RepositoryBase
{
    use ProductTrait;
    /**
     * @return Builder
     */
    public function query()
    {
        return $this->connection()->table(ConfigService::$tableOrderItem);
    }
}