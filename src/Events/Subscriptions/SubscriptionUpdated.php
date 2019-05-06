<?php

namespace Railroad\Ecommerce\Events\Subscriptions;

use Railroad\Ecommerce\Entities\Subscription;

class SubscriptionUpdated
{
    /**
     * @var Subscription
     */
    public $oldSubscription;

    /**
     * @var Subscription
     */
    public $newSubscription;

    /**
     * SubscriptionUpdated constructor.
     * @param Subscription $oldSubscription
     * @param Subscription $newSubscription
     */
    public function __construct(Subscription $oldSubscription, Subscription $newSubscription)
    {
        $this->oldSubscription = $oldSubscription;
        $this->newSubscription = $newSubscription;
    }
}