<?php

namespace Railroad\Ecommerce\Events\Subscriptions;

use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;

class CommandSubscriptionRenewed
{
    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * @var Payment
     */
    private $payment;

    /**
     * SubscriptionCreated constructor.
     * @param Subscription $subscription
     * @param Payment $payment
     */
    public function __construct(Subscription $subscription, Payment $payment)
    {
        $this->subscription = $subscription;
        $this->payment = $payment;
    }

    /**
     * @return Subscription
     */
    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }

    /**
     * @return Payment
     */
    public function getPayment(): Payment
    {
        return $this->payment;
    }
}
