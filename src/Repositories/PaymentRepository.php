<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Repositories\Queries\PaymentQuery;
use Railroad\Ecommerce\Repositories\Traits\SoftDelete;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class PaymentRepository extends RepositoryBase
{
    use SoftDelete;
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new PaymentQuery($this->connection()))
            ->from(ConfigService::$tablePayment)
            ->whereNull(ConfigService::$tablePayment.'.deleted_on');
    }

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'payment');
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}