<?php

namespace Railroad\Ecommerce\Repositories\QueryBuilders;


use Railroad\Ecommerce\Services\ConfigService;

class PaymentMethodQueryBuilder extends QueryBuilder
{

    public function selectColumns()
    {
        $this->select([
            ConfigService::$tablePaymentMethod . '.id',
            'method_type',
            'method_id',
            'currency',
            ConfigService::$tablePaymentMethod . '.created_on',
            ConfigService::$tablePaymentMethod . '.updated_on',
            ConfigService::$tableUserPaymentMethods . '.user_id',
            ConfigService::$tableCustomerPaymentMethods . '.customer_id'
        ]);
        return $this;
    }

    public function joinUserAndCustomerTables()
    {
        $this->leftJoin(ConfigService::$tableUserPaymentMethods,
            ConfigService::$tablePaymentMethod . '.id',
            '=',
            ConfigService::$tableUserPaymentMethods . '.payment_method_id')
            ->leftJoin(ConfigService::$tableCustomerPaymentMethods,
                ConfigService::$tablePaymentMethod . '.id',
                '=',
                ConfigService::$tableCustomerPaymentMethods . '.payment_method_id');
        return $this;
    }
}