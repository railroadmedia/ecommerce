<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class PaymentMethodRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tablePaymentMethod);
    }

    protected function decorate($results)
    {
        if(is_array($results))
        {
            $results = new PaymentMethod($results);
        }

        return Decorator::decorate($results, 'paymentMethod');
    }
}