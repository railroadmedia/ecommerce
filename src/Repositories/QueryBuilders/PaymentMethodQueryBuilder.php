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
        if (!PaymentMethodRepository::$pullAllPaymentMethods) {
            $this
                ->where('customer_id', PaymentMethodRepository::$availableCustomerId);
        }
        return $this;
    }

    public function restrictUserIdAccess()
    {
        if (!PaymentMethodRepository::$pullAllPaymentMethods) {
            $this
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