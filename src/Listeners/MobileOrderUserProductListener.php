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
        if ($event->getSubscription()) {
            $subscription = $event->getSubscription();
            $user = $subscription->getUser();
            $product = $subscription->getProduct();

            $this->userProductService->assignUserProduct(
                $user,
                $product,
                $subscription->getPaidUntil()
                    ->copy()
                    ->addDays(config('ecommerce.days_before_access_revoked_after_expiry_in_app_purchases_only', 1))
            );
        }

        if ($event->getOrder()) {
            $order = $event->getOrder();
            $user = $order->getUser();

            foreach ($order->getOrderItems() as $item) {
                $this->userProductService->assignUserProduct(
                    $user,
                    $item->getProduct(),
                    null,
                    $item->getQuantity()
                );
            }
        }
    }
}
