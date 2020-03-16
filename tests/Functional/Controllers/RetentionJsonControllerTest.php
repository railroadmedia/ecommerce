<?php

namespace Railroad\Ecommerce\Tests\Functional\Controllers;

use Carbon\Carbon;
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

    public function test_pull_stats()
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
}
