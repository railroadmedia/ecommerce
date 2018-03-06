<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\Traits\PaymentMethodTrait;
use Railroad\Ecommerce\Services\ConfigService;

class UserPaymentMethodsRepository extends RepositoryBase
{
    use PaymentMethodTrait;

    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableUserPaymentMethods);
    }
}