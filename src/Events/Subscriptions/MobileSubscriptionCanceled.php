<?php

namespace Railroad\Ecommerce\Events\Subscriptions;

use Railroad\Ecommerce\Entities\Subscription;

class MobileSubscriptionCanceled
{
    const ACTOR_CONSOLE = 'console';
    const ACTOR_SYSTEM = 'system';

    /**
     * @var string
     */
    private $actor;

    /**
     * @var Subscription
     */
    private $subscription;

    /**
     * MobileSubscriptionCanceled constructor.
     *
     * @param Subscription $subscription
     * @param string $actor
     */
    public function __construct(Subscription $subscription, string $actor)
    {
        $this->subscription = $subscription;
        $this->actor = $actor;
    }

    /**
     * @return string
     */
    public function getActor(): string
    {
        return $this->actor;
    }

    /**
     * @return Subscription
     */
    public function getSubscription(): Subscription
    {
        return $this->subscription;
    }
}
