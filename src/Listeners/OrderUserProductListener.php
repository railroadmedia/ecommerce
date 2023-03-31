<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
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
        UserProductRepository $userProductRepository,
        DateTimeService $dateTimeService
    ) {
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
                        $expirationDate = $subscription->getPaidUntil();

                        //for trials, we do not add the 'days_before_access_revoked_after_expiry' to expiration date
                        if ($order->getTotalPaid() != 0) {
                            $expirationDate = $expirationDate->addDays(
                                config('ecommerce.days_before_access_revoked_after_expiry', 5)
                            );
                        }
                    }
                } elseif ($product->getType() == Product::TYPE_DIGITAL_ONE_TIME &&
                    !empty($product->getDigitalAccessTimeIntervalType()) &&
                    !empty($product->getDigitalAccessTimeIntervalLength())) {
                    $intervalType = $product->getDigitalAccessTimeIntervalType();
                    $nIntervals = $product->getDigitalAccessTimeIntervalLength() * $orderItem->getQuantity();
                    $latestExpirationDate = $this->userProductService->getLatestExpirationDateByBrand(
                        $order->getUser(),
                        $product->getBrand()
                    );
                    $newProductExpirationDate = $this->dateTimeService->addInterval(
                        Carbon::now(),
                        $intervalType,
                        $nIntervals
                    )->addDays(
                        config('ecommerce.days_before_access_revoked_after_expiry', 5)
                    );
                    $existingProductExpirationDate = $latestExpirationDate ? $this->dateTimeService->addInterval(
                        $latestExpirationDate,
                        $intervalType,
                        $nIntervals
                    ) : null;
                    $expirationDate = ($existingProductExpirationDate && $existingProductExpirationDate > $newProductExpirationDate) ?
                        $existingProductExpirationDate :
                        $newProductExpirationDate;
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
