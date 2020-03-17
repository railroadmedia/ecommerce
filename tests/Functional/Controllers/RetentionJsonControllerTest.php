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

    protected function setUp()
    {
        parent::setUp();

        $this->retentionStatsService = $this->app->make(RetentionStatsService::class);
    }

    public function test_pull_retention_stats()
    {
        // random date, between 16 and 30 days ago
        $testSmallDate = Carbon::now()->subDays($this->faker->numberBetween(16, 30));

        // random date, between 5 and 15 days ago
        $testBigDate = Carbon::now()->subDays($this->faker->numberBetween(5, 15));

        $testIntervalSmallDate = $testSmallDate->copy()->subDays($testSmallDate->dayOfWeek)->startOfDay();
        $testIntervalBigDate = $testBigDate->addDays(6 - $testBigDate->dayOfWeek)->endOfDay();

        // retention statistics seed intervals
        $intervals = $this->retentionStatsService->getIntervals(
            Carbon::now()->subDays($this->faker->numberBetween(35, 45)),
            Carbon::now()
        );

        $brands = [
            $this->faker->word,
            $this->faker->word,
            $this->faker->word
        ];

        $testBrand = $this->faker->randomElement($brands);

        $memberships = [
            RetentionStats::TYPE_ONE_MONTH,
            RetentionStats::TYPE_SIX_MONTHS,
            RetentionStats::TYPE_ONE_YEAR
        ];

        $expectedStatsMap = [];

        foreach ($intervals as $interval) {
            foreach ($brands as $brand) {
                foreach ($memberships as $membership) {
                    $stats = $this->fakeRetentionStats(
                        [
                            'subscription_type' => $membership,
                            'interval_start_date' => $interval['start']->toDateString(),
                            'interval_end_date' => $interval['end']->toDateString(),
                            'brand' => $brand,
                        ]
                    );

                    if (
                        $interval['start'] >= $testIntervalSmallDate
                        && $interval['start'] <= $testIntervalBigDate
                        && $interval['end'] >= $testIntervalSmallDate
                        && $interval['end'] <= $testIntervalBigDate
                        && $brand == $testBrand
                    ) {
                        if (!isset($expectedStatsMap[$brand])) {
                            $expectedStatsMap[$brand] = [];
                        }

                        if (!isset($expectedStatsMap[$brand][$membership])) {
                            $expectedStatsMap[$brand][$membership] = [
                                'start' => null,
                                'end' => null,
                                'cs' => 0,
                                'cn' => 0,
                                'ce' => 0,
                            ];
                        }

                        if (
                            !$expectedStatsMap[$brand][$membership]['start']
                            || $expectedStatsMap[$brand][$membership]['start'] > $stats['interval_start_date']
                        ) {
                            $expectedStatsMap[$brand][$membership]['start'] = $stats['interval_start_date'];
                            $expectedStatsMap[$brand][$membership]['cs'] = $stats['customers_start'];
                        }

                        if (
                            !$expectedStatsMap[$brand][$membership]['end']
                            || $expectedStatsMap[$brand][$membership]['end'] > $stats['interval_end_date']
                        ) {
                            $expectedStatsMap[$brand][$membership]['end'] = $stats['interval_end_date'];
                            $expectedStatsMap[$brand][$membership]['ce'] = $stats['customers_end'];
                        }

                        $expectedStatsMap[$brand][$membership]['cn'] += $stats['customers_new'];
                    }
                }
            }
        }

        $expectedStats = [];

        foreach ($expectedStatsMap as $brand => $brandStats) {

            foreach ($brandStats as $subType => $stat) {

                if (!$stat['cs']) {
                    continue;
                }

                $statIdString = $brand . $subType . $stat['start'] . $stat['end'];

                $id = md5($statIdString);

                $retRate = round((($stat['ce'] - $stat['cn']) / $stat['cs']) * 100, 2);

                $expectedStats[] = [
                    'id' => $id,
                    'type' => 'retentionStats',
                    'attributes' => [
                        'brand' => $brand,
                        'subscription_type' => $subType,
                        'retention_rate' => $retRate,
                        'interval_start_date' => $stat['start'],
                        'interval_end_date' => $stat['end'],
                    ]
                ];
            }
        }

        $response = $this->call(
            'GET',
            '/retention-stats',
            [
                'small_date_time' => $testSmallDate->toDateTimeString(),
                'big_date_time' => $testBigDate->toDateTimeString(),
                'brand' => $testBrand,
            ]
        );

        $this->assertEquals(
            ['data' => $expectedStats],
            $response->decodeResponseJson()
        );
    }

    public function test_pull_average_membership_end_stats()
    {
        // random date, between 16 and 30 days ago
        $testSmallDate = Carbon::now()
                            ->subDays($this->faker->numberBetween(16, 30))
                            ->startOfDay();

        // random date, between 5 and 15 days ago
        $testBigDate = Carbon::now()
                            ->subDays($this->faker->numberBetween(5, 15))
                            ->endOfDay();

        $brands = [
            $this->faker->word,
            $this->faker->word,
            $this->faker->word
        ];

        $testBrand = $this->faker->randomElement($brands);

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

            $brand = $this->faker->randomElement($brands);

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

            if (
                $brand == $testBrand
                && $startDate >= $testSmallDate
                && (
                    $paidUntil <= $testBigDate
                    || ($canceled && $canceledOn <= $testBigDate)
                )
            ) {
                // subscription is in test interval

                $subType = RetentionStats::TYPE_ONE_YEAR;

                if ($intervalType == config('ecommerce.interval_type_monthly')) {
                    $subType = $intervalCount == 1 ?
                        RetentionStats::TYPE_ONE_MONTH : RetentionStats::TYPE_SIX_MONTHS;
                }

                if (!isset($expectedStatsMap[$brand])) {
                    $expectedStatsMap[$brand] = [];
                }

                if (!isset($expectedStatsMap[$brand][$subType])) {
                    $expectedStatsMap[$brand][$subType] = [];
                }

                if (!isset($expectedStatsMap[$brand][$subType][$totalCyclesPaid])) {
                    $expectedStatsMap[$brand][$subType][$totalCyclesPaid] = 1;
                } else {
                    $expectedStatsMap[$brand][$subType][$totalCyclesPaid] += 1;
                }
            }
        }

        $expectedStats = [];

        $memberships = [
            RetentionStats::TYPE_ONE_MONTH,
            RetentionStats::TYPE_SIX_MONTHS,
            RetentionStats::TYPE_ONE_YEAR
        ];

        foreach ($expectedStatsMap as $brand => $brandStats) {

            foreach ($memberships as $subType) {

                if (isset($brandStats[$subType])) {
                    $subTypeStat = $brandStats[$subType];

                    $weightedSum = 0;
                    $sum = 0;
                    foreach ($subTypeStat as $totalCyclesPaid => $count) {
                        $weightedSum += $totalCyclesPaid * $count;
                        $sum += $count;
                    }

                    $stat = round($weightedSum / $sum, 2);

                    $id = md5($brand . $subType);

                    $expectedStats[] = [
                        'id' => $id,
                        'type' => 'averageMembershipEnd',
                        'attributes' => [
                            'brand' => $brand,
                            'subscription_type' => $subType,
                            'average_membership_end' => $stat,
                            'interval_start_date' => $testSmallDate->toDateString(),
                            'interval_end_date' => $testBigDate->toDateString(),
                        ]
                    ];
                }
            }
        }

        $response = $this->call(
            'GET',
            '/retention-stats/average-membership-end',
            [
                'small_date_time' => $testSmallDate->toDateTimeString(),
                'big_date_time' => $testBigDate->toDateTimeString(),
                'brand' => $testBrand,
            ]
        );

        $this->assertEquals(
            ['data' => $expectedStats],
            $response->decodeResponseJson()
        );
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
            $response->decodeResponseJson()
        );
    }
}
