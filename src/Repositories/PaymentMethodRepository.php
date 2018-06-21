<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Repositories\Queries\PaymentMethodQuery;
use Railroad\Ecommerce\Repositories\Traits\SoftDelete;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Repositories\RepositoryBase;

class PaymentMethodRepository extends RepositoryBase
{
    use SoftDelete;
    /**
     * @return PaymentMethodQuery|$this
     */
    protected function newQuery()
    {
        return (new PaymentMethodQuery($this->connection()))->from(ConfigService::$tablePaymentMethod)->whereNull(
            'deleted_on'
        );
    }

    protected function decorate($results)
    {
        if (is_array($results)) {
            $results = new PaymentMethod($results);
        }

        return Decorator::decorate($results, 'paymentMethod');
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}