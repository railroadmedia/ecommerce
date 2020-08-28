<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\RetentionStats;
use Railroad\Ecommerce\Entities\Structures\MembershipEndStats;
use Railroad\Ecommerce\Entities\Structures\RetentionStatistic;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Repositories\RetentionStatsRepository;
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
        $debugMode = $request->has('debug');
        $debugUserId = $request->get('debug_user_id', 0);

        if ($debugMode) {
            ini_set('xdebug.var_display_max_depth', '100');
            ini_set('xdebug.var_display_max_children', '2560');
            ini_set('xdebug.var_display_max_data', '10240');
        }

        $connection = $this->databaseManager->connection(config('ecommerce.database_connection_name'));

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

                $allFailedSubscriptionIds = collect();

                // get all users who had a failed payment on a membership subscription
                // filter out ones that had a successful payment shortly after
                // get all users who cancelled or expired but did not have a failed payment
                // filter out users if they start another sub or upgraded in the same period

                $failedPaymentRows = $connection->table('ecommerce_payments')
                    ->select(
                        [
                            'ecommerce_payments.*',
                            'ecommerce_subscriptions.id as subscription_id',
                            'ecommerce_subscriptions.user_id',
                            'ecommerce_subscriptions.canceled_on'
                        ]
                    )
                    ->join(
                        'ecommerce_subscription_payments',
                        'ecommerce_subscription_payments.payment_id',
                        '=',
                        'ecommerce_payments.id'
                    )
                    ->join(
                        'ecommerce_subscriptions',
                        'ecommerce_subscriptions.id',
                        '=',
                        'ecommerce_subscription_payments.subscription_id'
                    )
                    ->whereBetween(
                        'ecommerce_payments.created_at',
                        [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
                    )
                    ->where('ecommerce_payments.total_paid', 0)
                    ->where('ecommerce_payments.status', Payment::STATUS_FAILED)
                    ->where('ecommerce_payments.gateway_name', $brand)
                    ->where('ecommerce_payments.attempt_number', 0)
                    ->where('ecommerce_subscriptions.type', Subscription::TYPE_SUBSCRIPTION)
                    ->where('ecommerce_subscriptions.brand', $brand)
                    ->where('ecommerce_subscriptions.interval_type', $intervalType)
                    ->where('ecommerce_subscriptions.interval_count', $intervalCount)
                    ->whereNotNull('ecommerce_subscriptions.product_id')
                    ->get();

                $allFailedSubscriptionIds =
                    $allFailedSubscriptionIds->merge($failedPaymentRows->pluck('subscription_id'));

                $failedPaymentUserIds = $failedPaymentRows->pluck('user_id');

                $expiredOrCancelledSubscriptionRows = $connection->table('ecommerce_subscriptions')
                    ->select(['ecommerce_subscriptions.*'])
                    ->where('ecommerce_subscriptions.type', Subscription::TYPE_SUBSCRIPTION)
                    ->where('ecommerce_subscriptions.brand', $brand)
                    ->where('ecommerce_subscriptions.interval_type', $intervalType)
                    ->where('ecommerce_subscriptions.interval_count', $intervalCount)
                    ->where(
                        function (Builder $builder) use ($bigDateTime, $smallDateTime) {
                            $builder
                                ->where(
                                    function (Builder $builder) use ($bigDateTime, $smallDateTime) {
                                        $builder->whereBetween(
                                            'ecommerce_subscriptions.paid_until',
                                            [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
                                        )
                                            ->whereNull('canceled_on');
                                    }
                                )
                                ->orWhereBetween(
                                    'ecommerce_subscriptions.canceled_on',
                                    [$smallDateTime->toDateTimeString(), $bigDateTime->toDateTimeString()]
                                );
                        }
                    )
                    ->get();

                $allFailedSubscriptionIds =
                    $allFailedSubscriptionIds->merge($expiredOrCancelledSubscriptionRows->pluck('id'));

                $expiredOrCancelledSubscriptionUserIds = $expiredOrCancelledSubscriptionRows->pluck('user_id');

                // We need an array of every user ID and the date which their membership failed
                // (failed payment, cancelled, or expired) so we can check if they were retained after that date.
                // Array format is: user_id => failure_carbon_datetime
                $userFailureDates = [];

                foreach ($failedPaymentRows as $failedPaymentRow) {

                    if ($debugMode) {
                        if ($failedPaymentRow->user_id == $debugUserId) {
                            var_dump($failedPaymentRow);
                        }
                    }

                    if (empty($userFailureDates[$failedPaymentRow->user_id]) ||
                        Carbon::parse($failedPaymentRow->created_at) < $userFailureDates[$failedPaymentRow->user_id]) {
                        $userFailureDates[$failedPaymentRow->user_id] = Carbon::parse($failedPaymentRow->created_at);
                    }
                }

                foreach ($expiredOrCancelledSubscriptionRows as $expiredOrCancelledSubscriptionRow) {
                    $dateToUse = null;

                    if (!empty($expiredOrCancelledSubscriptionRow->canceled_on) &&
                        Carbon::parse($expiredOrCancelledSubscriptionRow->canceled_on) <
                        Carbon::parse($expiredOrCancelledSubscriptionRow->paid_until)) {
                        $dateToUse = Carbon::parse($expiredOrCancelledSubscriptionRow->canceled_on);
                    } else {
                        $dateToUse = Carbon::parse($expiredOrCancelledSubscriptionRow->paid_until);
                    }

                    if (empty($userFailureDates[$expiredOrCancelledSubscriptionRow->user_id]) ||
                        $dateToUse < $userFailureDates[$expiredOrCancelledSubscriptionRow->user_id]
                    ) {
                        $userFailureDates[$expiredOrCancelledSubscriptionRow->user_id] = $dateToUse;
                    }

                    if ($debugMode) {
                        if ($expiredOrCancelledSubscriptionRow->user_id == $debugUserId) {
                            var_dump($expiredOrCancelledSubscriptionRow);
                            var_dump($dateToUse);
                        }
                    }
                }

                // We need to look at all the failed subscriptions and make sure that they don't have failed payments
                // lead up to the cancel or expiry date. For example if they is a failed payment in Jan, then another
                // in Feb (support is trying to re-bill them). Then support cancels the sub in March. We want this
                // sub to affect the Jan retention number, not March. If we don't filter like this they will count
                // for both months which is essentially duplicating their representation in the stats.
                $allFailedSubscriptionIds = $allFailedSubscriptionIds->unique();

                $allFailedSubscriptions = $connection->table('ecommerce_payments')
                    ->select(
                        [
                            'ecommerce_payments.*',
                            'ecommerce_subscriptions.id as subscription_id',
                            'ecommerce_subscriptions.user_id',
                            'ecommerce_subscriptions.paid_until',
                            'ecommerce_subscriptions.canceled_on',
                        ]
                    )
                    ->join(
                        'ecommerce_subscription_payments',
                        'ecommerce_subscription_payments.payment_id',
                        '=',
                        'ecommerce_payments.id'
                    )
                    ->join(
                        'ecommerce_subscriptions',
                        'ecommerce_subscriptions.id',
                        '=',
                        'ecommerce_subscription_payments.subscription_id'
                    )
                    ->whereIn('ecommerce_subscriptions.id', $allFailedSubscriptionIds)
                    ->orderBy('ecommerce_payments.created_at', 'desc')
                    ->get();

                $allFailedSubscriptionsGrouped = $allFailedSubscriptions->groupBy('subscription_id');

                foreach ($allFailedSubscriptions as $allFailedSubscriptionIndex => $allFailedSubscription) {

                    $allThisSubsPayments =
                        $allFailedSubscriptionsGrouped[$allFailedSubscription->subscription_id] ?? [];

                    $dateToUse = null;

                    if (!empty($allFailedSubscription->canceled_on) &&
                        Carbon::parse($allFailedSubscription->canceled_on) <
                        Carbon::parse($allFailedSubscription->paid_until)) {
                        $dateToUse = Carbon::parse($allFailedSubscription->canceled_on);
                    } else {
                        $dateToUse = Carbon::parse($allFailedSubscription->paid_until);
                    }

                    if (empty($dateToUse)) {
                        continue;
                    }

                    foreach ($allThisSubsPayments as $thisSubsPayment) {
                        if (Carbon::parse($thisSubsPayment->created_at) <
                            $dateToUse &&
                            $thisSubsPayment->status == Payment::STATUS_FAILED &&
                            Carbon::parse($thisSubsPayment->created_at) < $smallDateTime) {

                            if ($debugMode) {
//                                if ($failedPaymentRow->user_id == $debugUserId) {
//                                    var_dump($thisSubsPayment);
////                                    dd($failedPaymentRow);
//                                }
                            }

                            unset($userFailureDates[$allFailedSubscription->user_id]);
                            break;
                        }

                        if (Carbon::parse($thisSubsPayment->created_at) <
                            Carbon::parse($dateToUse) &&
                            $thisSubsPayment->status != Payment::STATUS_FAILED) {
                            break;
                        }
                    }
                }

                // if the failure date is outside the bounds of the reporting timeframe they should also be removed
                foreach ($userFailureDates as $userId => $userFailureDate) {
                    if ($userFailureDate < $smallDateTime || $userFailureDate > $bigDateTime) {
                        unset($userFailureDates[$userId]);
                    }
                }

                // Now that we have a full list of anyone who may have not been retained, we'll filter out people
                // who upgraded, or had their payment recovered shortly after it failed via the automated re-billing
                // system. We will also remove people who purchased a new sub within 28 days or had any successful
                // membership payment within the next 28 days (because of the retention teams efforts).
                // Because of this we must limit admins from pulling data after 30 days before the current time.
                // For example if its currently July 15th, we must only report up to June 15th, no later.

                $userIdsPotentiallyNotRetained =
                    $failedPaymentUserIds->merge($expiredOrCancelledSubscriptionUserIds)->unique()->sort();

                // remove all user ids who do not have a failure date
                foreach ($userIdsPotentiallyNotRetained as $userIdPotentiallyNotRetainedIndex =>
                         $userIdPotentiallyNotRetained) {
                    if (empty($userFailureDates[$userIdPotentiallyNotRetained])) {
                        unset($userIdsPotentiallyNotRetained[$userIdPotentiallyNotRetainedIndex]);
                    }
                }

                // Get all of these users who got lifetime access or other access anytime before the time period.
                $rowsUsersWhoHadLifetimeBeforeHand =
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_user_products')
                        ->join(
                            'ecommerce_products',
                            'ecommerce_products.id',
                            '=',
                            'ecommerce_user_products.product_id'
                        )
                        ->whereIn(
                            'ecommerce_user_products.user_id',
                            $userIdsPotentiallyNotRetained
                        )
                        ->whereIn('sku', $membershipProductSkus)
                        ->where(
                            function (Builder $builder) use ($bigDateTime) {
                                return $builder->whereNull('ecommerce_user_products.expiration_date');
                            }
                        )
                        ->whereNull('ecommerce_user_products.deleted_at')
                        ->where('ecommerce_user_products.created_at', '<', $smallDateTime->toDateTimeString())
                        ->get(['ecommerce_user_products.*', 'ecommerce_products.sku']);

                $userIdsWhoHadLifetimeBeforeHand = $rowsUsersWhoHadLifetimeBeforeHand->pluck('user_id')->unique();

                // remove these lifetime owners from the list
                $userIdsPotentiallyNotRetained = $userIdsPotentiallyNotRetained->diff($userIdsWhoHadLifetimeBeforeHand);

                // find people who upgraded or re-ordered
                $usersOrdersDuringPeriod = $connection->table('ecommerce_orders')
                    ->select(
                        [
                            'ecommerce_orders.created_at as order_created_at',
                            'ecommerce_orders.user_id as user_id',
                            'ecommerce_order_items.*'
                        ]
                    )
                    ->join('ecommerce_order_items', 'ecommerce_order_items.order_id', '=', 'ecommerce_orders.id')
                    ->join('ecommerce_products', 'ecommerce_products.id', '=', 'ecommerce_order_items.product_id')
                    ->join('ecommerce_order_payments', 'ecommerce_order_payments.order_id', '=', 'ecommerce_orders.id')
                    ->join('ecommerce_payments', 'ecommerce_payments.id', '=', 'ecommerce_order_payments.payment_id')
                    ->where('ecommerce_orders.brand', $brand)
                    ->where(
                        function (Builder $builder) {
                            $builder->where('ecommerce_orders.total_paid', '=', DB::raw('ecommerce_orders.total_due'))
                                ->orWhere('ecommerce_orders.total_paid', '>', 0);
                        }
                    )
                    ->whereIn('ecommerce_orders.user_id', $userIdsPotentiallyNotRetained)
                    ->whereIn('ecommerce_products.sku', $membershipProductSkus)
                    ->where('ecommerce_payments.total_refunded', '<', DB::raw('ecommerce_payments.total_paid'))
                    ->whereBetween(
                        'ecommerce_orders.created_at',
                        [$smallDateTime->toDateTimeString(), $bigDateTime->copy()->addDays(28)->toDateTimeString()]
                    )
                    ->get();

                $userIdsWhoUpgradedViaNewOrder = collect();

                foreach ($usersOrdersDuringPeriod->groupBy('user_id') as $userId => $orderItemDataRows) {

                    if ($debugMode) {
                        if ($userId == $debugUserId) {
                            var_dump($userFailureDates[$userId]);
                            var_dump($orderItemDataRows);
                        }
                    }

                    foreach ($orderItemDataRows as $orderItemDataRow) {

                        // we must add a minute because when a user does upgrades sometimes there is a few second
                        // delay between when the DB rows are updated/created
                        if (!empty($userFailureDates[$userId]) &&
                            Carbon::parse($orderItemDataRow->order_created_at)->addMinute() >
                            $userFailureDates[$userId]) {

                            // they upgraded
                            $userIdsWhoUpgradedViaNewOrder[] = $userId;
                        }
                    }
                }

//                dd($userIdsWhoUpgradedViaNewOrder);

                // get all users who successfully renewed with a payment
                $successfulPaymentRows = $connection->table('ecommerce_payments')
                    ->select(['ecommerce_payments.*', 'ecommerce_subscriptions.user_id'])
                    ->join(
                        'ecommerce_subscription_payments',
                        'ecommerce_subscription_payments.payment_id',
                        '=',
                        'ecommerce_payments.id'
                    )
                    ->join(
                        'ecommerce_subscriptions',
                        'ecommerce_subscriptions.id',
                        '=',
                        'ecommerce_subscription_payments.subscription_id'
                    )
                    ->whereBetween(
                        'ecommerce_payments.created_at',
                        [$smallDateTime->toDateTimeString(), $bigDateTime->copy()->addDays(28)->toDateTimeString()]
                    )
                    ->where('ecommerce_payments.total_refunded', 0)
                    ->where('ecommerce_payments.total_paid', '>', 0)
                    ->where('ecommerce_payments.status', Payment::STATUS_PAID)
                    ->where('ecommerce_payments.type', '!=', Payment::TYPE_INITIAL_ORDER)
                    ->where('ecommerce_payments.gateway_name', $brand)
                    ->where('ecommerce_subscriptions.type', Subscription::TYPE_SUBSCRIPTION)
                    ->where('ecommerce_subscriptions.brand', $brand)
                    ->where('ecommerce_subscriptions.interval_type', $intervalType)
                    ->where('ecommerce_subscriptions.interval_count', $intervalCount)
                    ->where('ecommerce_subscriptions.created_at', '<', $smallDateTime->toDateTimeString())
                    ->whereNotNull('ecommerce_subscriptions.product_id')
                    ->get();

                $userIdsWhoRenewed = collect();

                foreach ($successfulPaymentRows->groupBy('user_id') as $userId => $successfulPaymentDataRows) {

                    if ($debugMode) {
                        if ($userId == $debugUserId) {
                            var_dump($userFailureDates[$userId] ?? null);
                            var_dump($successfulPaymentDataRows);
                        }
                    }

                    foreach ($successfulPaymentDataRows as $successfulPaymentDataRow) {

                        // we must add a minute buffer because upgrades can have the same or small difference in dates
                        // due to order processing
                        if (!empty($userFailureDates[$userId]) &&
                            Carbon::parse($successfulPaymentDataRow->created_at)->addMinute(1) >
                            $userFailureDates[$userId]) {

                            // they upgraded
                            $userIdsWhoRenewed[] = $userId;
                        }

                        if (empty($userFailureDates[$userId]) &&
                            Carbon::parse($successfulPaymentDataRow->created_at) > $smallDateTime &&
                            Carbon::parse($successfulPaymentDataRow->created_at) < $bigDateTime) {
                            $userIdsWhoRenewed[] = $userId;
                        }
                    }
                }

                $userIdsWhoRenewed = $userIdsWhoRenewed->unique();

                $allRetainedUserIds = $userIdsWhoUpgradedViaNewOrder->merge($userIdsWhoRenewed)->unique()->sort();
                $allNotRetainedUserIds = $userIdsPotentiallyNotRetained->diff($allRetainedUserIds)->sort();

                if ($debugMode) {
                    var_dump(
                        $allRetainedUserIds->count() /
                        ($allRetainedUserIds->count() + $allNotRetainedUserIds->count())
                    );
                    var_dump($allRetainedUserIds);
                    var_dump($allNotRetainedUserIds);
                }

                $retentionRate =
                    $allRetainedUserIds->count() / ($allRetainedUserIds->count() + $allNotRetainedUserIds->count());

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

                if ($debugMode) {
                    var_dump($retentionStatistic);
                    die();
                }

                $retentionStatistics[] = $retentionStatistic;
            }
        }

        return $retentionStatistics;
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