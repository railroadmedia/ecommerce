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

    public function decorate($payments)
    {
        $paymentMethodIds = $payments->pluck('payment_method_id');
        $userPaymentMethod = $this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableUserPaymentMethods)
            ->whereIn(ConfigService::$tableUserPaymentMethods . '.payment_method_id', $paymentMethodIds)
            ->get()
            ->keyBy('id');

        foreach ($payments as $index => $payment) {
            $payments[$index]['user'] = $userPaymentMethod[$payment['payment_method_id']] ?? null;
        }

        return $payments;
    }
}