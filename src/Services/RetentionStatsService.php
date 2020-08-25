<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
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
            $brands = config('ecommerce.available_brands', []);
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
            $membershipProductSkus = config('ecommerce.membership_product_skus', [])[$brand] ?? [];

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

//                // pull metrics
//                $subscriptionsAtStart =
//                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
//                        ->table('ecommerce_subscriptions')
//                        ->select(
//                            [
//                                "ecommerce_subscriptions.*"
//                            ]
//                        )
//                        ->whereIn(
//                            'type',
//                            [
//                                Subscription::TYPE_SUBSCRIPTION,
//                            ]
//                        )
//                        ->where('interval_type', $intervalType)
//                        ->where('interval_count', $intervalCount)
//                        ->where('brand', $brand)
//                        ->where('start_date', '<', $smallDateTime->toDateTimeString())
//                        ->where('paid_until', '>', $smallDateTime->toDateTimeString())
//                        ->where(
//                            function (Builder $builder) use ($smallDateTime) {
//                                $builder
//                                    ->where('canceled_on', '>', $smallDateTime->toDateTimeString())
//                                    ->orWhereNull('canceled_on');
//                            }
//                        )
//                        ->groupBy('ecommerce_subscriptions.user_id')
//                        ->get();
//
//                // remove people who upgraded or got access via some other method
//                $userIdsWhoRenewedOrSwitchedToLifetimeOrOther = [];
//
//                if (!empty($membershipProductSkus)) {
//                    $rowsWhoRenewedOrSwitchedToLifetimeOrOther =
//                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
//                            ->table('ecommerce_user_products')
//                            ->join(
//                                'ecommerce_products',
//                                'ecommerce_products.id',
//                                '=',
//                                'ecommerce_user_products.product_id'
//                            )
//                            ->whereIn(
//                                'ecommerce_user_products.user_id',
//                                $subscriptionsAtStart->pluck('user_id')->toArray()
//                            )
//                            ->whereIn('sku', $membershipProductSkus)
//                            ->where(
//                                function (Builder $builder) use ($bigDateTime) {
//                                    return $builder->whereNull('ecommerce_user_products.expiration_date')
//                                        ->orWhere(
//                                            'ecommerce_user_products.expiration_date',
//                                            '>',
//                                            $bigDateTime->toDateTimeString()
//                                        );
//                                }
//                            )
//                            ->whereNull('ecommerce_user_products.deleted_at')
//                            ->where(
//                                function (Builder $builder) use ($smallDateTime, $bigDateTime) {
//                                    return $builder->whereBetween(
//                                        'ecommerce_user_products.created_at',
//                                        [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
//                                    )
//                                        ->orWhereBetween(
//                                            'ecommerce_user_products.updated_at',
//                                            [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
//                                        );
//                                }
//                            )
//                            ->get(['ecommerce_user_products.*', 'ecommerce_products.sku']);
//
//                    $userIdsWhoRenewedOrSwitchedToLifetimeOrOther =
//                        $rowsWhoRenewedOrSwitchedToLifetimeOrOther->pluck('user_id');
//                }
//
////                dd($rowsWhoSwitchedToLifetimeOrOther);
////
////                var_dump($upgradedOrChangedUserIds->diff($userIdsWhoSwitchedToLifetimeOrOther));
////                $upgradedOrChangedUserIds = $upgradedOrChangedUserIds->merge($userIdsWhoSwitchedToLifetimeOrOther);
////                var_dump($upgradedOrChangedUserIds->count());
////                die();
//
//                $rowsUsersWhoFellOff = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
//                    ->table('ecommerce_subscriptions')
//                    ->select(
//                        [
//                            "ecommerce_subscriptions.*"
//                        ]
//                    )
//                    ->whereIn(
//                        'type',
//                        [
//                            Subscription::TYPE_SUBSCRIPTION,
//                        ]
//                    )
//                    ->whereIn('id', $subscriptionsAtStart->pluck('id')->toArray())
//                    ->whereNotIn(
//                        'user_id',
//                        $userIdsWhoRenewedOrSwitchedToLifetimeOrOther->toArray()
//                    )
//                    ->where(
//                        function (Builder $builder) use ($bigDateTime, $smallDateTime) {
//                            $builder
//                                ->where(
//                                    function (Builder $builder) use ($bigDateTime, $smallDateTime) {
//                                        $builder->whereBetween(
//                                            'paid_until',
//                                            [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
//                                        )
//                                            ->whereNull('canceled_on');
//                                    }
//                                )
//                                ->orWhereBetween(
//                                    'canceled_on',
//                                    [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
//                                );
//                        }
//                    )
//                    ->groupBy('user_id')
//                    ->get();
//
//                $rowsWithPayments = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
//                    ->table('ecommerce_subscriptions')
//                    ->join(
//                        'ecommerce_subscription_payments',
//                        'ecommerce_subscription_payments.subscription_id',
//                        '=',
//                        'ecommerce_subscriptions.id'
//                    )
//                    ->join(
//                        'ecommerce_payments',
//                        'ecommerce_payments.id',
//                        '=',
//                        'ecommerce_subscription_payments.payment_id'
//                    )
//                    ->whereIn(
//                        'ecommerce_subscriptions.id',
//                        $subscriptionsAtStart->pluck('id')->toArray()
//                    )
//                    ->whereNotIn(
//                        'ecommerce_subscriptions.user_id',
//                        $rowsUsersWhoFellOff->pluck('user_id')->toArray()
//                    )
//                    ->where('total_paid', '>', 0)
//
//                    // adding days to the payment end date limit accounts for the support team doing payment recovery
//                    // which they try for 30 days after a failed renewal
//                    ->whereBetween(
//                        'ecommerce_payments.created_at',
//                        [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
//                    )
//                    ->groupBy('user_id')
//                    ->get();
//
//                $totalRetained = $rowsWithPayments->pluck('user_id')
//                    ->merge($userIdsWhoRenewedOrSwitchedToLifetimeOrOther)
//                    ->unique()
//                    ->count();
//
//                var_dump($rowsUsersWhoFellOff);
//                dd($totalRetained);

                // users who had a membership payment over
                // users who should have had a membership payment
                if ($intervalType == 'year') {
                    $minusIntervalStartDate = $smallDateTime->copy()->subYears($intervalCount);
                    $minusIntervalEndDate = $bigDateTime->copy()->subYears($intervalCount);
                } elseif ($intervalType == 'month') {
                    $minusIntervalStartDate = $smallDateTime->copy()->subMonths($intervalCount);
                    $minusIntervalEndDate = $bigDateTime->copy()->subMonths($intervalCount);
                } else {
                    throw new Exception('Invalid interval.');
                }

                $usersWhoShouldHaveRenewed =
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_subscriptions')
                        ->join('ecommerce_products', 'ecommerce_products.id', '=', 'ecommerce_subscriptions.product_id')
                        ->leftJoin(
                            'ecommerce_subscription_payments',
                            'ecommerce_subscription_payments.subscription_id',
                            '=',
                            'ecommerce_subscriptions.id'
                        )
                        ->leftJoin(
                            'ecommerce_payments',
                            'ecommerce_payments.id',
                            '=',
                            'ecommerce_subscription_payments.payment_id'
                        )
