<?php

namespace Railroad\Ecommerce\Factories;

use Illuminate\Container\Container;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Services\PaymentMethodService;

class GatewayFactory
{
    /**
     * Create a new gateway
     *
     * @param string $class Gateway name
     * @throws \Railroad\Ecommerce\Exceptions\NotFoundException    If no such gateway is found
     * @return  An object of class $class is created and returned
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
                $class = 'Stripe';
                break;
            case PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE:
                $class = 'Paypal';
                break;
            case null:
                $class = 'Manual';
                break;
            default:
                $class = '';
        }

        return '\\Railroad\\Ecommerce\\Services\\' . $class . 'PaymentGateway';
    }
}