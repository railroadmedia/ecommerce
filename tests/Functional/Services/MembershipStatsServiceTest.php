<?php

namespace Railroad\Ecommerce\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Structures\SubscriptionStateInterval;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Services\MembershipStatsService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class MembershipStatsServiceTest extends EcommerceTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function test_get_subscription_state_intervals_subscription_active()
    {
        $statsStartDate = Carbon::now()->subDays(30)->startOfDay();
        $statsEndDate = Carbon::now()->startOfDay();

        $subscription = new Subscription();

        $subscription->setIsActive(true);
        $subscription->setPaidUntil(Carbon::now()->addDays(10));
        $subscription->setStartDate(Carbon::now()->subMonth(2)); // subscription started before stats interval

        $expected = [
            new SubscriptionStateInterval($statsStartDate, $statsEndDate, SubscriptionStateInterval::TYPE_ACTIVE)
        ];

        $membershipStatsService = $this->app->make(MembershipStatsService::class);

        $result = $membershipStatsService->getSubscriptionStateIntervals($subscription, $statsStartDate, $statsEndDate);

        $this->assertEquals($expected, $result);
    }

    public function test_get_subscription_state_intervals_subscription_suspended()
    {
        $statsStartDate = Carbon::now()->subDays(30)->startOfDay();
        $statsEndDate = Carbon::now()->startOfDay();

        $subscription = new Subscription();

        $subscription->setIsActive(false);
        $subscription->setPaidUntil(Carbon::now()->subDays(40)); // subscription is suspended before stats interval
        $subscription->setStartDate(Carbon::now()->subMonth(3));

        $expected = [
            new SubscriptionStateInterval($statsStartDate, $statsEndDate, SubscriptionStateInterval::TYPE_SUSPENDED)
        ];

        $membershipStatsService = $this->app->make(MembershipStatsService::class);

        $result = $membershipStatsService->getSubscriptionStateIntervals($subscription, $statsStartDate, $statsEndDate);

        $this->assertEquals($expected, $result);
    }

    public function test_get_subscription_state_intervals_subscription_canceled()
    {
        $statsStartDate = Carbon::now()->subDays(30)->startOfDay();
        $statsEndDate = Carbon::now()->startOfDay();

        $subscription = new Subscription();

        $subscription->setIsActive(false);
        $subscription->setPaidUntil(Carbon::now()->subMonth(3));
        $subscription->setCanceledOn(Carbon::now()->subMonth(2));
        $subscription->setStartDate(Carbon::now()->subMonth(5));

        $expected = [
            new SubscriptionStateInterval($statsStartDate, $statsEndDate, SubscriptionStateInterval::TYPE_CANCELED)
        ];

        $membershipStatsService = $this->app->make(MembershipStatsService::class);

        $result = $membershipStatsService->getSubscriptionStateIntervals($subscription, $statsStartDate, $statsEndDate);

        $this->assertEquals($expected, $result);
    }

    public function test_add_subscription_state_intervals_two_active()
    {
        $statsStartDate = Carbon::now()->subDays(60)->startOfDay();
        $statsEndDate = Carbon::now()->subDays(15)->startOfDay();

        $subscriptionOne = new Subscription();

        $subscriptionOne->setIsActive(true);
        $subscriptionOne->setPaidUntil(Carbon::now()->subDays(20));
        $subscriptionOne->setStartDate(Carbon::now()->subDays(50));

        $expectedIntermediary = [
            new SubscriptionStateInterval(
                $subscriptionOne->getStartDate(),
                $subscriptionOne->getPaidUntil(),
                SubscriptionStateInterval::TYPE_ACTIVE
            ),
            new SubscriptionStateInterval(
                $subscriptionOne->getPaidUntil(),
                $statsEndDate,
                SubscriptionStateInterval::TYPE_SUSPENDED
            )
        ];

        $membershipStatsService = $this->app->make(MembershipStatsService::class);

        $result = $membershipStatsService->addSubscriptionStateIntervals($subscriptionOne, $statsStartDate, $statsEndDate, []);

        $this->assertEquals($expectedIntermediary, $result);

        $subscriptionTwo = new Subscription();

        $subscriptionTwo->setIsActive(true);
        $subscriptionTwo->setPaidUntil(Carbon::now()->addDays(10)); // because second subscription has paid until further into the future, it overwrites the first subscription
        $subscriptionTwo->setStartDate(Carbon::now()->subDays(30));

        $expectedFinal = [
            new SubscriptionStateInterval(
                $subscriptionTwo->getStartDate(),
                $statsEndDate,
                SubscriptionStateInterval::TYPE_ACTIVE
            )
        ];

        $result = $membershipStatsService->addSubscriptionStateIntervals(
            $subscriptionTwo,
            $statsStartDate,
            $statsEndDate,
            $result
        );

        $this->assertEquals($expectedFinal, $result);
    }

    public function test_add_subscription_state_intervals_suspended_and_active()
    {
        $statsStartDate = Carbon::now()->subDays(90)->startOfDay();
        $statsEndDate = Carbon::now()->subDays(15)->startOfDay();

        $subscriptionOne = new Subscription();

        $subscriptionOne->setIsActive(true);
        $subscriptionOne->setStartDate(Carbon::now()->subDays(110));
        $subscriptionOne->setPaidUntil(Carbon::now()->subDays(80));

        $expectedIntermediary = [
            new SubscriptionStateInterval(
                $statsStartDate,
                $subscriptionOne->getPaidUntil(),
                SubscriptionStateInterval::TYPE_ACTIVE
            ),
            new SubscriptionStateInterval(
                $subscriptionOne->getPaidUntil(),
                $statsEndDate,
                SubscriptionStateInterval::TYPE_SUSPENDED
            )
        ];

        $membershipStatsService = $this->app->make(MembershipStatsService::class);

        $result = $membershipStatsService->addSubscriptionStateIntervals($subscriptionOne, $statsStartDate, $statsEndDate, []);

        $this->assertEquals($expectedIntermediary, $result);

        $subscriptionTwo = new Subscription();

        $subscriptionTwo->setIsActive(true);
        $subscriptionTwo->setStartDate(Carbon::now()->subDays(30));
        $subscriptionTwo->setPaidUntil(Carbon::now()->addDays(10));

        $expectedFinal = [
            new SubscriptionStateInterval(
                $statsStartDate,
                $subscriptionOne->getPaidUntil(),
                SubscriptionStateInterval::TYPE_ACTIVE
            ),
            new SubscriptionStateInterval(
                $subscriptionOne->getPaidUntil(),
                $subscriptionTwo->getStartDate(), // suspended membership state has been shorten
                SubscriptionStateInterval::TYPE_SUSPENDED
            ),
            new SubscriptionStateInterval(
                $subscriptionTwo->getStartDate(),
                $statsEndDate,
                SubscriptionStateInterval::TYPE_ACTIVE
            )
        ];

        $result = $membershipStatsService->addSubscriptionStateIntervals(
            $subscriptionTwo,
            $statsStartDate,
            $statsEndDate,
            $result
        );

        $this->assertEquals($expectedFinal, $result);
    }

    public function test_add_subscription_state_intervals_canceled_and_active()
    {
        $statsStartDate = Carbon::now()->subDays(90)->startOfDay();
        $statsEndDate = Carbon::now()->subDays(15)->startOfDay();

        $subscriptionOne = new Subscription();

        $subscriptionOne->setIsActive(true);
        $subscriptionOne->setStartDate(Carbon::now()->subDays(110));
        $subscriptionOne->setPaidUntil(Carbon::now()->subDays(80));
        $subscriptionOne->setCanceledOn(Carbon::now()->subDays(50));

        $expectedIntermediary = [
            new SubscriptionStateInterval(
                $statsStartDate,
                $subscriptionOne->getPaidUntil(),
                SubscriptionStateInterval::TYPE_ACTIVE
            ),
            new SubscriptionStateInterval(
                $subscriptionOne->getPaidUntil(),
                $subscriptionOne->getCanceledOn(),
                SubscriptionStateInterval::TYPE_SUSPENDED
            ),
            new SubscriptionStateInterval(
                $subscriptionOne->getCanceledOn(),
                $statsEndDate,
                SubscriptionStateInterval::TYPE_CANCELED
            )
        ];

        $membershipStatsService = $this->app->make(MembershipStatsService::class);

        $result = $membershipStatsService->addSubscriptionStateIntervals($subscriptionOne, $statsStartDate, $statsEndDate, []);

        $this->assertEquals($expectedIntermediary, $result);

        $subscriptionTwo = new Subscription();

        $subscriptionTwo->setIsActive(true);
        $subscriptionTwo->setStartDate(Carbon::now()->subDays(30));
        $subscriptionTwo->setPaidUntil(Carbon::now()->addDays(10));

        $expectedFinal = [
            new SubscriptionStateInterval(
                $statsStartDate,
                $subscriptionOne->getPaidUntil(),
                SubscriptionStateInterval::TYPE_ACTIVE
            ),
            new SubscriptionStateInterval(
                $subscriptionOne->getPaidUntil(),
                $subscriptionOne->getCanceledOn(),
                SubscriptionStateInterval::TYPE_SUSPENDED
            ),
            new SubscriptionStateInterval(
                $subscriptionOne->getCanceledOn(),
                $subscriptionTwo->getStartDate(), // canceled membership state has been shorten
                SubscriptionStateInterval::TYPE_CANCELED
            ),
            new SubscriptionStateInterval(
                $subscriptionTwo->getStartDate(),
                $statsEndDate,
                SubscriptionStateInterval::TYPE_ACTIVE
            )
        ];

        $result = $membershipStatsService->addSubscriptionStateIntervals(
            $subscriptionTwo,
            $statsStartDate,
            $statsEndDate,
            $result
        );

        $this->assertEquals($expectedFinal, $result);
    }
}