//                        ->where('user_id', 294894)
                        ->where('interval_type', $intervalType)
                        ->where('interval_count', $intervalCount)
                        ->where('ecommerce_subscriptions.brand', $brand)
                        ->whereIn('sku', $membershipProductSkus)
                        ->where(
                            function (Builder $builder) use ($minusIntervalEndDate, $minusIntervalStartDate) {
                                $builder->whereBetween(
                                    'ecommerce_subscriptions.created_at',
                                    [
                                        $minusIntervalStartDate->toDateTimeString(),
                                        $minusIntervalEndDate->toDateTimeString()
                                    ]
                                )
                                    ->orWhere(
                                        function (Builder $builder) use (
                                            $minusIntervalStartDate,
                                            $minusIntervalEndDate
                                        ) {
                                            $builder->whereBetween(
                                                'ecommerce_payments.created_at',
                                                [
                                                    $minusIntervalStartDate->toDateTimeString(),
                                                    $minusIntervalEndDate->toDateTimeString()
                                                ]
                                            )
                                                ->where('ecommerce_payments.total_paid', '>', 0)
                                                ->where('ecommerce_payments.total_refunded', 0);
                                        }
                                    );

                            }
                        )
                        ->get(['user_id'])
                        ->pluck('user_id')
                        ->unique();

                // get users who cancelled or expired, they may have cancelled early
                $usersWhoCancelled = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_subscriptions')
                    ->join(
                        'ecommerce_products',
                        'ecommerce_products.id',
                        '=',
                        'ecommerce_subscriptions.product_id'
                    )
                    ->select(
                        [
                            "ecommerce_subscriptions.*"
                        ]
                    )
                    ->where('interval_type', $intervalType)
                    ->where('interval_count', $intervalCount)
                    ->where('ecommerce_subscriptions.brand', $brand)
                    ->whereIn('sku', $membershipProductSkus)
                    ->whereIn(
                        'ecommerce_subscriptions.type',
                        [
                            Subscription::TYPE_SUBSCRIPTION,
                        ]
                    )
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
                    ->get(['user_id'])
                    ->pluck('user_id')
                    ->unique();

                dd($usersWhoCancelled);

                $usersWhoShouldHaveRenewed = $usersWhoShouldHaveRenewed->merge($usersWhoCancelled);

                $usersWhoDidRenew =
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_subscriptions')
                        ->join('ecommerce_products', 'ecommerce_products.id', '=', 'ecommerce_subscriptions.product_id')
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
                        ->whereIn('user_id', $usersWhoShouldHaveRenewed->toArray())
