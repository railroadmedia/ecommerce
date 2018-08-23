<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Repositories\Queries\UserPaymentMethodQuery;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Repositories\RepositoryBase;

class UserPaymentMethodsRepository extends RepositoryBase
{

    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new UserPaymentMethodQuery($this->connection()))
            ->from(ConfigService::$tableUserPaymentMethods);
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'userPaymentMethods');

    }
}