<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class PaymentRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tablePayment);
    }

    protected function decorate($results)
    {
         if(is_array($results))
         {
             $results = new Payment($results);
         }

        return Decorator::decorate($results, 'payment');
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}