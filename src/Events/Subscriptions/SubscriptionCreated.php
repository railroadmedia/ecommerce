<?php

namespace Railroad\Ecommerce\Events\Subscriptions;

use Railroad\Ecommerce\Entities\Subscription;

class SubscriptionCreated
{
    /**
     * @var Subscription
     */
    public $subscription;

    /**
     * SubscriptionCreated constructor.
     * @param Subscription $subscription
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * @return Subscription
     */
    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }
}