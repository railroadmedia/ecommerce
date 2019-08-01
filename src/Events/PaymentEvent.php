<?php

namespace Railroad\Ecommerce\Events;

use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\User;

class PaymentEvent
{
    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var User
     */
    protected $user;

    /**
     * Create a new event instance.
     *
     * @param Payment $payment
     * @param User $user
     */
    public function __construct(Payment $payment, User $user)
    {
        $this->payment = $payment;
        $this->user = $user;
    }

    /**
     * @return Payment
     */
    public function getPayment(): Payment
    {
        return $this->payment;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }
}
