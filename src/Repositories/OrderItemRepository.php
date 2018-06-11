<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class OrderItemRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tableOrderItem);
    }

    protected function decorate($results)
    {
        if(is_array($results))
        {
            $results = new OrderItem($results);
        }
        return Decorator::decorate($results, 'orderItem');
    }


}