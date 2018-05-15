<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Repositories\Traits\PaymentMethodTrait;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class CustomerPaymentMethodsRepository extends RepositoryBase
{
    use PaymentMethodTrait;

    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tableCustomerPaymentMethods);
    }
}