<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Services\UserProductService;
use Throwable;

class DuplicateSubscriptionHandler
{
    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;
    /**
     * @var EcommerceEntityManager
     */
    private $ecommerceEntityManager;
    /**
     * @var UserProductRepository
     */
    private $userProductRepository;
    /**
     * @var UserProductService
     */
    private $userProductService;

    /**
     * DuplicateSubscriptionHandler constructor.
     * @param SubscriptionRepository $subscriptionRepository
     * @param EcommerceEntityManager $ecommerceEntityManager
     * @param UserProductRepository $userProductRepository
     * @param UserProductService $userProductService
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        EcommerceEntityManager $ecommerceEntityManager,
        UserProductRepository $userProductRepository,
        UserProductService $userProductService
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->ecommerceEntityManager = $ecommerceEntityManager;
        $this->userProductRepository = $userProductRepository;
        $this->userProductService = $userProductService;
    }

    /**
     * @param OrderEvent $orderEvent
     */
    public function handle(OrderEvent $orderEvent)
    {
        try {
            if (!empty($orderEvent->getOrder()->getUser())) {
                $this->cancelAndExtendDuplicateSubscriptions($orderEvent->getOrder()->getUser()->getId());
            }
        } catch (Throwable $exception) {
            error_log('--- Error with duplicate subscription handling in ecommerce package.');
            error_log($exception);
        }
    }

    /**
     * @param int $userId
     *
     * @throws Throwable
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function cancelAndExtendDuplicateSubscriptions($userId)
    {
        $allUsersSubscriptions = $this->subscriptionRepository->getAllUsersSubscriptions($userId);
        $userProducts = $this->userProductRepository->getAllUsersProducts($userId);

        // check if they are a lifetime member
        $userIsLifetimeMember = false;

        foreach ($userProducts as $userProduct) {
            if ($userProduct->isValid() &&
                empty($userProduct->getExpirationDate()) &&
                $userProduct->getProduct()->isMembershipProduct() &&
                $userProduct->getProduct()->getDigitalAccessTimeType() == Product::DIGITAL_ACCESS_TIME_TYPE_LIFETIME) {
                $userIsLifetimeMember = true;
            }
        }

        // if they are a lifetime member, cancel all active membership subscriptions
        if ($userIsLifetimeMember) {
            foreach ($allUsersSubscriptions as $userSubscription) {
                if (!empty($userSubscription->getProduct()) &&
                    $userSubscription->getProduct()->isMembershipProduct() &&
                    $userSubscription->getIsActive()) {
                    $oldUserSubscription = clone($userSubscription);

                    $userSubscription->setIsActive(false);
                    $userSubscription->setCanceledOn(Carbon::now());

                    $this->ecommerceEntityManager->persist($userSubscription);
                    $this->ecommerceEntityManager->flush($userSubscription);

                    event(new SubscriptionUpdated($oldUserSubscription, $userSubscription));
                }
            }
        } else {
            // otherwise, find all duplicate subscriptions with an expiration date in the future,
            // add up their remaining time, then set the latest active one with that expiration date
            $totalMinutes = 0;

            /**
             * @var $mostRecentlyPurchasedActiveSubscription Subscription|null
             */
            $mostRecentlyPurchasedActiveSubscription = null;

            // extend the date of the most recently created subscription
            foreach ($allUsersSubscriptions as $userSubscription) {
                if (!empty($userSubscription->getProduct()) &&
                    $userSubscription->getProduct()->isMembershipProduct() &&
                    $userSubscription->getPaidUntil() > Carbon::now() &&
                    $userSubscription->getIsActive()) {
                    $totalMinutes += Carbon::now()->diffInMinutes($userSubscription->getPaidUntil());

                    if ($userSubscription->getIsActive() &&
                        empty($userSubscription->getCanceledOn())) {
                        if (empty($mostRecentlyPurchasedActiveSubscription) ||
                            $mostRecentlyPurchasedActiveSubscription->getCreatedAt() < $userSubscription->getCreatedAt(
                            )) {
                            $mostRecentlyPurchasedActiveSubscription = $userSubscription;
                        }
                    }
                }
            }

            if (!empty($mostRecentlyPurchasedActiveSubscription)) {
                $oldUserSubscription = clone($mostRecentlyPurchasedActiveSubscription);

                $mostRecentlyPurchasedActiveSubscription->setPaidUntil(Carbon::now()->addMinutes($totalMinutes));

                $this->ecommerceEntityManager->persist($mostRecentlyPurchasedActiveSubscription);
                $this->ecommerceEntityManager->flush($mostRecentlyPurchasedActiveSubscription);

                event(new SubscriptionUpdated($oldUserSubscription, $mostRecentlyPurchasedActiveSubscription));

                $this->userProductService->updateSubscriptionProducts($mostRecentlyPurchasedActiveSubscription);

                // cancel all the other ones
                foreach ($allUsersSubscriptions as $userSubscription) {
                    if (!empty($userSubscription->getProduct()) &&
                        $userSubscription->getProduct()->isMembershipProduct() &&
                        $userSubscription->getPaidUntil() > Carbon::now() &&
                        $userSubscription->getIsActive() &&
                        $mostRecentlyPurchasedActiveSubscription->getId() != $userSubscription->getId()) {
                        $oldUserSubscription = clone($userSubscription);

                        $userSubscription->setIsActive(false);
                        $userSubscription->setCanceledOn(Carbon::now());

                        $this->ecommerceEntityManager->persist($userSubscription);
                        $this->ecommerceEntityManager->flush($userSubscription);

                        event(new SubscriptionUpdated($oldUserSubscription, $userSubscription));
                    }
                }
            }
        }
    }
}