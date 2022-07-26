<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Doctrine\ORM\NonUniqueResultException;
use Faker\Provider\DateTime;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Services\DateTimeService;
use Railroad\Ecommerce\Services\UserProductService;
use Throwable;

use function Aws\map;

class OrderOneTimeProductListener
{
    /**
     * @var SubscriptionRepository
     */
    protected $subscriptionRepository;
    /**
     * @var EcommerceEntityManager
     */
    private $ecommerceEntityManager;
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
     * @param EcommerceEntityManager $ecommerceEntityManager
     * @param UserProductService $userProductService
     * @param DateTimeService $dateTimeService
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        EcommerceEntityManager $ecommerceEntityManager,
        UserProductService $userProductService,
        DateTimeService $dateTimeService
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->ecommerceEntityManager = $ecommerceEntityManager;
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
        if (!$order->getUser() || !$order->getOrderItems() || !count($order->getOrderItems())) {
            return;
        }
        $orderItems = $order->getOrderItems();
        $subscriptions = collect(
            $this->subscriptionRepository->getActiveSubscriptionsByUserId($order->getUser()->getId())
        );
        $subscriptionsMap = $subscriptions->mapWithKeys(function ($subscription, $key) {
            return [$subscription->getBrand() => $subscription];
        });

        foreach ($orderItems as $orderItem) {
            $product = $orderItem->getProduct();
            $subscription = $subscriptionsMap[$product->getBrand()] ?? null;
            if ($subscription == null) {
                continue;
            }
            $paidUntil = $subscription->getPaidUntil()->copy();
            $isOneTimeProduct = $product->getType() == Product::TYPE_DIGITAL_ONE_TIME &&
                !empty($product->getDigitalAccessTimeIntervalType()) &&
                !empty($product->getDigitalAccessTimeIntervalLength());

            if ($isOneTimeProduct) {
                $intervalType = $product->getDigitalAccessTimeIntervalType();
                $nIntervals = $product->getDigitalAccessTimeIntervalLength() * $orderItem->getQuantity();
                $paidUntil = $this->dateTimeService->addInterval($paidUntil, $intervalType, $nIntervals);

                $this->updateSubscription($subscription, $paidUntil);
            }
        }
    }

    /**
     * @param $subscription
     * @param Carbon $paidUntil
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateSubscription($subscription, Carbon $paidUntil)
    {
        $oldSubscription = clone $subscription;
        $subscription->setPaidUntil($paidUntil);
        $this->ecommerceEntityManager->persist($subscription);
        $this->ecommerceEntityManager->flush($subscription);
        event(new SubscriptionUpdated($oldSubscription, $subscription));
        $this->userProductService->updateSubscriptionProducts($subscription);
    }
}
