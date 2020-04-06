<?php

namespace Railroad\Ecommerce\Events\Subscriptions;

use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;

class SubscriptionRenewFailed
{
    /**
     * @var Subscription
     */
    public $oldSubscription;

    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var Subscription
     */
    public $subscription;

    /**
     * SubscriptionRenewFailed constructor.
     *
     * @param Subscription $subscription
     * @param Subscription $oldSubscription
     * @param Payment $payment
     */
    public function __construct(
        Subscription $subscription,
        Subscription $oldSubscription,
        ?Payment $payment
    )
    {
        $this->subscription = $subscription;
        $this->oldSubscription = $oldSubscription;
        $this->payment = $payment;
    }

    /**
     * @return Subscription
     */
    public function getOldSubscription(): Subscription
    {
        return $this->oldSubscription;
    }

    /**
     * @return Payment|null
     */
    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    /**
     * @return Subscription
     */
    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }
}
