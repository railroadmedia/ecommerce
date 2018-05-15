<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\Traits\PaymentMethodTrait;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class PaypalBillingAgreementRepository extends RepositoryBase
{
 use PaymentMethodTrait;
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
            ->select(ConfigService::$tablePaypalBillingAgreement.'.*', ConfigService::$tablePaymentGateway.'.config')
            ->join(ConfigService::$tablePaymentGateway , 'payment_gateway_id','=',ConfigService::$tablePaymentGateway.'.id')
            ->where(ConfigService::$tablePaypalBillingAgreement. '.id', $id)
            ->first();
    }
}