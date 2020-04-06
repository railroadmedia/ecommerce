<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Entities\RetentionStats;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Services\RetentionStatsService;
use Throwable;

class AddPastRetentionStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'AddPastRetentionStats {startDate?} {endDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create weekly retention stats records for a past period';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var RetentionStatsService
     */
    private $retentionStatsService;

    const LIFETIME_SKUS = [
        'PIANOTE-MEMBERSHIP-LIFETIME' => 'pianote',
        'PIANOTE-MEMBERSHIP-LIFETIME-EXISTING-MEMBERS' => 'pianote',
        'GUITAREO-LIFETIME-MEMBERSHIP' => 'guitareo',
        'DLM-Lifetime' => 'drumeo'
    ];

    const BRANDS = [
        'drumeo',
        'pianote',
        'guitareo'
    ];

    /**
     * AddPastRetentionStats constructor.
     *
     * @param DatabaseManager $databaseManager
     * @param RetentionStatsService $retentionStatsService
     */
    public function __construct(
        DatabaseManager $databaseManager,
        RetentionStatsService $retentionStatsService
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
        $this->retentionStatsService = $retentionStatsService;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $startDate = $this->argument('startDate') ?
                        Carbon::parse($this->argument('startDate')) : Carbon::now()->subWeeks(4);

        $endDate = $this->argument('endDate') ?
                        Carbon::parse($this->argument('endDate')) : Carbon::now();

        $endDate = $endDate->endOfDay();

        $format = "Started computing retention stats for interval [%s -> %s].\n";

        $this->info(sprintf($format, $startDate->toDateTimeString(), $endDate->toDateTimeString()));

        $start = microtime(true);

        $this->computeStats($startDate, $endDate);

        $finish = microtime(true) - $start;

        $format = "Finished computing retention stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    /**
     * @param Carbon $smallDate
     * @param Carbon $bigDate
     */
    public function computeStats(Carbon $smallDate, Carbon $bigDate)
    {
        $intervals = $this->retentionStatsService->getIntervals($smallDate, $bigDate);

        /*
        $intervals = [
            0 => [
                'start' => Carbon (Sunday),
                'end' => Carbon (Saturday),
            ],
            ...
            n => [
                'start' => Carbon (Sunday),
                'end' => Carbon (Saturday),
            ]
        ];
        the $smallDate is between $intervals[0]['start'] and $intervals[0]['end']
        the $bigDate is between $intervals[n]['start'] and $intervals[n]['end']
        */

        $lifetimeBrandProductIds = [
            'drumeo' => [],
            'pianote' => [],
            'guitareo' => [],
        ];

        $lifetimeProducts = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
            ->table('ecommerce_products')
            ->whereIn('sku', array_keys(self::LIFETIME_SKUS))
            ->get()
            ->keyBy('id')
            ->toArray();

        foreach ($lifetimeProducts as $lifetimeProduct) {
            $lifetimeBrandProductIds[$lifetimeProduct->brand][] = $lifetimeProduct->id;
        }

        $subscriptionTypes = [
            RetentionStats::TYPE_ONE_MONTH => [
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 1,
            ],
            RetentionStats::TYPE_SIX_MONTHS => [
                'interval_type' => config('ecommerce.interval_type_monthly'),
                'interval_count' => 6,
            ],
            RetentionStats::TYPE_ONE_YEAR => [
                'interval_type' => config('ecommerce.interval_type_yearly'),
                'interval_count' => 1,
            ],
        ];

        $insertData = [];
        $insertChunkSize = 5000;

        $createdAt = Carbon::now()->toDateTimeString();

        foreach ($intervals as $interval) {

            $start = microtime(true);

            $startEndOfDay = $interval['start']->copy()->endOfDay();
            $endEndOfDay = $interval['end']->copy()->endOfDay();

            foreach (self::BRANDS as $brand) {

                foreach ($subscriptionTypes as $subscriptionType => $subscriptionTypeData) {

                    $customersStart = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_subscriptions')
                        ->whereIn(
                            'type',
                            [
                                Subscription::TYPE_SUBSCRIPTION,
                            ]
                        )
                        ->where('interval_type', $subscriptionTypeData['interval_type'])
                        ->where('interval_count', $subscriptionTypeData['interval_count'])
                        ->where('brand', $brand)
                        ->where('start_date', '<', $startEndOfDay)
                        ->where(function ($query) use ($startEndOfDay) {
                            $query->whereNull('canceled_on')
                                ->orWhere('canceled_on', '>', $startEndOfDay);
                        })
                        ->where('paid_until', '>', $startEndOfDay)
                        ->count();

                    $customersEnd = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_subscriptions')
                        ->whereIn(
                            'type',
                            [
                                Subscription::TYPE_SUBSCRIPTION,
                            ]
                        )
                        ->where('interval_type', $subscriptionTypeData['interval_type'])
                        ->where('interval_count', $subscriptionTypeData['interval_count'])
                        ->where('brand', $brand)
                        ->where('start_date', '<', $endEndOfDay)
                        ->where(function ($query) use ($endEndOfDay) {
                            $query->whereNull('canceled_on')
                                ->orWhere('canceled_on', '>', $endEndOfDay);
                        })
                        ->where('paid_until', '>', $endEndOfDay)
                        ->count();

                    $customersNew = $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_subscriptions')
                        ->whereIn(
                            'type',
                            [
                                Subscription::TYPE_SUBSCRIPTION,
                            ]
                        )
                        ->where('interval_type', $subscriptionTypeData['interval_type'])
                        ->where('interval_count', $subscriptionTypeData['interval_count'])
                        ->where('brand', $brand)
                        ->where('start_date', '>', $startEndOfDay)
                        ->where('start_date', '<', $endEndOfDay)
                        ->where(function ($query) use ($endEndOfDay) {
                            $query->whereNull('canceled_on')
                                ->orWhere('canceled_on', '>', $endEndOfDay);
                        })
                        ->where('paid_until', '>', $endEndOfDay)
                        ->count();

                    // insert or update so we can re-calculate time spans without making duplicate rows
                    $this->databaseManager->connection(config('ecommerce.database_connection_name'))
                        ->table('ecommerce_retention_stats')
                        ->updateOrInsert(
                            [
                                'brand' => $brand,
                                'subscription_type' => $subscriptionType,
                                'interval_start_date' => $interval['start']->toDateString(),
                                'interval_end_date' => $interval['end']->toDateString(),
                            ],
                            [
                                'customers_start' => $customersStart,
                                'customers_end' => $customersEnd,
                                'customers_new' => $customersNew,
                                'created_at' => $createdAt,
                                'updated_at' => $createdAt,
                            ]
                        );
                }
            }

            $finish = microtime(true) - $start;

            $format = "Finished interval [%s -> %s] in %s seconds\n";

            $this->info(
                sprintf(
                    $format,
                    $interval['start'],
                    $interval['end'],
                    $finish
                )
            );
        }
    }
}
