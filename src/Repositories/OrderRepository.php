<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Repositories\Traits\SoftDelete;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class OrderRepository extends RepositoryBase
{
    use SoftDelete;
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tableOrder)->whereNull('deleted_on');
    }

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'order');
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}