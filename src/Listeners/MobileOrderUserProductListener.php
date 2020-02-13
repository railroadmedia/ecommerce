<?php

namespace Railroad\Ecommerce\Listeners;

use Railroad\Ecommerce\Events\MobileOrderEvent;
use Railroad\Ecommerce\Services\UserProductService;
use Throwable;

class MobileOrderUserProductListener
{
    /**
     * @var UserProductService
     */
    protected $userProductService;

    /**
     * @param UserProductService $userProductService
     */
    public function __construct(UserProductService $userProductService)
    {
        $this->userProductService = $userProductService;
    }

    /**
     * @param MobileOrderEvent $event
     * @throws Throwable
     */
    public function handle(MobileOrderEvent $event)
    {
        $this->userProductService->assignUserProduct(
            $event->getSubscription()->getUser(),
            $event->getSubscription()->getProduct(),
            $event->getSubscription()->getPaidUntil()
                ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 1)),
            1
        );
    }
}
