<?php

namespace Railroad\Ecommerce\Events\Subscriptions;

use Railroad\Ecommerce\Entities\Subscription;

class SubscriptionDeactivated
{
    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * @var Subscription
     */
    private $oldSubscription;

    /**
     * SubscriptionDeactivated constructor.
     *
     * @param Subscription $subscription
     * @param Subscription $oldSubscription
     */
    public function __construct(
        Subscription $subscription,
        Subscription $oldSubscription
    )
    {
        $this->subscription = $subscription;
        $this->oldSubscription = $oldSubscription;
    }

    /**
     * @return Subscription
     */
    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }

    /**
     * @return Subscription
     */
    public function getOldSubscription(): Subscription
    {
        return $this->oldSubscription;
    }
}