//                        ->whereIn('user_id', [302841])
                        ->where('interval_type', $intervalType)
                        ->where('interval_count', $intervalCount)
                        ->where('ecommerce_subscriptions.brand', $brand)
                        ->whereIn('sku', $membershipProductSkus)
                        ->where(
                            function (Builder $builder) use ($smallDateTime, $bigDateTime) {
                                $builder->whereBetween(
                                    'ecommerce_payments.created_at',
                                    [
                                        $smallDateTime->toDateTimeString(),
                                        $bigDateTime->addDays(3)->toDateTimeString()
                                    ]
                                )
                                    ->where('ecommerce_payments.total_paid', '>', 0)
                                    ->where('ecommerce_payments.total_refunded', 0);
                            }
                        )
                        ->get(['user_id'])
                        ->pluck('user_id')
                        ->unique();

                $usersWhoRenewedOrSwitchedToLifetimeOrOther =
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_user_products')
                        ->join(
                            'ecommerce_products',
                            'ecommerce_products.id',
                            '=',
                            'ecommerce_user_products.product_id'
                        )
                        ->whereIn('user_id', $usersWhoShouldHaveRenewed->toArray())
//                        ->whereIn('user_id', [302841])
                        ->whereIn('sku', $membershipProductSkus)
                        ->where(
                            function (Builder $builder) use ($bigDateTime) {
                                return $builder->whereNull(
                                    'ecommerce_user_products.expiration_date'
                                )
                                    ->orWhere(
                                        'ecommerce_user_products.expiration_date',
                                        '>',
                                        $bigDateTime->copy()->addDays(15)->toDateTimeString()
                                    );
                            }
                        )
                        ->whereNull('ecommerce_user_products.deleted_at')
                        ->where(
                            function (Builder $builder) use ($smallDateTime, $bigDateTime) {
                                return $builder->whereBetween(
                                    'ecommerce_user_products.created_at',
                                    [
                                        $smallDateTime->toDateTimeString(),
                                        $bigDateTime->toDateTimeString()
                                    ]
                                )
                                    ->orWhereBetween(
                                        'ecommerce_user_products.updated_at',
                                        [
                                            $smallDateTime->toDateTimeString(),
                                            $bigDateTime->toDateTimeString()
                                        ]
                                    );
                            }
                        )
                        ->get(['user_id'])
                        ->pluck('user_id')
                        ->unique();

                $usersWhoShouldHaveRenewedCount = $usersWhoShouldHaveRenewed->count();

                $usersRetained = $usersWhoDidRenew
                    ->merge($usersWhoRenewedOrSwitchedToLifetimeOrOther)
                    ->unique();

                $usersRetainedCount = $usersRetained->count();

                if ($usersWhoShouldHaveRenewedCount > 0) {
                    $retentionRate = $usersRetainedCount / $usersWhoShouldHaveRenewedCount;
                } else {
                    $retentionRate = 1;
                }

                // create the statistic
                $retentionStatistic = new RetentionStatistic();

                $retentionStatistic->setBrand($brand);
                $retentionStatistic->setSubscriptionType($intervalName);
                $retentionStatistic->setTotalUsersInPool(0);
                $retentionStatistic->setTotalUsersWhoUpgradedOrRepurchased(0);
                $retentionStatistic->setTotalUsersWhoRenewed(0);
                $retentionStatistic->setTotalUsersWhoCanceledOrExpired(0);
                $retentionStatistic->setRetentionRate($retentionRate);

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
     * @param Request $request
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
