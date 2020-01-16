<?php

namespace Railroad\Ecommerce\Listeners;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Events\OrderEvent;
use Railroad\Ecommerce\Events\Subscriptions\SubscriptionUpdated;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
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
     * DuplicateSubscriptionHandler constructor.
     * @param SubscriptionRepository $subscriptionRepository
     * @param EcommerceEntityManager $ecommerceEntityManager
     * @param UserProductRepository $userProductRepository
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        EcommerceEntityManager $ecommerceEntityManager,
        UserProductRepository $userProductRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->ecommerceEntityManager = $ecommerceEntityManager;
        $this->userProductRepository = $userProductRepository;
    }

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

    public function cancelAndExtendDuplicateSubscriptions($userId)
    {
        $allUsersSubscriptions = $this->subscriptionRepository->getAllUsersSubscriptions($userId);
        $userProducts = $this->userProductRepository->getAllUsersProducts($userId);

        // for each brand
        foreach (config('ecommerce.membership_product_syncing_info', []) as $brand => $syncData) {

            // get all membership product skus
            $membershipProductSkus = $syncData['membership_product_skus'] ?? [];

            if (empty($membershipProductSkus)) {
                return;
            }

            // check if they are a lifetime member
            $userIsLifetimeMember = false;

            foreach ($userProducts as $userProduct) {
                if ($userProduct->getProduct()->getBrand() == $brand &&
                    $userProduct->isValid() &&
                    empty($userProduct->getExpirationDate()) &&
                    in_array($userProduct->getProduct()->getSku(), $membershipProductSkus)) {
                    $userIsLifetimeMember = true;
                }
            }

            // if they are a lifetime member, cancel all active membership subscriptions
            if ($userIsLifetimeMember) {

                foreach ($allUsersSubscriptions as $userSubscription) {

                    if (!empty($userSubscription->getProduct()) &&
                        $userSubscription->getProduct()->getBrand() == $brand &&
                        in_array($userSubscription->getProduct()->getSku(), $membershipProductSkus) &&
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
                        $userSubscription->getProduct()->getBrand() == $brand &&
                        in_array($userSubscription->getProduct()->getSku(), $membershipProductSkus) &&
                        $userSubscription->getPaidUntil() > Carbon::now() &&
                        $userSubscription->getIsActive()) {

                        $totalMinutes += Carbon::now()->diffInMinutes($userSubscription->getPaidUntil());

                        if ($userSubscription->getIsActive() &&
                            empty($userSubscription->getCanceledOn())) {

                            if (empty($mostRecentlyPurchasedActiveSubscription) ||
                                $mostRecentlyPurchasedActiveSubscription->getCreatedAt() < $userSubscription->getCreatedAt()) {
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

                    // cancel all the other ones
                    foreach ($allUsersSubscriptions as $userSubscription) {

                        if (!empty($userSubscription->getProduct()) &&
                            $userSubscription->getProduct()->getBrand() == $brand &&
                            in_array($userSubscription->getProduct()->getSku(), $membershipProductSkus) &&
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
}