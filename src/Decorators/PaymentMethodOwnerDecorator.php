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

    public function decorate($paymentMethods)
    {
        $paymentMethodIds = $paymentMethods->pluck('id');

        $userPaymentMethods = $this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableUserPaymentMethods)
            ->whereIn(ConfigService::$tableUserPaymentMethods . '.payment_method_id', $paymentMethodIds)
            ->get()
            ->keyBy('payment_method_id');

        $customerPaymentMethods = $this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableCustomerPaymentMethods)
            ->whereIn(ConfigService::$tableCustomerPaymentMethods . '.payment_method_id', $paymentMethodIds)
            ->get()
            ->keyBy('payment_method_id');

        foreach ($paymentMethods as $index => $paymentData) {
            $paymentMethods[$index]['user'] = null;
            $paymentMethods[$index]['customer'] = null;

            $userPaymentMethod = $userPaymentMethods[$paymentData['id']] ?? null;
            $customerPaymentMethod = $customerPaymentMethods[$paymentData['id']] ?? null;

            if (!is_null($userPaymentMethod)) {
                $userPaymentMethod = (array)$userPaymentMethod;

                $paymentMethods[$index]['user_id'] = $userPaymentMethod['user_id'];
                $paymentMethods[$index]['user'] = [
                    'user_id' => $userPaymentMethod['user_id'],
                    'is_primary' => $userPaymentMethod['is_primary'],
                ];
            }
            if (!is_null($customerPaymentMethod)) {
                $customerPaymentMethod = (array)$customerPaymentMethod;

                $paymentMethods[$index]['customer'] = $customerPaymentMethod['customer_id'];
                $paymentMethods[$index]['customer'] = [
                    'customer_id' => $customerPaymentMethod['customer_id'],
                    'is_primary' => $customerPaymentMethod['is_primary'],
                ];
            }
        }

        return $paymentMethods;
    }
}