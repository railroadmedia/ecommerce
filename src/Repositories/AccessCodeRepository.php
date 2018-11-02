<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Repositories\Queries\AccessCodeQuery;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class AccessCodeRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new AccessCodeQuery($this->connection()))->from(ConfigService::$tableAccessCode);
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'accessCode');
    }
}
