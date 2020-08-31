<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Entities\MembershipStats;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\MembershipStatsService;
use Throwable;

class AddPastMembershipStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AddPastMembershipStats {startDate?} {endDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create daily membership stats records for a past period';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var MembershipStatsService
     */
    private $membershipStatsService;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;


    // todo: move to config
    const LIFETIME_SKUS = [
        'PIANOTE-MEMBERSHIP-LIFETIME',
        'PIANOTE-MEMBERSHIP-LIFETIME-EXISTING-MEMBERS',
        'GUITAREO-LIFETIME-MEMBERSHIP',
        'DLM-Lifetime'
    ];

    /**
     * AddPastMembershipStats constructor.
     *
     * @param DatabaseManager $databaseManager
     * @param EcommerceEntityManager $entityManager
     * @param MembershipStatsService $membershipStatsService
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(
        DatabaseManager $databaseManager,
        EcommerceEntityManager $entityManager,
        MembershipStatsService $membershipStatsService,
        SubscriptionRepository $subscriptionRepository
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
        $this->entityManager = $entityManager;
        $this->membershipStatsService = $membershipStatsService;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $startDateString = $this->argument('startDate') ?: Carbon::now()->subDays(3)->toDateString();
        $startDate = Carbon::parse($startDateString);

        $endDate = $this->argument('endDate') ?
            Carbon::parse($this->argument('endDate')) : Carbon::now();

        $endDate = $endDate->endOfDay();


        $format = "Started computing membership stats for interval [%s -> %s].\n";

        $this->info(sprintf($format, $startDate->toDateTimeString(), $endDate->toDateTimeString()));

        $start = microtime(true);

        $this->seedPeriod($startDate, $endDate);
        $this->processSubscriptions($startDate, $endDate);
        $this->processSum($startDate, $endDate);

        $finish = microtime(true) - $start;

        $format = "Finished computing membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    public function seedPeriod(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_membership_stats')
            ->whereBetween('stats_date', [$smallDate, $bigDate])
            ->delete();

        $days = $smallDate->diffInDays($bigDate);

        $insertChunkSize = 1000;
        $insertData = [];
        $now = Carbon::now()
            ->toDateTimeString();

        // we dont want duplicate rows so we can delete the old ones first
        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_membership_stats')
            ->whereBetween('stats_date', [$smallDate->toDateString(), $bigDate->toDateString()])
            ->delete();

        for ($i = 0; $i <= $days; $i++) {

            $statsDate = $smallDate->copy()
                ->addDays($i)
                ->toDateString();

            foreach (config('ecommerce.available_brands', []) as $brand) {
                $insertData[] = [
                    'new' => 0,
                    'active_state' => 0,
                    'expired' => 0,
                    'suspended_state' => 0,
                    'canceled' => 0,
                    'canceled_state' => 0,
                    'interval_type' => MembershipStats::TYPE_ONE_MONTH,
                    'stats_date' => $statsDate,
                    'brand' => $brand,
                    'created_at' => $now,
                    'updated_at' => null,
                ];

                $insertData[] = [
                    'new' => 0,
                    'active_state' => 0,
                    'expired' => 0,
                    'suspended_state' => 0,
                    'canceled' => 0,
                    'canceled_state' => 0,
                    'interval_type' => MembershipStats::TYPE_SIX_MONTHS,
                    'stats_date' => $statsDate,
                    'brand' => $brand,
                    'created_at' => $now,
                    'updated_at' => null,
                ];

                $insertData[] = [
                    'new' => 0,
                    'active_state' => 0,
                    'expired' => 0,
                    'suspended_state' => 0,
                    'canceled' => 0,
                    'canceled_state' => 0,
                    'interval_type' => MembershipStats::TYPE_ONE_YEAR,
                    'stats_date' => $statsDate,
                    'brand' => $brand,
                    'created_at' => $now,
                    'updated_at' => null,
                ];

                $insertData[] = [
                    'new' => 0,
                    'active_state' => 0,
                    'expired' => 0,
                    'suspended_state' => 0,
                    'canceled' => 0,
                    'canceled_state' => 0,
                    'interval_type' => MembershipStats::TYPE_LIFETIME,
                    'stats_date' => $statsDate,
                    'brand' => $brand,
                    'created_at' => $now,
                    'updated_at' => null,
                ];

                $insertData[] = [
                    'new' => 0,
                    'active_state' => 0,
                    'expired' => 0,
                    'suspended_state' => 0,
                    'canceled' => 0,
                    'canceled_state' => 0,
                    'interval_type' => MembershipStats::TYPE_OTHER,
                    'stats_date' => $statsDate,
                    'brand' => $brand,
                    'created_at' => $now,
                    'updated_at' => null,
                ];

                $insertData[] = [
                    'new' => 0,
                    'active_state' => 0,
                    'expired' => 0,
                    'suspended_state' => 0,
                    'canceled' => 0,
                    'canceled_state' => 0,
                    'interval_type' => MembershipStats::TYPE_ALL,
                    'stats_date' => $statsDate,
                    'brand' => $brand,
                    'created_at' => $now,
                    'updated_at' => null,
                ];
            }

            if ($i > 0 && count($insertData) >= $insertChunkSize) {
                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_membership_stats')
                    ->insert($insertData);

                $insertData = [];
            }
        }

        if (!empty($insertData)) {
            $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                ->table('ecommerce_membership_stats')
                ->insert($insertData);
        }

        $finish = microtime(true) - $start;

        $format = "Finished seeding membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    public function processSubscriptions(Carbon $smallDate, Carbon $bigDate)
    {
        $this->processNewSubscriptionsMemberships($smallDate, $bigDate);
        $this->processExpiredSubscriptionMemberships($smallDate, $bigDate);
        $this->processCanceledSubscriptionMemberships($smallDate, $bigDate);
        $this->processUserMembership($smallDate, $bigDate);
    }

    /**
     * Update new membership - ecommerce_membership_stats.new column
     */
    public function processNewSubscriptionsMemberships(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $sql = <<<'EOT'
UPDATE ecommerce_membership_stats ms
INNER JOIN (
    SELECT
        COUNT(id) AS new,
        DATE(created_at) AS stats_date,
        COALESCE(
            IF (interval_type = '%s' AND interval_count = 1, '%s', NULL),
            IF (interval_type = '%s' AND interval_count = 6, '%s', NULL),
            IF (interval_type = '%s' AND interval_count = 1, '%s', NULL)
        ) AS stats_interval_type,
        brand
    FROM ecommerce_subscriptions
    WHERE
        ((interval_type = '%s' AND (interval_count = 1 OR interval_count = 6))
            OR (interval_type = '%s' AND interval_count = 1))
        AND created_at >= '%s'
        AND created_at <= '%s'
        AND product_id IS NOT NULL
    GROUP BY stats_date, stats_interval_type, brand
) n ON
    ms.stats_date = n.stats_date
    AND ms.interval_type = n.stats_interval_type
    AND ms.brand = n.brand
SET ms.new = n.new

EOT;
        $statement = sprintf(
            $sql,
            config('ecommerce.interval_type_monthly'),
            MembershipStats::TYPE_ONE_MONTH,
            config('ecommerce.interval_type_monthly'),
            MembershipStats::TYPE_SIX_MONTHS,
            config('ecommerce.interval_type_yearly'),
            MembershipStats::TYPE_ONE_YEAR,
            config('ecommerce.interval_type_monthly'),
            config('ecommerce.interval_type_yearly'),
            $smallDate->copy()->startOfDay()->toDateTimeString(),
            $bigDate->copy()->endOfDay()->toDateTimeString()
        );

        $this->databaseManager->statement($statement);

        $finish = microtime(true) - $start;

        $format = "Finished processing new membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }


    /**
     * Update expired membership - ecommerce_membership_stats.expired column
     */
    public function processExpiredSubscriptionMemberships(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $sql = <<<'EOT'
UPDATE ecommerce_membership_stats ms
INNER JOIN (
    SELECT
        COUNT(id) AS expired,
        DATE(paid_until) AS stats_date,
        COALESCE(
            IF (interval_type = '%s' AND interval_count = 1, '%s', NULL),
            IF (interval_type = '%s' AND interval_count = 6, '%s', NULL),
            IF (interval_type = '%s' AND interval_count = 1, '%s', NULL)
        ) AS stats_interval_type,
        brand
    FROM ecommerce_subscriptions
    WHERE
        ((interval_type = '%s' AND (interval_count = 1 OR interval_count = 6))
            OR (interval_type = '%s' AND interval_count = 1))
        AND paid_until IS NOT NULL
        AND is_active = 0
        AND canceled_on IS NULL
        AND paid_until >= '%s'
        AND paid_until <= '%s'
    GROUP BY stats_date, stats_interval_type, brand
) e ON
    ms.stats_date = e.stats_date
    AND ms.interval_type = e.stats_interval_type
    AND ms.brand = e.brand
SET ms.expired = e.expired
EOT;
        $statement = sprintf(
            $sql,
            config('ecommerce.interval_type_monthly'),
            MembershipStats::TYPE_ONE_MONTH,
            config('ecommerce.interval_type_monthly'),
            MembershipStats::TYPE_SIX_MONTHS,
            config('ecommerce.interval_type_yearly'),
            MembershipStats::TYPE_ONE_YEAR,
            config('ecommerce.interval_type_monthly'),
            config('ecommerce.interval_type_yearly'),
            $smallDate->copy()->startOfDay()->toDateTimeString(),
            $bigDate->copy()->endOfDay()->toDateTimeString()
        );

        $this->databaseManager->statement($statement);

        $finish = microtime(true) - $start;

        $format = "Finished processing expired membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    /**
     * Update canceled membership - ecommerce_membership_stats.canceled column
     */
    public function processCanceledSubscriptionMemberships(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $sql = <<<'EOT'
UPDATE ecommerce_membership_stats ms
INNER JOIN (
    SELECT
        COUNT(id) AS canceled,
        DATE(canceled_on) AS stats_date,
        COALESCE(
            IF (interval_type = '%s' AND interval_count = 1, '%s', NULL),
            IF (interval_type = '%s' AND interval_count = 6, '%s', NULL),
            IF (interval_type = '%s' AND interval_count = 1, '%s', NULL)
        ) AS stats_interval_type,
        brand
    FROM ecommerce_subscriptions
    WHERE
        ((interval_type = '%s' AND (interval_count = 1 OR interval_count = 6))
            OR (interval_type = '%s' AND interval_count = 1))
        AND canceled_on IS NOT NULL
        AND canceled_on >= '%s'
        AND canceled_on <= '%s'
    GROUP BY stats_date, stats_interval_type, brand
) c ON
    ms.stats_date = c.stats_date
    AND ms.interval_type = c.stats_interval_type
    AND ms.brand = c.brand
SET ms.canceled = c.canceled
EOT;
        $statement = sprintf(
            $sql,
            config('ecommerce.interval_type_monthly'),
            MembershipStats::TYPE_ONE_MONTH,
            config('ecommerce.interval_type_monthly'),
            MembershipStats::TYPE_SIX_MONTHS,
            config('ecommerce.interval_type_yearly'),
            MembershipStats::TYPE_ONE_YEAR,
            config('ecommerce.interval_type_monthly'),
            config('ecommerce.interval_type_yearly'),
            $smallDate->copy()->startOfDay()->toDateTimeString(),
            $bigDate->copy()->endOfDay()->toDateTimeString()
        );

        $this->databaseManager->statement($statement);

        $finish = microtime(true) - $start;

        $format = "Finished processing canceled membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    /**
     * Updates active/suspended/canceled state memberships - Total Users
     * table: ecommerce_membership_stats, columns: 'active_state', 'suspended_state', 'canceled_state'
     * also adds 'new' for lifetimes
     */
    public function processUserMembership(Carbon $smallDate, Carbon $bigDate)
    {
        $this->info("Started processing user membership total counts.");

        // todo: move to config
        $subscriptionIntervals = [
            [
                'interval_type' => 'month',
                'interval_count' => 1,
            ],
            [
                'interval_type' => 'month',
                'interval_count' => 6,
            ],
            [
                'interval_type' => 'year',
                'interval_count' => 1,
            ],
        ];

        $lifetimeProductIds = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_products')
            ->whereIn('sku', self::LIFETIME_SKUS)
            ->get()
            ->keyBy('id')
            ->toArray();

        // for each day
        $dateIncrement = $smallDate->copy();

        while ($dateIncrement <= $bigDate) {

            $this->info('Processing date: ' . $dateIncrement->toDateString());

            $dateIncrementEndOfDay = $dateIncrement->copy()->endOfDay();

            foreach (config('ecommerce.available_brands', []) as $brand) {

                // get total with access (should add up to this...)
                if (!empty(config('ecommerce.membership_product_skus')[$brand])) {

                    $totalMembershipCount =
                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_user_products')
                            ->join(
                                'ecommerce_products',
                                'ecommerce_products.id',
                                '=',
                                'ecommerce_user_products.product_id'
                            )
                            ->whereIn(
                                'sku',
                                config('ecommerce.membership_product_skus')[$brand]
                            )
                            ->where(
                                'ecommerce_user_products.created_at',
                                '<=',
                                $dateIncrementEndOfDay->toDateTimeString()
                            )
                            ->where('brand', $brand)
                            ->where(
                                function (Builder $builder) {
                                    $builder->where('expiration_date', '>', Carbon::now()->toDateTimeString())
                                        ->orWhereNull('expiration_date');
                                }
                            )
                            ->count($this->databaseManager->raw('DISTINCT user_id'));

                    $this->info("Total " . $brand . " users with access as of now: " . $totalMembershipCount);
                }

                $totalActiveForBrandFromPrimarySources = 0;

                // active lifetimes
                $lifetimeUserIds =
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_user_products')
                        ->join(
                            'ecommerce_products',
                            'ecommerce_products.id',
                            '=',
                            'ecommerce_user_products.product_id'
                        )
                        ->whereIn(
                            'product_id',
                            array_keys($lifetimeProductIds)
                        )
                        ->where(
                            'ecommerce_user_products.created_at',
                            '<=',
                            $dateIncrementEndOfDay->toDateTimeString()
                        )
                        ->where('brand', $brand)
                        ->whereNull('expiration_date')
                        ->get([$this->databaseManager->raw('DISTINCT user_id')])
                        ->pluck('user_id');

                $totalActiveForBrandFromPrimarySources += $lifetimeUserIds->count();

                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_membership_stats')
                    ->where(
                        'stats_date',
                        $dateIncrement->toDateString()
                    )
                    ->where('interval_type', 'lifetime')
                    ->where('brand', $brand)
                    ->update(
                        [
                            'active_state' => $lifetimeUserIds->count(),
                        ]
                    );

                // new lifetimes
                $newLifetimeUserIds =
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_user_products')
                        ->join(
                            'ecommerce_products',
                            'ecommerce_products.id',
                            '=',
                            'ecommerce_user_products.product_id'
                        )
                        ->whereIn(
                            'product_id',
                            array_keys($lifetimeProductIds)
                        )
                        ->whereBetween(
                            'ecommerce_user_products.created_at',
                            [
                                $dateIncrement->copy()->startOfDay(),
                                $dateIncrementEndOfDay->toDateTimeString()
                            ]
                        )
                        ->where('brand', $brand)
                        ->whereNull('expiration_date')
                        ->get([$this->databaseManager->raw('DISTINCT user_id')])
                        ->pluck('user_id');

                $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                    ->table('ecommerce_membership_stats')
                    ->where(
                        'stats_date',
                        $dateIncrement->toDateString()
                    )
                    ->where('interval_type', 'lifetime')
                    ->where('brand', $brand)
                    ->update(
                        [
                            'new' => $newLifetimeUserIds->count(),
                        ]
                    );

                foreach ($subscriptionIntervals as $subscriptionIntervalData) {

                    // if a user upgraded to another subscription type during period we will exclude them from
                    // the expired or active totals
                    $otherActiveSubscriptions =
                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_subscriptions')
                            ->whereNotNull('ecommerce_subscriptions.product_id')
                            ->where('brand', $brand)
                            ->where(
                                'ecommerce_subscriptions.created_at',
                                '<',
                                $dateIncrementEndOfDay->toDateTimeString()
                            )
                            ->where(
                                function (Builder $builder) use ($dateIncrementEndOfDay) {
                                    return $builder->where(
                                        'paid_until',
                                        '>',
                                        $dateIncrementEndOfDay->toDateTimeString()
                                    )
                                        ->where(
                                            function (Builder $builder) use ($dateIncrementEndOfDay) {
                                                $builder->whereNull('canceled_on')
                                                    ->orWhere(
                                                        'canceled_on',
                                                        '>',
                                                        $dateIncrementEndOfDay->toDateTimeString()
                                                    );
                                            }
                                        );
                                }
                            )
                            ->where('is_active', true)
                            ->where(
                                'ecommerce_subscriptions.interval_type',
                                '!=',
                                $subscriptionIntervalData['interval_type']
                            )
                            ->where(
                                'ecommerce_subscriptions.interval_count',
                                '!=',
                                $subscriptionIntervalData['interval_count']
                            )
                            ->whereIn(
                                'ecommerce_subscriptions.type',
                                [
                                    Subscription::TYPE_SUBSCRIPTION,
                                    Subscription::TYPE_APPLE_SUBSCRIPTION,
                                    Subscription::TYPE_GOOGLE_SUBSCRIPTION,
                                ]
                            )
                            ->groupBy('user_id')
                            ->get(['user_id']);


                    $activeSubscriptionsCount =
                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_subscriptions')
                            ->whereNotNull('ecommerce_subscriptions.product_id')
                            ->where('brand', $brand)
                            ->whereNotIn('user_id', $lifetimeUserIds->toArray())
                            ->whereNotIn('user_id', $otherActiveSubscriptions->pluck('user_id')->toArray())
                            ->where(
                                'ecommerce_subscriptions.created_at',
                                '<',
                                $dateIncrementEndOfDay->toDateTimeString()
                            )
                            ->where(
                                function (Builder $builder) use ($dateIncrementEndOfDay) {
                                    return $builder->where(
                                        'paid_until',
                                        '>',
                                        $dateIncrementEndOfDay->toDateTimeString()
                                    )
                                        ->where(
                                            function (Builder $builder) use ($dateIncrementEndOfDay) {
                                                $builder->whereNull('canceled_on')
                                                    ->orWhere(
                                                        'canceled_on',
                                                        '>',
                                                        $dateIncrementEndOfDay->toDateTimeString()
                                                    );
                                            }
                                        );
                                }
                            )
                            ->where('is_active', true)
                            ->where('ecommerce_subscriptions.interval_type', $subscriptionIntervalData['interval_type'])
                            ->where(
                                'ecommerce_subscriptions.interval_count',
                                $subscriptionIntervalData['interval_count']
                            )
                            ->whereIn(
                                'ecommerce_subscriptions.type',
                                [
                                    Subscription::TYPE_SUBSCRIPTION,
                                    Subscription::TYPE_APPLE_SUBSCRIPTION,
                                    Subscription::TYPE_GOOGLE_SUBSCRIPTION,
                                ]
                            )
                            ->count($this->databaseManager->raw('DISTINCT user_id'));

                    $expiredSubscriptionsCount =
                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_subscriptions')
                            ->whereNotNull('ecommerce_subscriptions.product_id')
                            ->whereNotIn('user_id', $otherActiveSubscriptions->pluck('user_id')->toArray())
                            ->where('brand', $brand)
                            ->whereNotIn('user_id', $lifetimeUserIds->toArray())
                            ->where(
                                'ecommerce_subscriptions.created_at',
                                '<',
                                $dateIncrementEndOfDay->toDateTimeString()
                            )
                            ->where(
                                function (Builder $builder) use ($dateIncrementEndOfDay) {
                                    return $builder->where(
                                        'paid_until',
                                        '<',
                                        $dateIncrementEndOfDay->toDateTimeString()
                                    )
                                        ->whereNull('canceled_on');
                                }
                            )
                            ->where('is_active', false)
                            ->where('ecommerce_subscriptions.interval_type', $subscriptionIntervalData['interval_type'])
                            ->where(
                                'ecommerce_subscriptions.interval_count',
                                $subscriptionIntervalData['interval_count']
                            )
                            ->whereIn(
                                'ecommerce_subscriptions.type',
                                [
                                    Subscription::TYPE_SUBSCRIPTION,
                                    Subscription::TYPE_APPLE_SUBSCRIPTION,
                                    Subscription::TYPE_GOOGLE_SUBSCRIPTION,
                                ]
                            )
                            ->count($this->databaseManager->raw('DISTINCT user_id'));

                    $canceledSubscriptionsCount =
                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_subscriptions')
                            ->whereNotNull('ecommerce_subscriptions.product_id')
                            ->whereNotIn('user_id', $otherActiveSubscriptions->pluck('user_id')->toArray())
                            ->where('brand', $brand)
                            ->whereNotIn('user_id', $lifetimeUserIds->toArray())
                            ->where(
                                'ecommerce_subscriptions.created_at',
                                '<',
                                $dateIncrementEndOfDay->toDateTimeString()
                            )
                            ->where(
                                function (Builder $builder) use ($dateIncrementEndOfDay) {
                                    return $builder->where(
                                        'canceled_on',
                                        '<',
                                        $dateIncrementEndOfDay->toDateTimeString()
                                    );
                                }
                            )
                            ->where('is_active', false)
                            ->where('ecommerce_subscriptions.interval_type', $subscriptionIntervalData['interval_type'])
                            ->where(
                                'ecommerce_subscriptions.interval_count',
                                $subscriptionIntervalData['interval_count']
                            )
                            ->whereIn(
                                'ecommerce_subscriptions.type',
                                [
                                    Subscription::TYPE_SUBSCRIPTION,
                                    Subscription::TYPE_APPLE_SUBSCRIPTION,
                                    Subscription::TYPE_GOOGLE_SUBSCRIPTION,
                                ]
                            )
                            ->count($this->databaseManager->raw('DISTINCT user_id'));

                    // debugging
//                    $this->info('-------------------------------------------');
//                    $this->info('interval_type=' . $subscriptionIntervalData['interval_type']);
//                    $this->info('interval_count=' . $subscriptionIntervalData['interval_count']);
//                    $this->info('$brand=' . $brand);
//                    $this->info('$activeCount=' . $activeSubscriptionsCount);
//                    $this->info('$expiredCount=' . $expiredSubscriptionsCount);
//                    $this->info('$canceledCount=' . $canceledSubscriptionsCount);

                    if ($subscriptionIntervalData['interval_type'] == config('ecommerce.interval_type_monthly')) {
                        if ($subscriptionIntervalData['interval_count'] == 1) {
                            $intervalType = MembershipStats::TYPE_ONE_MONTH;
                        } else {
                            $intervalType = MembershipStats::TYPE_SIX_MONTHS;
                        }
                    } else {
                        $intervalType = MembershipStats::TYPE_ONE_YEAR;
                    }

                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_membership_stats')
                        ->where(
                            'stats_date',
                            $dateIncrement->toDateString()
                        )
                        ->where('interval_type', $intervalType)
                        ->where('brand', $brand)
                        ->update(
                            [
                                'active_state' => $activeSubscriptionsCount,
                                'suspended_state' => $expiredSubscriptionsCount,
                                'canceled_state' => $canceledSubscriptionsCount,
                            ]
                        );

                    $totalActiveForBrandFromPrimarySources += $activeSubscriptionsCount;

                    // other count
                    $otherActiveStateCount = 0;
                    $otherNewCount = 0;

                    if (!empty(config('ecommerce.membership_product_skus')[$brand])) {
                        $otherActiveStateCount =
                            $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_user_products')
                                ->join(
                                    'ecommerce_products',
                                    'ecommerce_products.id',
                                    '=',
                                    'ecommerce_user_products.product_id'
                                )
                                ->whereIn(
                                    'sku',
                                    config('ecommerce.membership_product_skus')[$brand]
                                )
                                ->where(
                                    'ecommerce_user_products.created_at',
                                    '<=',
                                    $dateIncrementEndOfDay->toDateTimeString()
                                )
                                ->where('brand', $brand)
                                ->where(
                                    function (Builder $builder) {
                                        $builder->where('expiration_date', '>', Carbon::now()->toDateTimeString())
                                            ->orWhereNull('expiration_date');
                                    }
                                )
                                ->count($this->databaseManager->raw('DISTINCT user_id'));

                        $otherActiveStateCount -= $totalActiveForBrandFromPrimarySources;

                        $otherNewCount =
                            $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                ->table('ecommerce_user_products')
                                ->select(['ecommerce_user_products.*'])
                                ->join(
                                    'ecommerce_products',
                                    'ecommerce_products.id',
                                    '=',
                                    'ecommerce_user_products.product_id'
                                )
                                ->leftJoin(
                                    'ecommerce_subscriptions AS es',
                                    function (Builder $builder) use ($dateIncrementEndOfDay, $dateIncrement) {

                                        return $builder->on(
                                            function (Builder $builder) use (
                                                $dateIncrementEndOfDay,
                                                $dateIncrement
                                            ) {
                                                $builder->on('ecommerce_user_products.user_id', '=', 'es.user_id')
                                                    ->on(
                                                        'es.created_at',
                                                        '>',
                                                        $this->databaseManager->raw(
                                                            '"' .
                                                            $dateIncrement->copy()->startOfDay() .
                                                            '"'
                                                        )
                                                    )
                                                    ->on(
                                                        'es.created_at',
                                                        '<',
                                                        $this->databaseManager->raw(
                                                            '"' .
                                                            $dateIncrementEndOfDay->toDateTimeString() .
                                                            '"'
                                                        )
                                                    );

                                            }
                                        )->orOn(
                                            function (Builder $builder) use (
                                                $dateIncrementEndOfDay,
                                                $dateIncrement
                                            ) {
                                                $builder->on('ecommerce_user_products.user_id', '=', 'es.user_id')
                                                    ->on(
                                                        'es.created_at',
                                                        '<',
                                                        $this->databaseManager->raw(
                                                            '"' .
                                                            $dateIncrementEndOfDay->toDateTimeString() .
                                                            '"'
                                                        )
                                                    )
                                                    ->on(
                                                        'es.paid_until',
                                                        '>',
                                                        $this->databaseManager->raw(
                                                            '"' .
                                                            $dateIncrementEndOfDay->toDateTimeString() .
                                                            '"'
                                                        )
                                                    );

                                            }
                                        );
                                    }
                                )
                                ->whereNull('es.id')
                                ->whereIn(
                                    'sku',
                                    config('ecommerce.membership_product_skus')[$brand]
                                )
                                ->whereBetween(
                                    'ecommerce_user_products.created_at',
                                    [
                                        $dateIncrement->copy()->startOfDay(),
                                        $dateIncrementEndOfDay->toDateTimeString()
                                    ]
                                )
                                ->where('ecommerce_products.brand', $brand)
                                ->where(
                                    function (Builder $builder) use ($dateIncrementEndOfDay) {
                                        return $builder->where(
                                            'expiration_date',
                                            '>',
                                            $dateIncrementEndOfDay->toDateTimeString()
                                        )
                                            ->orWhereNull('expiration_date');
                                    }
                                )
                                ->groupBy('user_id')
                                ->get();

                        $otherNewCount = $otherNewCount->count();
                    }

                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_membership_stats')
                        ->where(
                            'stats_date',
                            $dateIncrement->toDateString()
                        )
                        ->where('interval_type', 'other')
                        ->where('brand', $brand)
                        ->update(
                            [
                                'active_state' => $otherActiveStateCount,
                                'new' => $otherNewCount,
                            ]
                        );
                }

            }

            $dateIncrement->addDay();
        }
    }

    public function processSum(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $sql = <<<'EOT'
UPDATE ecommerce_membership_stats ms
INNER JOIN (
    SELECT
        SUM(new) AS new,
        SUM(active_state) AS active_state,
        SUM(expired) AS expired,
        SUM(suspended_state) AS suspended_state,
        SUM(canceled) AS canceled,
        SUM(canceled_state) AS canceled_state,
        stats_date,
        brand
    FROM ecommerce_membership_stats m
    WHERE
        m.interval_type IN ('%s')
        AND m.stats_date >= '%s'
        AND m.stats_date <= '%s'
    GROUP BY stats_date, brand
) n ON ms.stats_date = n.stats_date 
    AND ms.brand = n.brand
SET
    ms.new = n.new,
    ms.active_state = n.active_state,
    ms.expired = n.expired,
    ms.suspended_state = n.suspended_state,
    ms.canceled = n.canceled,
    ms.canceled_state = n.canceled_state
WHERE ms.interval_type = '%s'
EOT;

        $statement = sprintf(
            $sql,
            implode(
                "', '",
                [
                    MembershipStats::TYPE_ONE_MONTH,
                    MembershipStats::TYPE_SIX_MONTHS,
                    MembershipStats::TYPE_ONE_YEAR,
                    MembershipStats::TYPE_OTHER,
                    MembershipStats::TYPE_LIFETIME,
                ]
            ),
            $smallDate->toDateString(),
            $bigDate->toDateString(),
            MembershipStats::TYPE_ALL
        );

        $this->databaseManager->statement($statement);

        $finish = microtime(true) - $start;

        $format = "Finished processing membership sum stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }
}
