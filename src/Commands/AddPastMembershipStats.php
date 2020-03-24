<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\MembershipStats;
use Railroad\Ecommerce\Entities\Structures\SubscriptionStateInterval;
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

    const LIFETIME_SKUS = [
        'PIANOTE-MEMBERSHIP-LIFETIME',
        'PIANOTE-MEMBERSHIP-LIFETIME-EXISTING-MEMBERS',
        'GUITAREO-LIFETIME-MEMBERSHIP',
        'DLM-Lifetime'
    ];

    const BRANDS = [
        'drumeo',
        'pianote',
        'guitareo',
        'recordeo'
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
        $startDateString = $this->argument('startDate') ?: '2015-01-01';
        $startDate = Carbon::parse($startDateString);

        $endDate = $this->argument('endDate') ?
                        Carbon::parse($this->argument('endDate')) : Carbon::now();

        $endDate = $endDate->endOfDay();


        $format = "Started computing membership stats for interval [%s -> %s].\n";

        $this->info(sprintf($format, $startDate->toDateTimeString(), $endDate->toDateTimeString()));

        $start = microtime(true);

        $this->seedPeriod($startDate, $endDate);
        $this->processSubscriptions($startDate, $endDate);
        $this->processLifetimeSubscriptions($startDate, $endDate);

        $finish = microtime(true) - $start;

        $format = "Finished computing membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    public function seedPeriod(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $days = $smallDate->diffInDays($bigDate);

        $insertChunkSize = 1000;
        $insertData = [];
        $now = Carbon::now()
                ->toDateTimeString();

        for ($i = 0; $i <= $days; $i++) {

            $statsDate = $smallDate->copy()
                            ->addDays($i)
                            ->toDateString();

            foreach (self::BRANDS as $brand) {
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
        $this->processNewMembership($smallDate, $bigDate);
        $this->processExpiredMembership($smallDate, $bigDate);
        $this->processCanceledMembership($smallDate, $bigDate);
        $this->processUserMembership($smallDate, $bigDate);
    }

    /**
     * Update new membership - ecommerce_membership_stats.new column
     */
    public function processNewMembership(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $sql = <<<'EOT'
UPDATE ecommerce_membership_stats ms
INNER JOIN (
    SELECT
        COUNT(id) AS new,
        DATE(start_date) AS stats_date,
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
        AND start_date >= '%s'
        AND start_date <= '%s'
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
            $smallDate->toDateString(),
            $bigDate->toDateString()
        );

        $this->databaseManager->statement($statement);

        $finish = microtime(true) - $start;

        $format = "Finished processing new membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    /**
     * Update expired membership - ecommerce_membership_stats.expired column
     */
    public function processExpiredMembership(Carbon $smallDate, Carbon $bigDate)
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
            $smallDate->toDateTimeString(),
            $bigDate->toDateTimeString()
        );

        $this->databaseManager->statement($statement);

        $finish = microtime(true) - $start;

        $format = "Finished processing expired membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    /**
     * Update canceled membership - ecommerce_membership_stats.canceled column
     */
    public function processCanceledMembership(Carbon $smallDate, Carbon $bigDate)
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
            $smallDate->toDateTimeString(),
            $bigDate->toDateTimeString()
        );

        $this->databaseManager->statement($statement);

        $finish = microtime(true) - $start;

        $format = "Finished processing canceled membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    /**
     * todo - add doc
     */
    public function processUserMembership(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $this->info("Started processing users membership");

        $chunkSize = 1000;
        $processed = 0;
        $processingStart = microtime(true);

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('usora_users')
            ->select(['usora_users.id'])
            ->leftJoin(
                'ecommerce_subscriptions',
                'ecommerce_subscriptions.user_id',
                '=',
                'usora_users.id'
            )
            ->whereNotNull('ecommerce_subscriptions.id')
            ->where('ecommerce_subscriptions.start_date', '<=', $bigDate)
            ->where(function ($query) {
                $query->where(function ($query) {
                        $query->where('ecommerce_subscriptions.interval_type', config('ecommerce.interval_type_monthly'))
                            ->where(function ($query) {
                                $query->where('ecommerce_subscriptions.interval_count', 1)
                                    ->orWhere('ecommerce_subscriptions.interval_count', 6);
                            });
                    })
                    ->orWhere(function ($query) {
                        $query->where('ecommerce_subscriptions.interval_type', config('ecommerce.interval_type_yearly'))
                            ->where('ecommerce_subscriptions.interval_count', 1);
                    });
            })
            ->whereIn(
                'ecommerce_subscriptions.type',
                [
                    Subscription::TYPE_SUBSCRIPTION,
                    Subscription::TYPE_APPLE_SUBSCRIPTION,
                    Subscription::TYPE_GOOGLE_SUBSCRIPTION,
                ]
            )
            ->groupBy('usora_users.id')
            ->orderBy('usora_users.id', 'desc')
            ->chunk(
                $chunkSize,
                function (Collection $rows) use (
                    $smallDate,
                    $bigDate,
                    $chunkSize,
                    &$processed,
                    &$processingStart
                ) {

                    foreach ($rows as $userRow) {

                        $userId = $userRow->id;
                        $membership = [];

                        $subscriptions = $this->subscriptionRepository
                                            ->getUserMembershipSubscriptionBeforeDate($userId, $bigDate);

                        foreach ($subscriptions as $subscription) {
                            $brand = $subscription->getBrand();

                            $intervalType = null;

                            if ($subscription->getIntervalType() == config('ecommerce.interval_type_monthly')) {
                                if ($subscription->getIntervalCount() == 1) {
                                    $intervalType = MembershipStats::TYPE_ONE_MONTH;
                                } else {
                                    $intervalType = MembershipStats::TYPE_SIX_MONTHS;
                                }
                            } else {
                                $intervalType = MembershipStats::TYPE_ONE_YEAR;
                            }

                            if (!isset($membership[$brand])) {
                                $membership[$brand] = [];
                            }

                            if (!isset($membership[$brand][$intervalType])) {
                                $membership[$brand][$intervalType] = [];
                            }

                            $membership[$brand][$intervalType] = $this->membershipStatsService
                                    ->addSubscriptionStateIntervals(
                                        $subscription,
                                        $smallDate,
                                        $bigDate,
                                        $membership[$brand][$intervalType]
                                    );
                        }

                        $this->entityManager->clear();

                        $typeToColumn = [
                            SubscriptionStateInterval::TYPE_ACTIVE => 'active_state',
                            SubscriptionStateInterval::TYPE_SUSPENDED => 'suspended_state',
                            SubscriptionStateInterval::TYPE_CANCELED => 'canceled_state',
                        ];

                        foreach ($membership as $brand => $intervalTypes) {
                            foreach ($intervalTypes as $intervalType => $intervals) {
                                foreach ($intervals as $subscriptionStateInterval) {
                                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                                        ->table('ecommerce_membership_stats')
                                        ->where(
                                            'stats_date',
                                            '>=',
                                            $subscriptionStateInterval->getStart()
                                                ->toDateString()
                                        )
                                        ->where(
                                            'stats_date',
                                            '<=',
                                            $subscriptionStateInterval->getEnd()
                                                ->toDateString()
                                        )
                                        ->where('interval_type', $intervalType)
                                        ->where('brand', $brand)
                                        ->increment($typeToColumn[$subscriptionStateInterval->getType()]);
                                }
                            }
                        }
                    }

                    $processed += $chunkSize;

                    if ($processed && $processed%($chunkSize * 10) == 0) {
                        $finishBatch = microtime(true) - $processingStart;

                        $format = "Finished processing %s users, batch processed in %s seconds";

                        $this->info(sprintf($format, $processed, $finishBatch));

                        $processingStart = microtime(true);
                    }
                }
            );

        $finishBatch = microtime(true) - $processingStart;

        $format = "Finished processing %s users, batch processed in %s seconds";

        $this->info(sprintf($format, $processed, $finishBatch));

        $finish = microtime(true) - $start;

        $format = "Finished processing active state membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    /**
     * todo - remove
     */
    public function processSuspendedMembership(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $chunkSize = 1000;

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_subscriptions')
            ->whereNotNull('paid_until')
            ->where('paid_until', '<=', $bigDate)
            ->where('is_active', 0)
            ->whereNull('canceled_on')
            ->where(function ($query) {
                $query->where(function ($query) {
                        $query->where('interval_type', config('ecommerce.interval_type_monthly'))
                            ->where(function ($query) {
                                $query->where('interval_count', 1)
                                    ->orWhere('interval_count', 6);
                            });
                    })
                    ->orWhere(function ($query) {
                        $query->where('interval_type', config('ecommerce.interval_type_yearly'))
                            ->where('interval_count', 1);
                    });
            })
            ->orderBy('id', 'desc')
            ->chunk(
                $chunkSize,
                function (Collection $rows) use ($smallDate, $bigDate) {

                    foreach ($rows as $item) {

                        $itemData = get_object_vars($item);

                        $paidUntil = Carbon::parse($itemData['paid_until'])->addDays(1);
                        $start = $paidUntil < $smallDate ? $smallDate : $paidUntil;

                        $end = $bigDate;

                        $intervalType = null;

                        if ($itemData['interval_type'] == config('ecommerce.interval_type_monthly')) {
                            if ($itemData['interval_count'] == 1) {
                                $intervalType = MembershipStats::TYPE_ONE_MONTH;
                            } else {
                                $intervalType = MembershipStats::TYPE_SIX_MONTHS;
                            }
                        } else {
                            $intervalType = MembershipStats::TYPE_ONE_YEAR;
                        }

                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_membership_stats')
                            ->where('stats_date', '>=', $start->toDateString())
                            ->where('stats_date', '<=', $end->toDateString())
                            ->where('interval_type', $intervalType)
                            ->where('brand', $itemData['brand'])
                            ->increment('suspended_state');
                    }
                }
            );

        $finish = microtime(true) - $start;

        $format = "Finished processing suspended state membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    /**
     * todo - remove
     */
    public function processCanceledStateMembership(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $chunkSize = 1000;

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_subscriptions')
            ->whereNotNull('canceled_on')
            ->where('canceled_on', '<=', $bigDate)
            ->where(function ($query) {
                $query->where(function ($query) {
                        $query->where('interval_type', config('ecommerce.interval_type_monthly'))
                            ->where(function ($query) {
                                $query->where('interval_count', 1)
                                    ->orWhere('interval_count', 6);
                            });
                    })
                    ->orWhere(function ($query) {
                        $query->where('interval_type', config('ecommerce.interval_type_yearly'))
                            ->where('interval_count', 1);
                    });
            })
            ->orderBy('id', 'desc')
            ->chunk(
                $chunkSize,
                function (Collection $rows) use ($smallDate, $bigDate) {

                    foreach ($rows as $item) {

                        $itemData = get_object_vars($item);

                        $canceledOn = Carbon::parse($itemData['canceled_on']);

                        $start = $canceledOn < $smallDate ? $smallDate : $canceledOn;

                        $end = $bigDate;

                        $intervalType = null;

                        if ($itemData['interval_type'] == config('ecommerce.interval_type_monthly')) {
                            if ($itemData['interval_count'] == 1) {
                                $intervalType = MembershipStats::TYPE_ONE_MONTH;
                            } else {
                                $intervalType = MembershipStats::TYPE_SIX_MONTHS;
                            }
                        } else {
                            $intervalType = MembershipStats::TYPE_ONE_YEAR;
                        }

                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_membership_stats')
                            ->where('stats_date', '>=', $start->toDateString())
                            ->where('stats_date', '<=', $end->toDateString())
                            ->where('interval_type', $intervalType)
                            ->where('brand', $itemData['brand'])
                            ->increment('canceled_state');
                    }
                }
            );

        $finish = microtime(true) - $start;

        $format = "Finished processing canceled state membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    public function processLifetimeSubscriptions(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $lifetimeProducts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_products')
            ->whereIn('sku', self::LIFETIME_SKUS)
            ->get()
            ->keyBy('id')
            ->toArray();

        $sql = <<<'EOT'
UPDATE ecommerce_membership_stats ms
INNER JOIN (
    SELECT
        COUNT(up.id) AS new,
        DATE(up.created_at) AS stats_date,
        lp.brand,
        'lifetime' AS interval_type
    FROM ecommerce_user_products up
    INNER JOIN
        (
            SELECT id, brand
            FROM ecommerce_products
            WHERE
                sku IN ('%s')
        ) lp
        ON lp.id = up.product_id
    WHERE
        (up.deleted_at IS NULL OR DATE(up.deleted_at) > '%s')
        AND (up.expiration_date IS NULL OR DATE(up.expiration_date) > '%s')
        AND DATE(up.created_at) >= '%s'
        AND DATE(up.created_at) <= '%s'
    GROUP BY stats_date, brand
) n ON ms.stats_date = n.stats_date AND ms.brand = n.brand AND ms.interval_type = n.interval_type
SET ms.new = n.new
EOT;

        $statement = sprintf(
            $sql,
            implode("', '", self::LIFETIME_SKUS),
            $smallDate->toDateString(),
            $smallDate->toDateString(),
            $smallDate->toDateString(),
            $bigDate->toDateString(),
            MembershipStats::TYPE_LIFETIME
        );

        $this->databaseManager->statement($statement);

        $chunkSize = 1000;

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_user_products')
            ->whereIn(
                'product_id',
                array_keys($lifetimeProducts)
            )
            ->where('created_at', '<=', $bigDate)
            ->orderBy('id', 'desc')
            ->chunk(
                $chunkSize,
                function (Collection $rows) use ($smallDate, $bigDate, $lifetimeProducts) {

                    foreach ($rows as $item) {

                        $itemData = get_object_vars($item);

                        $createdAt = Carbon::parse($itemData['created_at']);

                        $start = $createdAt < $smallDate ? $smallDate : $createdAt;

                        $end = $bigDate;

                        $deletedAt = $itemData['deleted_at'] ? Carbon::parse($itemData['deleted_at']) : null;

                        if ($deletedAt && $deletedAt < $end) {
                            $end = $deletedAt;
                        }

                        $expirationDate = $itemData['expiration_date'] ? Carbon::parse($itemData['expiration_date']) : null;

                        if ($expirationDate && $expirationDate < $end) {
                            $end = $expirationDate;
                        }

                        $brand = $lifetimeProducts[$itemData['product_id']]->brand;

                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_membership_stats')
                            ->where('stats_date', '>=', $start->toDateString())
                            ->where('stats_date', '<=', $end->toDateString())
                            ->where('interval_type', MembershipStats::TYPE_LIFETIME)
                            ->where('brand', $brand)
                            ->increment('active_state');
                    }
                }
            );

        $finish = microtime(true) - $start;

        $format = "Finished processing TYPE_LIFETIME membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }
}
