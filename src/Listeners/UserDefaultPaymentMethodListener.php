<?php

namespace Railroad\Ecommerce\Listeners;

use Illuminate\Support\Facades\Event;
use Railroad\Ecommerce\Events\UserDefaultPaymentMethodEvent;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;

class UserDefaultPaymentMethodListener
{
    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    protected $subscriptionRepository;

    public function __construct(SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function handle(UserDefaultPaymentMethodEvent $event)
    {
        $this->subscriptionRepository
            ->query()
            ->where('user_id', $event->getUserId())
            ->update(
                ['payment_method_id' => $event->getDefaultPaymentMethodId()]
            );
    }
}
