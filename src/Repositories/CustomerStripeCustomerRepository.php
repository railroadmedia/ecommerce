<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class CustomerStripeCustomerRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tablePayment);
    }

    public function getByCustomerId($customerId)
    {
        return $this->query()->where('customer_id', $customerId)->first();
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}