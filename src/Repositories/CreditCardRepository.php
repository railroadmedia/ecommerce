<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class CreditCardRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tableCreditCard);
    }

    public function getById($id)
    {
        return $this->query()
            ->select(ConfigService::$tableCreditCard . '.*', ConfigService::$tablePaymentGateway . '.config')
            ->join(
                ConfigService::$tablePaymentGateway,
                'payment_gateway_id',
                '=',
                ConfigService::$tablePaymentGateway . '.id'
            )
            ->where(ConfigService::$tableCreditCard . '.id', $id)
            ->first();
    }

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'credit-card');
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}