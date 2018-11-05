<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class ShippingOptionRepository extends RepositoryBase
{
    /**
     * Determines whether inactive shipping options will be pulled or not.
     *
     * @var array|bool
     */
    public static $pullInactiveShippingOptions = true;

    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tableShippingOption);
    }

    /**
     * @param $results
     * @return mixed
     */
    protected function decorate($results)
    {
        return Decorator::decorate($results, 'shippingOptions');
    }

    /**
     * @return mixed
     */
    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}