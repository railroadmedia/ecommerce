<?php

namespace Railroad\Ecommerce\Events\Subscriptions;

use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\Subscription;

class MobileSubscriptionRenewed
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
     * @var Payment
     */
    private $payment;

    /**
     * MobileSubscriptionRenewed constructor.
     *
     * @param Subscription $subscription
     * @param Payment $payment
     * @param string $actor
     */
    public function __construct(
        Subscription $subscription,
        Payment $payment,
        string $actor
    )
    {
        $this->subscription = $subscription;
        $this->payment = $payment;
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
     * @return Payment
     */
    public function getPayment(): Payment
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
