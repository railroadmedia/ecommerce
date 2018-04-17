<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\Traits\PaymentMethodTrait;
use Railroad\Ecommerce\Services\ConfigService;

class PaypalBillingAgreementRepository extends RepositoryBase
{
 use PaymentMethodTrait;
    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tablePaypalBillingAgreement);
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