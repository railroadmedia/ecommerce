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
        ini_set('xdebug.var_display_max_depth', '100');
        ini_set('xdebug.var_display_max_children', '2560');
        ini_set('xdebug.var_display_max_data', '10240');

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
//                    continue;
                } elseif ($intervalName == 'one_year') {
                    $intervalType = 'year';
                    $intervalCount = 1;
                } else {
                    throw new Exception('Invalid interval.');
                }

                $totalRetained = 0;
                $totalNotRetained = 0;

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

                // For each of these failed payments, we need to make sure this is the first failed payment
                // in the stack for a subscription. For new data the payment_attempt = 0 check does this but
                // if support manually tries to re-bill users the payment_attempt doesn't increment. This leads
                // to a single instance of a failed membership effecting multiple months retention rate.

                $allSubscriptionsFailedPayments = $connection->table('ecommerce_payments')
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
                    ->whereIn('ecommerce_subscriptions.id', $failedPaymentRows->pluck('subscription_id'))
                    ->orderBy('ecommerce_payments.created_at', 'desc')
                    ->get();

                $allSubscriptionsFailedPaymentsGrouped = $allSubscriptionsFailedPayments->groupBy('subscription_id');

                foreach ($failedPaymentRows as $failedPaymentRowIndex => $failedPaymentRow) {
                    $allThisSubsPayments =
                        $allSubscriptionsFailedPaymentsGrouped[$failedPaymentRow->subscription_id] ?? [];

                    foreach ($allThisSubsPayments as $thisSubsPayment) {
                        if (Carbon::parse($thisSubsPayment->created_at) <
                            Carbon::parse($failedPaymentRow->created_at) &&
                            $thisSubsPayment->status == Payment::STATUS_FAILED) {

//                            if ($failedPaymentRow->user_id == 151665) {
//                                var_dump($thisSubsPayment);
//                                dd($failedPaymentRow);
//                            }

                            unset($failedPaymentRows[$failedPaymentRowIndex]);
                        }

                        if (Carbon::parse($thisSubsPayment->created_at) <
                            Carbon::parse($failedPaymentRow->created_at) &&
                            $thisSubsPayment->status != Payment::STATUS_FAILED) {
                            break;
                        }
                    }
                }

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

                $expiredOrCancelledSubscriptionUserIds = $expiredOrCancelledSubscriptionRows->pluck('user_id');

                // We need an array of every user ID and the date which their membership failed
                // (failed payment, cancelled, or expired) so we can check if they were retained after that date.
                // Array format is: user_id => failure_carbon_datetime
                $userFailureDates = [];

                foreach ($failedPaymentRows as $failedPaymentRow) {

//                    if ($failedPaymentRow->user_id == 367708) {
//                        var_dump($failedPaymentRow);
//                    }

                    if (empty($userFailureDates[$failedPaymentRow->user_id]) ||
                        Carbon::parse($failedPaymentRow->created_at) < $userFailureDates[$failedPaymentRow->user_id]) {
                        $userFailureDates[$failedPaymentRow->user_id] = Carbon::parse($failedPaymentRow->created_at);
                    }
                }

                foreach ($expiredOrCancelledSubscriptionRows as $expiredOrCancelledSubscriptionRow) {
//                    if ($expiredOrCancelledSubscriptionRow->user_id == 367708) {
//                        var_dump($expiredOrCancelledSubscriptionRow);
//                        var_dump($dateToUse);
//                    }

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
                }

//                dd($userFailureDates);

                // Now that we have a full list of anyone who may have not been retained, we'll filter out people
                // who upgraded, or had their payment recovered shortly after it failed via the automated re-billing
                // system. We will also remove people who purchased a new sub within 28 days or had any successful
                // membership payment within the next 28 days (because of the retention teams efforts).
                // Because of this we must limit admins from pulling data after 30 days before the current time.
                // For example if its currently July 15th, we must only report up to June 15th, no later.

                $userIdsPotentiallyNotRetained =
                    $failedPaymentUserIds->merge($expiredOrCancelledSubscriptionUserIds)->unique()->sort();


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
                                return $builder->whereNull('ecommerce_user_products.expiration_date')
                                    ->orWhere(
                                        'ecommerce_user_products.expiration_date',
                                        '>',
                                        $bigDateTime->toDateTimeString()
                                    );
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

//                    if ($userId == 149769) {
//                        var_dump($userFailureDates[$userId]);
//                        var_dump($orderItemDataRows);
//                    }

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

//                    if ($userId == 149769) {
//                        var_dump($userFailureDates[$userId]);
//                        var_dump($successfulPaymentDataRows);
//                    }

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

//                if ($intervalType == 'year') {
//                    var_dump(
//                        $allRetainedUserIds->count() / ($allRetainedUserIds->count() + $allNotRetainedUserIds->count())
//                    );
//                    var_dump($allRetainedUserIds);
//                    var_dump($allNotRetainedUserIds);
//                    die();
//                }

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

                // debugging/test code, do not remove
//                var_dump($retentionStatistic);

                $retentionStatistics[] = $retentionStatistic;
            }
        }

        return $retentionStatistics;
    }


    /**
     * @param RetentionStatsRequest $request
     *
     * @return RetentionStatistic[]
     * @throws Exception
     */
    public function _getStats(RetentionStatsRequest $request): array
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

                // get all users who had a failed payment on a membership subscription
                // filter out ones that had a successful payment shortly after
                // get all users who cancelled or expired but did not have a failed payment
                // filter out users if they start another sub or upgraded in the same period

                // get all users who successfully renewed with a payment
                // get all users who upgraded, which should count as a successful renewal

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

//                dd($usersWhoCancelled);

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