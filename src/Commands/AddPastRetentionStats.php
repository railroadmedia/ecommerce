<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
// use Illuminate\Support\Collection;
// use Railroad\Ecommerce\Entities\RetentionStats;
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
     * AddPastRetentionStats constructor.
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

        $endDate = $endDate->endOfDay();


        $format = "Started computing retention stats for interval [%s -> %s].\n";

        $this->info(sprintf($format, $startDate->toDateTimeString(), $endDate->toDateTimeString()));

        $start = microtime(true);

        // seed period
        // compute stats

        $finish = microtime(true) - $start;

        $format = "Finished computing retention stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }
}
