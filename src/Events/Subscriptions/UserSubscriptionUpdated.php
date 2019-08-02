<?php

namespace Railroad\Ecommerce\Events\Subscriptions;

use Railroad\Ecommerce\Entities\Subscription;

class UserSubscriptionUpdated
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
     * UserSubscriptionUpdated constructor.
     *
     * @param Subscription $oldSubscription
     * @param Subscription $newSubscription
     */
    public function __construct(Subscription $oldSubscription, Subscription $newSubscription)
    {
        $this->oldSubscription = $oldSubscription;
        $this->newSubscription = $newSubscription;
    }

    /**
     * @return Subscription
     */
    public function getOldSubscription(): Subscription
    {
        return $this->oldSubscription;
    }

    /**
     * @return Subscription
     */
    public function getNewSubscription(): Subscription
    {
        return $this->newSubscription;
    }
}
