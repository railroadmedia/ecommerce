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

class SimulateAddPastMembershipStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SimulateAddPastMembershipStats {startDate?} {endDate?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'SimulateAddPastMembershipStats';

    /**
     * SimulateAddPastMembershipStats constructor.
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

        //
        $dateInterval = $startDate->copy();
        $realNow = Carbon::now();

        while ($dateInterval <= $endDate) {
            $this->info('Simulating ' . $dateInterval->toDateString());
            Carbon::setTestNow($dateInterval);

            $this->call('AddPastMembershipStats', []);
            Carbon::setTestNow($realNow);

            $dateInterval->addDay();
        }

        $finish = microtime(true) - $start;

        $format = "Finished computing membership stats in total %s seconds\n";

        $this->info(sprintf($format, $finish));
    }
}
