<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\UserProductService;

class OrderUserProductListener
{
    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;

    /**
     * @var UserProductService
     */
    protected $userProductService;

    /**
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService
    )
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
    }

    /**
     * @param OrderEvent $event
     */
    public function handle(OrderEvent $event)
    {
        $order = $event->getOrder();

        if ($order->getUser() && $order->getOrderItems() && count($order->getOrderItems())) {
            $orderItems = $order->getOrderItems();

            foreach ($orderItems as $orderItem) {

                $product = $orderItem->getProduct();

                $expirationDate = null;

                if ($product->getType() == ConfigService::$typeSubscription) {

                    $subscription = $this->subscriptionRepository->getOrderProductSubscription(
                        $order,
                        $product
                    );

                    if ($subscription) {
                        $expirationDate = $subscription->getPaidUntil();
                    }
                }

                $this->userProductService->assignUserProduct(
                    $order->getUser(),
                    $product,
                    $expirationDate,
                    $orderItem->getQuantity()
                );
            }
        }
    }
}
