<?php

namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class PaymentUserDecorator implements DecoratorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function decorate($payment)
    {
        $paymentMethodId   = $payment->pluck('payment_method_id');
        $userPaymentMethod = $this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableUserPaymentMethods)
            ->where(ConfigService::$tableUserPaymentMethods . '.payment_method_id', $paymentMethodId)
            ->first();

        foreach($payment as $index => $paymentData)
        {
            $payment[$index]['user'] = [
                'user_id'    => $userPaymentMethod->user_id,
                'is_primary' => $userPaymentMethod->is_primary
            ];
        }

        return $payment;
    }
}