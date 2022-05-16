<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\RetentionStats;
use Railroad\Ecommerce\Services\RetentionStatsService;
use Railroad\Ecommerce\Tests\EcommerceTestCase;

class RetentionJsonControllerTest extends EcommerceTestCase
{
    /**
     * @var RetentionStatsService
     */
    private $retentionStatsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->retentionStatsService = $this->app->make(RetentionStatsService::class);
    }

    public function test_pull_membership_end_stats()
    {
        // random date, between 16 and 30 days ago
        $testSmallDate = Carbon::now()
                            ->subDays($this->faker->numberBetween(16, 30))
                            ->startOfDay();

        // random date, between 5 and 15 days ago
        $testBigDate = Carbon::now()
                            ->subDays($this->faker->numberBetween(5, 15))
                            ->endOfDay();

        $brand = $this->faker->word;
        $testIntervalType = RetentionStats::TYPE_ONE_MONTH;

        $expectedStatsMap = [];

        for ($i=0; $i < 200; $i++) {
            // seed a subscription
            $canceled = $this->faker->randomElement([false, true]); // 1 in 2 chance to be canceled

            $startDate = $paidUntil = $canceledOn = null;
            $subDays = $this->faker->numberBetween(1, 60);
            $startDate = $paidUntil = Carbon::now()
                    ->subDays($subDays + 3);

            if ($canceled) {
                $paidUntil = Carbon::now()
                    ->addMonths($this->faker->numberBetween(1, 5));
                $canceledOn = Carbon::now()
                    ->subDays($subDays);
            } else {
                $paidUntil = Carbon::now()
                    ->subDays($subDays);
            }

            $intervalType = $this->faker->randomElement(
                [
                    config('ecommerce.interval_type_monthly'),
                    config('ecommerce.interval_type_yearly')
                ]
            );

            $intervalCount = $intervalType == config('ecommerce.interval_type_yearly') ?
                1 : $this->faker->randomElement([1, 6]);

            $totalCyclesPaid = $this->faker->numberBetween(0, 5);

            $product = $this->fakeProduct([
                'type' => Product::TYPE_DIGITAL_SUBSCRIPTION,
                'subscription_interval_type' => config('ecommerce.interval_type_yearly'),
                'subscription_interval_count' => 1,
                'brand' => $brand,
            ]);

            $subscription = $this->fakeSubscription([
                'brand' => $brand,
                'product_id' => $product['id'],
                'payment_method_id' => null,
                'start_date' => $startDate,
                'paid_until' => $paidUntil,
                'canceled_on' => $canceledOn,
                'interval_count' => $intervalCount,
                'interval_type' => $intervalType,
                'total_cycles_paid' => $totalCyclesPaid,
            ]);

            $subType = RetentionStats::TYPE_ONE_YEAR;

            if ($intervalType == config('ecommerce.interval_type_monthly')) {
                $subType = $intervalCount == 1 ?
                    RetentionStats::TYPE_ONE_MONTH : RetentionStats::TYPE_SIX_MONTHS;
            }

            if (
                $testIntervalType == $subType
                && $startDate >= $testSmallDate
                && (
                    $paidUntil <= $testBigDate
                    || ($canceled && $canceledOn <= $testBigDate)
                )
            ) {
                // subscription is in test interval & test type

                if (!isset($expectedStatsMap[$totalCyclesPaid])) {
                    $expectedStatsMap[$totalCyclesPaid] = 1;
                } else {
                    $expectedStatsMap[$totalCyclesPaid] += 1;
                }
            }
        }

        ksort($expectedStatsMap);

        $expectedStats = [];

        foreach ($expectedStatsMap as $totalCyclesPaid => $count) {

            $id = md5($brand . $testIntervalType . $totalCyclesPaid);

            $expectedStats[] = [
                'id' => $id,
                'type' => 'membershipEndStats',
                'attributes' => [
                    'brand' => $brand,
                    'subscription_type' => $testIntervalType,
                    'cycles_paid' => $totalCyclesPaid,
                    'count' => $count,
                    'interval_start_date' => $testSmallDate->toDateString(),
                    'interval_end_date' => $testBigDate->toDateString(),
                ]
            ];
        }

        $response = $this->call(
            'GET',
            '/retention-stats/membership-end-stats',
            [
                'small_date_time' => $testSmallDate->toDateTimeString(),
                'big_date_time' => $testBigDate->toDateTimeString(),
                'brand' => $brand,
                'interval_type' => $testIntervalType,
            ]
        );

        $this->assertEquals(
            ['data' => $expectedStats],
            $response->json()
        );
    }
}
