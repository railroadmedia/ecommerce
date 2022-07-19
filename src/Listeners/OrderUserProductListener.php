<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\DateTimeService;
use Railroad\Ecommerce\Services\UserProductService;
use Throwable;

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
     * @var DateTimeService
     */
    protected $dateTimeService;

    /**
     * @param SubscriptionRepository $subscriptionRepository
     * @param UserProductService $userProductService
     * @param DateTimeService $dateTimeService
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        UserProductService $userProductService,
        DateTimeService $dateTimeService
    )
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProductService = $userProductService;
        $this->dateTimeService = $dateTimeService;
    }

    /**
     * @param OrderEvent $event
     *
     * @throws NonUniqueResultException
     * @throws Throwable
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
                        $expirationDate = $subscription->getPaidUntil()->copy();
                    }
                }
                // if its a non-recurring one time membership product
                else if ($product->getType() == Product::TYPE_DIGITAL_ONE_TIME &&
                    !empty($product->getSubscriptionIntervalType()) &&
                    !empty($product->getSubscriptionIntervalCount())) {
                    $userProduct = $this->userProductService->getUserProduct($order->getUser(), $product);
                    $start = $userProduct->getExpirationDate()->copy() ?? Carbon::now();
                    $intervalType = $product->getSubscriptionIntervalType();
                    $nIntervals = $product->getSubscriptionIntervalCount() * $orderItem->getQuantity();

                    $expirationDate = $this->dateTimeService->addInterval($start, $intervalType, $nIntervals);
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
