<?php

namespace Railroad\Ecommerce\Events\Payments;

use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\User;

class SubscriptionPaymentFailed
{
    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var User
     */
    private $user;

    /**
     * SubscriptionPaymentFailed constructor.
     *
     * @param Subscription $subscription
     * @param User $user
     */
    public function __construct(
        Payment $payment,
        User $user
    )
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
     * User linked to subscription/payment
     *
     * @return User
     */
    public function getPaymentUser(): User
    {
        return $this->user;
    }
}
