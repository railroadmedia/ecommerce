<?php

namespace Railroad\Ecommerce\Events\PaymentMethods;

use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\User;

class PaymentMethodUpdated
{
    /**
     * @var PaymentMethod
     */
    private $newPaymentMethod;

    /**
     * @var PaymentMethod
     */
    private $oldPaymentMethod;

    /**
     * @var User
     */
    private $user;

    /**
     * @param PaymentMethod $newPaymentMethod
     * @param PaymentMethod $oldPaymentMethod
     * @param User $user
     */
    public function __construct(
        PaymentMethod $newPaymentMethod,
        PaymentMethod $oldPaymentMethod,
        User $user
    )
    {
        $this->newPaymentMethod = $newPaymentMethod;
        $this->oldPaymentMethod = $oldPaymentMethod;
        $this->user = $user;
    }

    /**
     * @return PaymentMethod
     */
    public function getNewPaymentMethod(): PaymentMethod
    {
        return $this->newPaymentMethod;
    }

    /**
     * @return PaymentMethod
     */
    public function getOldPaymentMethod(): PaymentMethod
    {
        return $this->oldPaymentMethod;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}