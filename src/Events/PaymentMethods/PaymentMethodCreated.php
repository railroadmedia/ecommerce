<?php

namespace Railroad\Ecommerce\Events\PaymentMethods;

use Railroad\Ecommerce\Contracts\IdentifiableInterface;
use Railroad\Ecommerce\Entities\PaymentMethod;

class PaymentMethodCreated
{
    /**
     * @var PaymentMethod
     */
    private $paymentMethod;

    /**
     * @var IdentifiableInterface
     */
    private $user;

    /**
     * @param PaymentMethod $paymentMethod
     */
    public function __construct(PaymentMethod $paymentMethod, IdentifiableInterface $user)
    {
        $this->paymentMethod = $paymentMethod;
        $this->user = $user;
    }

    /**
     * @return PaymentMethod
     */
    public function getPaymentMethod(): PaymentMethod
    {
        return $this->paymentMethod;
    }

    /**
     * @return IdentifiableInterface
     */
    public function getUser(): IdentifiableInterface
    {
        return $this->user;
    }
}