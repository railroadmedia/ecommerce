<?php

namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class PaymentPaymentMethodDecorator implements DecoratorInterface
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
        $productMethodIds = $payments->pluck('payment_method_id');

        $paymentMethods = $this->databaseManager
            ->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tablePaymentMethod)
            ->whereIn(ConfigService::$tablePaymentMethod . '.id', $productMethodIds)
            ->get()
            ->keyBy('id');

        foreach ($payments as $index => $payment) {
            $payments[$index]['payment_method'] = $paymentMethods[$payment['payment_method_id']] ?? null;
        }

        return $payments;
    }
}