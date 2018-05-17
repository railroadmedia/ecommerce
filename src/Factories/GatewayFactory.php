<?php

namespace Railroad\Ecommerce\Factories;

use Illuminate\Container\Container;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\PaypalPaymentGateway;
use Railroad\Ecommerce\Services\StripePaymentGateway;


class GatewayFactory
{
    /**
     * Create a new gateway
     *
     * @param string $class Gateway name
     * @throws \Railroad\Ecommerce\Exceptions\NotFoundException    If no such gateway is found
     * @return  PaypalPaymentGateway|StripePaymentGateway
     */
    public function create($class)
    {
        $container = Container::getInstance();

        $class = $this->prepareClass($class);

        if(!class_exists($class))
        {
            throw new NotFoundException("Class '$class' not found");
        }

        return $container->make($class);
    }

    /** Prepare payment gateway class name
     * @param string $class
     * @return string
     */
    private function prepareClass($class)
    {
        switch($class)
        {
            case PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE:
                $class = 'StripePaymentGateway';
                break;
            case PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE:
                $class = 'PaypalPaymentGateway';
                break;
            case null:
                $class = 'ManualPaymentGateway';
                break;
            default:
                $class = '';
        }

        return '\\Railroad\\Ecommerce\\Services\\' . $class ;
    }
}