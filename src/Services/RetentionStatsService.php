<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Commands\AddPastRetentionStats;
use Railroad\Ecommerce\Entities\RetentionStats;
use Railroad\Ecommerce\Entities\Structures\AverageMembershipEnd;
use Railroad\Ecommerce\Entities\Structures\MembershipEndStats;
use Railroad\Ecommerce\Entities\Structures\RetentionStatistic;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Repositories\RetentionStatsRepository;
use Railroad\Ecommerce\Requests\AverageMembershipEndRequest;
use Railroad\Ecommerce\Requests\RetentionStatsRequest;

class RetentionStatsService
{
    /**
     * @var RetentionStatsRepository
     */
    protected $retentionStatsRepository;
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * ShippingService constructor.
     *
     * @param RetentionStatsRepository $retentionStatsRepository
     * @param DatabaseManager $databaseManager
     */
    public function __construct(
        RetentionStatsRepository $retentionStatsRepository,
        DatabaseManager $databaseManager

    )
    {
        $this->retentionStatsRepository = $retentionStatsRepository;
        $this->databaseManager = $databaseManager;
    }

    /**
     * @param RetentionStatsRequest $request
     *
     * @return RetentionStatistic[]
     * @throws Exception
     */
    public function getStats(RetentionStatsRequest $request): array
    {
        $retentionStatistics = [];

        if (!empty($request->get('brand'))) {
            $brands = [$request->get('brand')];
        } else {
            $brands = AddPastRetentionStats::BRANDS;
        }

        if (!empty($request->get('interval_type'))) {
            $intervalNames = [$request->get('interval_type')];
        } else {
            $intervalNames = ['one_month', 'one_year'];
        }

        $smallDate = $request->get(
            'small_date_time',
            Carbon::now()
                ->subDays(2)
                ->toDateTimeString()
        );

        $smallDateTime =
            Carbon::parse($smallDate)
                ->startOfDay();


        $bigDate = $request->get(
            'big_date_time',
            Carbon::yesterday()
                ->toDateTimeString()
        );

        $bigDateTime =
            Carbon::parse($bigDate)
                ->endOfDay();

        foreach ($brands as $brand) {

            foreach ($intervalNames as $intervalName) {
                if ($intervalName == 'one_month') {
                    $intervalType = 'month';
                    $intervalCount = 1;
                } elseif ($intervalName == 'one_year') {
                    $intervalType = 'year';
                    $intervalCount = 1;
                } else {
                    throw new Exception('Invalid interval.');
                }

                // pull metrics
                $subscriptionsAtStart =
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_subscriptions')
                        ->select(
                            [
                                "ecommerce_subscriptions.*"
                            ]
                        )
                        ->whereIn(
                            'type',
                            [
                                Subscription::TYPE_SUBSCRIPTION,
                            ]
                        )
                        ->where('interval_type', $intervalType)
                        ->where('interval_count', $intervalCount)
                        ->where('brand', $brand)
                        ->where('start_date', '<', $smallDateTime->toDateTimeString())
                        ->where('paid_until', '>', $smallDateTime->toDateTimeString())
                        ->where(
                            function (Builder $builder) use ($smallDateTime) {
                                $builder
                                    ->where('canceled_on', '>', $smallDateTime->toDateTimeString())
                                    ->orWhereNull('canceled_on');
                            }
                        )
                        ->groupBy('ecommerce_subscriptions.user_id')
                        ->get();

                // remove upgrades or people who purchased a different subscription
                $upgradedOrChangedUsers =
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_subscriptions')
                        ->select(
                            [
                                "ecommerce_subscriptions.*"
                            ]
                        )
                        ->whereIn(
                            'type',
                            [
                                Subscription::TYPE_SUBSCRIPTION,
                            ]
                        )
                        ->where('interval_type', 'year')
                        ->where('interval_count', 1)
                        ->where('brand', $brand)
                        ->where('start_date', '>', $smallDateTime->toDateTimeString())
                        ->where('paid_until', '>', $bigDateTime->toDateTimeString())
                        ->whereIn('user_id', $subscriptionsAtStart->pluck('user_id')->toArray())
                        ->where(
                            function (Builder $builder) use ($bigDateTime) {
                                $builder
                                    ->where('canceled_on', '>', $bigDateTime->toDateTimeString())
                                    ->orWhereNull('canceled_on');
                            }
                        )
                        ->groupBy('ecommerce_subscriptions.user_id')
                        ->get();

                $totalUsersWhoFellOff = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_subscriptions')
                    ->select(
                        [
                            "ecommerce_subscriptions.*"
                        ]
                    )
                    ->whereIn(
                        'type',
                        [
                            Subscription::TYPE_SUBSCRIPTION,
                        ]
                    )
                    ->whereIn('id', $subscriptionsAtStart->pluck('id')->toArray())
                    ->whereNotIn('user_id', $upgradedOrChangedUsers->pluck('user_id')->toArray())
                    ->where(
                        function (Builder $builder) use ($bigDateTime, $smallDateTime) {
                            $builder
                                ->where(
                                    function (Builder $builder) use ($bigDateTime, $smallDateTime) {
                                        $builder->whereBetween(
                                            'paid_until',
                                            [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
                                        )
                                            ->whereNull('canceled_on');
                                    }
                                )
                                ->orWhereBetween(
                                    'canceled_on',
                                    [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
                                );
                        }
                    )
                    ->groupBy('user_id')
                    ->get();

                $totalWithPayments = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_subscriptions')
                    ->join(
                        'ecommerce_subscription_payments',
                        'ecommerce_subscription_payments.subscription_id',
                        '=',
                        'ecommerce_subscriptions.id'
                    )
                    ->join(
                        'ecommerce_payments',
                        'ecommerce_payments.id',
                        '=',
                        'ecommerce_subscription_payments.payment_id'
                    )
                    ->whereIn(
                        'ecommerce_subscriptions.id',
                        $subscriptionsAtStart->pluck('id')->toArray()
                    )
                    ->whereNotIn(
                        'ecommerce_subscriptions.user_id',
                        $totalUsersWhoFellOff->pluck('user_id')->toArray()
                    )
                    ->where('total_paid', '>', 0)

                    // adding days to the payment end date limit accounts for the support team doing payment recovery
                    // which they try for 30 days after a failed renewal
                    ->whereBetween(
                        'ecommerce_payments.created_at',
                        [$smallDateTime->toDateTimeString(), $bigDateTime->copy()->addDays(30)->toDateTimeString()]
                    )
                    ->groupBy('user_id')
                    ->get();

                // create the statistic
                $retentionStatistic = new RetentionStatistic();

                $retentionStatistic->setBrand($brand);
                $retentionStatistic->setSubscriptionType($intervalName);
                $retentionStatistic->setTotalUsersInPool($subscriptionsAtStart->count());
                $retentionStatistic->setTotalUsersWhoUpgradedOrRepurchased($upgradedOrChangedUsers->count());
                $retentionStatistic->setTotalUsersWhoRenewed($totalWithPayments->count());
                $retentionStatistic->setTotalUsersWhoCanceledOrExpired($totalUsersWhoFellOff->count());

                // this is before any users exists, return zeros
                if ($totalWithPayments->count() + $totalUsersWhoFellOff->count() >= 0) {

                    // calculate rate
                    $paymentRetentionRate =
                        $totalWithPayments->count() / ($totalWithPayments->count() + $totalUsersWhoFellOff->count());

                    $retentionStatistic->setRetentionRate($paymentRetentionRate);
                } else {
                    $retentionStatistic->setRetentionRate(0);
                }

                $retentionStatistic->setIntervalStartDateTime($smallDateTime->toDateTimeString());
                $retentionStatistic->setIntervalEndDateTime($bigDateTime->toDateTimeString());

                // debugging/test code, do not remove
//                var_dump($retentionStatistic);

                $retentionStatistics[] = $retentionStatistic;
            }
        }

        return $retentionStatistics;
    }

    /**
     * @param AverageMembershipEndRequest $request
     *
     * @return array
     */
    public function getAverageMembershipEnd(AverageMembershipEndRequest $request): array
    {
        $smallDate = $request->has('small_date_time') ?
            Carbon::parse($request->get('small_date_time'))
                ->startOfDay() :
            null;

        $bigDate = $request->has('big_date_time') ?
            Carbon::parse($request->get('big_date_time'))
                ->endOfDay() :
            null;

        $intervalType = $request->get('interval_type');
        $brand = $request->get('brand');

        $result = [];

        $intervalTypes = [
            RetentionStats::TYPE_ONE_MONTH => [
                'type' => config('ecommerce.interval_type_monthly'),
                'count' => 1,
            ],
            RetentionStats::TYPE_SIX_MONTHS => [
                'type' => config('ecommerce.interval_type_monthly'),
                'count' => 6,
            ],
            RetentionStats::TYPE_ONE_YEAR => [
                'type' => config('ecommerce.interval_type_yearly'),
                'count' => 1,
            ]
        ];

        foreach ($intervalTypes as $type => $typeDetails) {

            if ($intervalType == null || $type == $intervalType) {

                $rawStats = $this->retentionStatsRepository->getAverageMembershipEnd(
                    $typeDetails['type'],
                    $typeDetails['count'],
                    $smallDate,
                    $bigDate,
                    $brand
                );

                $stats = [];

                foreach ($rawStats as $rawStat) {
                    $statBrand = $rawStat['brand'];
                    if (!isset($stats[$statBrand])) {
                        $stats[$statBrand] = [
                            'weightedSum' => 0,
                            'sum' => 0,
                        ];
                    }
                    $stats[$statBrand]['weightedSum'] += $rawStat['totalCyclesPaid'] * $rawStat['count'];
                    $stats[$statBrand]['sum'] += $rawStat['count'];
                }

                foreach ($stats as $statBrand => $brandStats) {

                    $id = md5($statBrand . $type);

                    $stat = new AverageMembershipEnd($id);

                    $stat->setBrand($statBrand);
                    $stat->setSubscriptionType($type);
                    $stat->setAverageMembershipEnd(round($brandStats['weightedSum'] / $brandStats['sum'], 2));
                    $stat->setIntervalStartDate($smallDate);
                    $stat->setIntervalEndDate($bigDate);

                    $result[] = $stat;
                }
            }
        }

        return $result;
    }

    /**
     * @param AverageMembershipEndRequest $request
     *
     * @return array
     */
    public function getMembershipEndStats(Request $request): array
    {
        $smallDate = $request->has('small_date_time') ?
            Carbon::parse($request->get('small_date_time'))
                ->startOfDay() :
            null;

        $bigDate = $request->has('big_date_time') ?
            Carbon::parse($request->get('big_date_time'))
                ->endOfDay() :
            null;

        $intervalType = $request->get('interval_type');
        $brand = $request->get('brand');

        $result = [];

        $intervalTypes = [
            RetentionStats::TYPE_ONE_MONTH => [
                'type' => config('ecommerce.interval_type_monthly'),
                'count' => 1,
            ],
            RetentionStats::TYPE_SIX_MONTHS => [
                'type' => config('ecommerce.interval_type_monthly'),
                'count' => 6,
            ],
            RetentionStats::TYPE_ONE_YEAR => [
                'type' => config('ecommerce.interval_type_yearly'),
                'count' => 1,
            ]
        ];

        foreach ($intervalTypes as $type => $typeDetails) {
            if ($intervalType == null || $type == $intervalType) {
                $rawStats = $this->retentionStatsRepository->getAverageMembershipEnd(
                    $typeDetails['type'],
                    $typeDetails['count'],
                    $smallDate,
                    $bigDate,
                    $brand
                );

                foreach ($rawStats as $rawStat) {

                    $id = md5($rawStat['brand'] . $type . $rawStat['totalCyclesPaid']);

                    $stat = new MembershipEndStats($id);

                    $stat->setBrand($rawStat['brand']);
                    $stat->setSubscriptionType($type);
                    $stat->setCyclesPaid($rawStat['totalCyclesPaid']);
                    $stat->setCount($rawStat['count']);
                    $stat->setIntervalStartDate($smallDate);
                    $stat->setIntervalEndDate($bigDate);

                    $result[] = $stat;
                }
            }
        }

        return $result;
    }

    private function normalizeToSumPercent(array $range)
    {
        $arraySum = array_sum($range);

        $newRange = [];

        foreach ($range as $i => $v) {
            $newRange[$i] = round($v / $arraySum, 4) * 100;
        }

        return array_filter($newRange);
    }

    private function seasonalityCurve(Carbon $smallDateTime, Carbon $bigDateTime)
    {
        $startDayOfYear = $smallDateTime->copy()->startOfYear()->diffInDays($smallDateTime);
        $endDayOfYear = $startDayOfYear + $smallDateTime->diffInDays($bigDateTime);

        // get how many were scheduled to renew on these days of the year
        // figure out how many actually renewed during period
        $seasonalityCurveData =
            $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                ->table('ecommerce_subscriptions')
                ->select(
                    [
                        $this->databaseManager->raw("DAYOFYEAR(start_date) doy"),
                        $this->databaseManager->raw("COUNT(*) as count")
                    ]
                )
                ->whereIn(
                    'type',
                    [
                        Subscription::TYPE_SUBSCRIPTION,
                    ]
                )
                ->where('interval_type', 'year')
                ->where('interval_count', 1)
                ->where('brand', 'drumeo')
                ->where('created_at', '>', Carbon::now()->subYears(2))
                ->where('created_at', '<', Carbon::now()->subYears(1))
                ->groupBy('doy')
                ->orderBy('doy')
                ->get();


        // normalize the curve
        $seasonalityCurveNormalizedArray =
            $this->normalizeToSumPercent(
                array_combine(
                    $seasonalityCurveData->pluck('doy')->toArray(),
                    $seasonalityCurveData->pluck('count')->toArray()
                )
            );

        $totalInPeriod = 0;

        $daysToIntervalStart = $smallDateTime->copy()->startOfYear()->diffInDays($smallDateTime);
        $daysToIntervalEnd = $bigDateTime->copy()->startOfYear()->diffInDays($bigDateTime) + 1;

        var_dump('$daysToIntervalStart=' . $daysToIntervalStart);
        var_dump('$daysToIntervalEnd=' . $daysToIntervalEnd);
        var_dump('$bigDateTime=' . $bigDateTime);

        $dayIterator = 1;
        $daysInPeriodArray = [];

        while (count($daysInPeriodArray) < 365 && $dayIterator <= 365) {

            // if the range crosses over the 365/0 mark
            if ($daysToIntervalStart > $daysToIntervalEnd) {
                if ($dayIterator >= $daysToIntervalStart || $dayIterator <= $daysToIntervalEnd) {
                    $daysInPeriodArray[] = $dayIterator;
                }
            } else {
                if ($dayIterator >= $daysToIntervalStart && $dayIterator <= $daysToIntervalEnd) {
                    $daysInPeriodArray[] = $dayIterator;
                }
            }

            $dayIterator++;
        }

        foreach ($seasonalityCurveNormalizedArray as $dayOfYear => $seasonalityPercent) {
            if (in_array($dayOfYear, $daysInPeriodArray)) {
                $totalInPeriod += $seasonalityPercent;
            }
        }

        if ($smallDateTime->diffInDays($bigDateTime) > 365) {
            $totalInPeriod = 100;
        }

        var_dump("totalInPeriod = $totalInPeriod");
        var_dump("totalOutOfPeriod = " . (100 - $totalInPeriod));
    }
}
