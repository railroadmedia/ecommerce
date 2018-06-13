<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class PaypalBillingAgreementRepository extends RepositoryBase
{

    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tablePaypalBillingAgreement);
    }

    public function getById($id)
    {
        return $this->query()
            ->select(
                ConfigService::$tablePaypalBillingAgreement . '.*',
                ConfigService::$tablePaymentGateway . '.config'
            )
            ->join(
                ConfigService::$tablePaymentGateway,
                'payment_gateway_id',
                '=',
                ConfigService::$tablePaymentGateway . '.id'
            )
            ->where(ConfigService::$tablePaypalBillingAgreement . '.id', $id)
            ->first();
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}