<?php

namespace Railroad\Ecommerce\Listeners;

use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Events\MobileOrderEvent;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\UserProductService;

class MobileOrderUserProductListener
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
     * @param MobileOrderEvent $event
     */
    public function handle(MobileOrderEvent $event)
    {
        $order = $event->getOrder();

        if ($order->getUser() && $order->getOrderItems() && count($order->getOrderItems())) {
            $orderItems = $order->getOrderItems();

            foreach ($orderItems as $orderItem) {

                $product = $orderItem->getProduct();

                $expirationDate = null;

                if ($product->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION) {

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
