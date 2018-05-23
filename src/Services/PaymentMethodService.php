<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Factories\GatewayFactory;


class PaymentMethodService
{
    /**
     * @var \Railroad\Ecommerce\Factories\GatewayFactory
     */
    private $gatewayFactory;

    //constants that represent payment method types
    CONST PAYPAL_PAYMENT_METHOD_TYPE      = 'paypal';
    CONST CREDIT_CARD_PAYMENT_METHOD_TYPE = 'credit card';
    //constants for update action
    CONST UPDATE_PAYMENT_METHOD_AND_CREATE_NEW_CREDIT_CARD = 'create-credit-card';
    CONST UPDATE_PAYMENT_METHOD_AND_UPDATE_CREDIT_CARD     = 'update-current-credit-card';
    CONST UPDATE_PAYMENT_METHOD_AND_USE_PAYPAL             = 'use-paypal';

    /**
     * PaymentMethodService constructor.
     *
     * @param \Railroad\Ecommerce\Factories\GatewayFactory $gatewayFactory
     */
    public function __construct(GatewayFactory $gatewayFactory)
    {
        $this->gatewayFactory = $gatewayFactory;
    }

    /**
     * @param \Railroad\Ecommerce\Requests\PaymentMethodCreateRequest $request
     * @return array|int
     * @throws \Railroad\Ecommerce\Exceptions\NotFoundException
     */
    public function saveMethod(array $data)
    {
        $gateway = $this->gatewayFactory->create($data['method_type']);

        $results = $gateway->saveExternalData($data);

        return $results;
    }
}