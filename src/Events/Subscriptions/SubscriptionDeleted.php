<?php

namespace Railroad\Ecommerce\Events\Subscriptions;

use Railroad\Ecommerce\Entities\Subscription;

class SubscriptionDeleted
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
}