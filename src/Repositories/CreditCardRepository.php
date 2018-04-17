<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\Traits\PaymentMethodTrait;
use Railroad\Ecommerce\Services\ConfigService;

class CreditCardRepository extends RepositoryBase
{
    use PaymentMethodTrait;
    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableCreditCard);
    }

    public function getById($id)
    {
        return $this->query()
            ->select(ConfigService::$tableCreditCard.'.*', ConfigService::$tablePaymentGateway.'.config')
            ->join(ConfigService::$tablePaymentGateway , 'payment_gateway_id','=',ConfigService::$tablePaymentGateway.'.id')
            ->where(ConfigService::$tableCreditCard. '.id', $id)
            ->first();
    }
}