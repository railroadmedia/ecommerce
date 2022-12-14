<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Repositories\SubscriptionRepository;

class SubscriptionUpgradeService
{
    protected SubscriptionRepository $subscriptionRepository;
    protected UserProviderInterface $userProvider;

    public function __construct(SubscriptionRepository $subscriptionRepository, UserProviderInterface $userProvider)
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->userProvider = $userProvider;
    }

    private function isFullTier(Subscription $subscription): bool
    {
    }

    private function isBasicTier(Subscription $subscription): bool
    {
    }

    private function isLifeTime(): bool
    {
    }

    private function isLifeTimeFull(): bool
    {
    }

    public function upgrade(int $userId): void
    {
        $user = $this->userProvider->
        $subscriptions = $this->subscriptionRepository->getLatestActiveSubscriptionExcludingMobile($userId);
        if ($this->isBasicTier()) {
        }
    }

    public function getUpgradeRate(int $userId): float
    {
        if ($this->isBasicTier()) {
        } else {
            return 0;
        }
    }

    public function downgrade(int $userId): void
    {
        if ($this->isFullTier()) {
        }
    }
}