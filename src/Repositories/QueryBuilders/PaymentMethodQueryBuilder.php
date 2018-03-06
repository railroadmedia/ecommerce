<?php

namespace Railroad\Ecommerce\Repositories\QueryBuilders;


use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Services\ConfigService;

class PaymentMethodQueryBuilder extends QueryBuilder
{
    /**
     * @return $this
     */
    public function restrictCustomerIdAccess()
    {
        if (PaymentMethodRepository::$availableCustomerId) {
            $this->leftJoin(ConfigService::$tableCustomerPaymentMethods,
                ConfigService::$tablePaymentMethod . '.id',
                '=',
                ConfigService::$tableCustomerPaymentMethods . '.payment_method_id')
                ->where('customer_id', PaymentMethodRepository::$availableCustomerId);
        }
        return $this;
    }

    public function restrictUserIdAccess()
    {
        if (PaymentMethodRepository::$availableUserId) {
            $this->leftJoin(ConfigService::$tableUserPaymentMethods,
                ConfigService::$tablePaymentMethod . '.id',
                '=',
                ConfigService::$tableUserPaymentMethods . '.payment_method_id')
                ->where('user_id', PaymentMethodRepository::$availableUserId);
        }
        return $this;
    }

    public function selectColumns()
    {
        $this->select([
            ConfigService::$tablePaymentMethod . '.id',
            'method_type',
            'method_id',
            ConfigService::$tablePaymentMethod . '.created_on',
            ConfigService::$tablePaymentMethod . '.updated_on'
        ]);
        return $this;
    }
}