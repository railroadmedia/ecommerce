<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Entities\Entity;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class DiscountRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tableDiscount);
    }

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'discount');
    }
}