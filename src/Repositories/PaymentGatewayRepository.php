<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Repositories\RepositoryBase;

class PaymentGatewayRepository extends RepositoryBase
{

    /**
     * @return Builder
     */
    public function query()
    {
        return $this->connection()->table(ConfigService::$tablePaymentGateway);
    }
}