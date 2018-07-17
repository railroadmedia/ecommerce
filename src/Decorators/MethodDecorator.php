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

    public function decorate($paymentMethods)
    {
        $paymentMethods->map(function ($item) {
            switch($item->method_type)
            {
                case PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE:
                    $item['method'] = $this->decorateCreditCard($item['method_id']);
                    break;
                case PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE:
                    $item['method'] = $this->decoratePaypalBillingAgreement($item['method_id']);
                    break;
                default:
                    $item['method'] = [];
            }
        });

        return $paymentMethods;
    }

    public function decorateCreditCard($methodId)
    {
        return (array)$this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableCreditCard)
            ->where(ConfigService::$tableCreditCard . '.id', $methodId)
            ->first();
    }

    public function decoratePaypalBillingAgreement($methodId)
    {
        return (array)$this->databaseManager->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tablePaypalBillingAgreement)
            ->where(ConfigService::$tablePaypalBillingAgreement . '.id', $methodId)
            ->first();
    }
}