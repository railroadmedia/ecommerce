<?php

namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Resora\Decorators\DecoratorInterface;

class MethodDecorator implements DecoratorInterface
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
        $methodId = $paymentMethod->pluck('method_id');
        $paymentMethod->map(function ($item) use ($methodId) {
            switch($item->method_type)
            {
                case PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE:
                    $item['method'] = $this->decorateCreditCard($methodId);
                    break;
                case PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE:
                    $item['method'] = $this->decoratePaypalBillingAgreement($methodId);
                    break;
                default:
                    $item['method'] = [];
            }
        });

        return $paymentMethod;
    }

    public function decorateCreditCard($methodId)
    {
        return $this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableCreditCard)
            ->where(ConfigService::$tableCreditCard . '.id', $methodId)
            ->first();
    }

    public function decoratePaypalBillingAgreement($methodId)
    {
        return $this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tablePaypalBillingAgreement)
            ->where(ConfigService::$tablePaypalBillingAgreement . '.id', $methodId)
            ->first();
    }
}