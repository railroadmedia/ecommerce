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

    public function decorate($payment)
    {
        $productMethodId = $payment->pluck('payment_method_id');
        $paymentMethods = $this->databaseManager
            ->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tablePaymentMethod)
            ->where(ConfigService::$tablePaymentMethod.'.id', $productMethodId)
            ->first();

        foreach ($payment as $index => $paymentData) {
            $payment[$index]['payment_method'] = [
                'method_id' => $paymentMethods['method_id'],
                'method_type' => $paymentMethods['method_type'],
            ];
        }

        return $payment;
    }
}