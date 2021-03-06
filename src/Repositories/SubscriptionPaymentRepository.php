<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;

class SubscriptionPaymentRepository extends RepositoryBase
{

    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableSubscriptionPayment);
    }
}