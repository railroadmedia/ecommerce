<?php

namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class PaymentMethodOwnerDecorator implements DecoratorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function decorate($paymentMethod)
    {
        $paymentMethodId   = $paymentMethod->pluck('id');
        $userPaymentMethod = $this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableUserPaymentMethods)
            ->where(ConfigService::$tableUserPaymentMethods . '.payment_method_id', $paymentMethodId)
            ->first();

        $customerPaymentMethod = $this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableCustomerPaymentMethods)
            ->where(ConfigService::$tableCustomerPaymentMethods . '.payment_method_id', $paymentMethodId)
            ->first();

        foreach($paymentMethod as $index => $paymentData)
        {
            $paymentMethod[$index]['user']     = null;
            $paymentMethod[$index]['customer'] = null;

            if($userPaymentMethod)
            {
                $paymentMethod[$index]['user'] = [
                    'user_id'    => $userPaymentMethod->user_id,
                    'is_primary' => $userPaymentMethod->is_primary
                ];
            }
            if($customerPaymentMethod)
            {
                $paymentMethod[$index]['customer'] = [
                    'customer_id' => $customerPaymentMethod->customer_id,
                    'is_primary'  => $customerPaymentMethod->is_primary
                ];
            }
        }

        return $paymentMethod;
    }
}