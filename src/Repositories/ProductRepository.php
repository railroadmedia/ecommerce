<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class ProductRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tableProduct);
    }

    protected function decorate($results)
    {
        if(is_array($results))
        {
            $results = new Product($results);
        }

        return Decorator::decorate($results, 'product');
    }
}