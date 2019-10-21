<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
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

                if ($product->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION) {

                    $subscription = $this->subscriptionRepository->getOrderProductSubscription(
                        $order,
                        $product
                    );

                    if ($subscription) {
                        $expirationDate = $subscription->getPaidUntil();
                    }
                }

                // if its a non-recurring one time membership product
                if ($product->getType() == Product::TYPE_DIGITAL_ONE_TIME &&
                    !empty($product->getSubscriptionIntervalType()) &&
                    !empty($product->getSubscriptionIntervalCount())) {

                    if ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_monthly')) {
                        $expirationDate =
                            Carbon::now()
                                ->addMonths($product->getSubscriptionIntervalCount());

                    }
                    elseif ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_yearly')) {
                        $expirationDate =
                            Carbon::now()
                                ->addYears($product->getSubscriptionIntervalCount());

                    }
                    elseif ($product->getSubscriptionIntervalType() == config('ecommerce.interval_type_daily')) {
                        $expirationDate =
                            Carbon::now()
                                ->addDays($product->getSubscriptionIntervalCount());
                    }
                }

                if (!empty($expirationDate)) {
                    $expirationDate = $expirationDate->addDays(
                        config('ecommerce.days_before_access_revoked_after_expiry', 5)
                    );
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
