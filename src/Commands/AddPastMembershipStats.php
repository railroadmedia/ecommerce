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
        $startDateString = $this->argument('startDate') ?: '2017-01-01';
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

        for ($i = 0; $i < $days; $i++) {

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

        $finish = microtime(true) - $start;

        $format = "Finished seeding membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }

    public function processSubscriptions(Carbon $smallDate, Carbon $bigDate)
    {
        
    }

    public function processLifetimeSubscriptions(Carbon $smallDate, Carbon $bigDate)
    {

    }
}
