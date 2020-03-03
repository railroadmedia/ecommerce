<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Ecommerce\Entities\MembershipStats;
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
     */
    public function __construct(
        DatabaseManager $databaseManager
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
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

            $insertData[] = [
                'new' => 0,
                'active_state' => 0,
                'expired' => 0,
                'suspended_state' => 0,
                'canceled' => 0,
                'canceled_state' => 0,
                'interval_type' => MembershipStats::TYPE_ONE_MONTH,
                'stats_date' => $statsDate,
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
                'created_at' => $now,
                'updated_at' => null,
            ];

            if ($i > 0 && (($i * 4) % $insertChunkSize) == 0) {
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
        $this->processActiveMembership($smallDate, $bigDate);
        $this->processExpiredMembership($smallDate, $bigDate);
        $this->processSuspendedMembership($smallDate, $bigDate);
        $this->processCanceledMembership($smallDate, $bigDate);
        $this->processCanceledStateMembership($smallDate, $bigDate);
    }

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
        ) AS interval_type
    FROM ecommerce_subscriptions
    WHERE
        ((interval_type = '%s' AND (interval_count = 1 OR interval_count = 6))
            OR (interval_type = '%s' AND interval_count = 1))
        AND created_at >= '%s'
        AND created_at <= '%s'
    GROUP BY stats_date, interval_type
) n ON ms.stats_date = n.stats_date AND ms.interval_type = n.interval_type
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
            $smallDate->toDateTimeString(),
            $bigDate->toDateTimeString()
        );

        $this->databaseManager->statement($statement);

        $finish = microtime(true) - $start;

        $format = "Finished processing new membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

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
        ) AS interval_type
    FROM ecommerce_subscriptions
    WHERE
        ((interval_type = '%s' AND (interval_count = 1 OR interval_count = 6))
            OR (interval_type = '%s' AND interval_count = 1))
        AND paid_until IS NOT NULL
        AND is_active = 0
        AND canceled_on IS NULL
        AND paid_until >= '%s'
        AND paid_until <= '%s'
    GROUP BY stats_date, interval_type
) e ON ms.stats_date = e.stats_date AND ms.interval_type = e.interval_type
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
        ) AS interval_type
    FROM ecommerce_subscriptions
    WHERE
        ((interval_type = '%s' AND (interval_count = 1 OR interval_count = 6))
            OR (interval_type = '%s' AND interval_count = 1))
        AND canceled_on IS NOT NULL
        AND canceled_on >= '%s'
        AND canceled_on <= '%s'
    GROUP BY stats_date, interval_type
) c ON ms.stats_date = c.stats_date AND ms.interval_type = c.interval_type
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

    public function processActiveMembership(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $chunkSize = 1000;

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_subscriptions')
            ->where('created_at', '<=', $bigDate)
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

                        $createdAt = Carbon::parse($itemData['created_at']);

                        $start = $createdAt < $smallDate ? $smallDate : $createdAt;

                        $end = $bigDate->copy()->addDays(1); // the update query uses end interval exclusive

                        if ($itemData['canceled_on']) {
                            $canceledOn = Carbon::parse($itemData['canceled_on']);
                            $end = $canceledOn > $end ? $end : $canceledOn;
                        }

                        if ($itemData['paid_until']) {
                            $paidUntil = Carbon::parse($itemData['paid_until']);
                            $end = $paidUntil > $end ? $end : $paidUntil;
                        }

                        // at this point $end represents the smallest non-null value of ($bigDate, $canceledOn, $paidUntil)

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
                            ->where('stats_date', '<', $end->toDateString())
                            ->where('interval_type', $intervalType)
                            ->increment('active_state');
                    }
                }
            );

        $finish = microtime(true) - $start;

        $format = "Finished processing active state membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    public function processSuspendedMembership(Carbon $smallDate, Carbon $bigDate)
    {
        $start = microtime(true);

        $chunkSize = 1000;

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_subscriptions')
            ->whereNotNull('paid_until')
            ->where('paid_until', '<=', $bigDate)
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

                        $paidUntil = Carbon::parse($itemData['paid_until']);
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
                            ->increment('suspended_state');
                    }
                }
            );

        $finish = microtime(true) - $start;

        $format = "Finished processing suspended state membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

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

        $lifetimeProductsIds = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_products')
            ->whereIn('sku', self::LIFETIME_SKUS)
            ->get()
            ->pluck('id')
            ->toArray();

        $sql = <<<'EOT'
UPDATE ecommerce_membership_stats ms
INNER JOIN (
    SELECT
        COUNT(id) AS new,
        DATE(created_at) AS stats_date
    FROM ecommerce_user_products
    WHERE
        product_id IN (%s)
        AND deleted_at IS NULL
        AND expiration_date IS NULL
        AND created_at >= '%s'
        AND created_at <= '%s'
    GROUP BY stats_date
) n ON ms.stats_date = n.stats_date
SET ms.new = n.new
WHERE ms.interval_type = '%s'
EOT;

        $statement = sprintf(
            $sql,
            implode(', ', $lifetimeProductsIds),
            $smallDate->toDateTimeString(),
            $bigDate->toDateTimeString(),
            MembershipStats::TYPE_LIFETIME
        );

        $this->databaseManager->statement($statement);

        $chunkSize = 1000;

        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_user_products')
            ->whereIn('product_id', $lifetimeProductsIds)
            ->where('created_at', '<=', $bigDate)
            ->orderBy('id', 'desc')
            ->chunk(
                $chunkSize,
                function (Collection $rows) use ($smallDate, $bigDate) {

                    foreach ($rows as $item) {

                        $itemData = get_object_vars($item);

                        $start = $itemData['created_at'] < $smallDate ? $smallDate : $itemData['created_at'];

                        $end = $bigDate;

                        if ($itemData['deleted_at'] && $itemData['deleted_at'] < $end) {
                            $end = $itemData['deleted_at'];
                        }

                        if ($itemData['expiration_date'] && $itemData['expiration_date'] < $end) {
                            $end = $itemData['expiration_date'];
                        }

                        $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                            ->table('ecommerce_membership_stats')
                            ->where('stats_date', '>=', $start)
                            ->where('stats_date', '<=', $end)
                            ->where('interval_type', MembershipStats::TYPE_LIFETIME)
                            ->increment('active_state');
                    }
                }
            );

        $finish = microtime(true) - $start;

        $format = "Finished processing TYPE_LIFETIME membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }
}
