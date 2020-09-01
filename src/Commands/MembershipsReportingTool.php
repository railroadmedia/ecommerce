<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Repositories\MembershipStatsRepository;
use Throwable;

class MembershipsReportingTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * startDate and endDate can be any date or dateime string
     *
     * @var string
     */
    protected $signature = 'MembershipsReportingTool {startDate} {endDate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull membership stats between dates per month.';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var MembershipStatsRepository
     */
    private $membershipStatsRepository;


    /**
     * MembershipsReportingTool constructor.
     *
     * @param DatabaseManager $databaseManager
     * @param MembershipStatsRepository $membershipStatsRepository
     */
    public function __construct(
        DatabaseManager $databaseManager,
        MembershipStatsRepository $membershipStatsRepository
    )
    {
        parent::__construct();

        $this->databaseManager = $databaseManager;
        $this->membershipStatsRepository = $membershipStatsRepository;
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle()
    {
        $startDate = Carbon::parse($this->argument('startDate'))->startOfDay();
        $endDate = Carbon::parse($this->argument('endDate'))->endOfDay();
        $dateInterval = $startDate->copy();

        $csvData = [
            [
                'Date Start' => 'Date Start',
                'Date End' => 'Date End',
                'Drumeo Total Memberships' => 'Drumeo Total Memberships',
                'Drumeo New Memberships' => 'Drumeo New Memberships',
                'Drumeo New Non-recurring Memberships' => 'Drumeo New Non-recurring Memberships',
                'Pianote Total Memberships' => 'Pianote Total Memberships',
                'Pianote New Memberships' => 'Pianote New Memberships',
                'Pianote New Non-recurring Memberships' => 'Pianote New Non-recurring Memberships',
                'Guitareo Total Memberships' => 'Guitareo Total Memberships',
                'Guitareo New Memberships' => 'Guitareo New Memberships',
                'Guitareo New Non-recurring Memberships' => 'Guitareo New Non-recurring Memberships',
            ]
        ];

        $brands = ['drumeo', 'pianote', 'guitareo'];

        while ($dateInterval <= $endDate) {
            $intervalStart = $dateInterval->copy()->startOfMonth();
            $intervalEnd = $dateInterval->copy()->endOfMonth();

            $this->info('Start date: ' . $intervalStart->toDateTimeString());
            $this->info('End date: ' . $intervalEnd->toDateTimeString());

            $thisRow = $csvData[0];

            $thisRow['Date Start'] = $intervalStart->toDateString();
            $thisRow['Date End'] = $intervalEnd->toDateString();

            foreach ($brands as $brand) {
                $stats =
                    $this->membershipStatsRepository->getStats(
                        $intervalStart->copy(),
                        $intervalEnd->copy(),
                        null,
                        $brand
                    );

                // sum of all TOTAL recurring interval types and lifetimes at end of period
                // - lifetime
                // - one year
                // - six months
                // - one month
                $totalMemberships = 0;

                // sum of all NEW recurring interval types and lifetimes
                // - lifetime
                // - one year
                // - six months
                // - one month
                $newMemberships = 0;

                // total other non-recurring memberships at end of period (excluding lifetimes)
                // - other
                $newNonRecurringMemberships = 0;

                foreach ($stats as $membershipStatsIndex => $membershipStats) {
                    if (in_array(
                        $membershipStats->getIntervalType(),
                        ['one month', 'six months', 'one year', 'lifetime']
                    )) {
                        $newMemberships += $membershipStats->getNew();
                    }

                    if (in_array(
                        $membershipStats->getIntervalType(),
                        ['one month', 'six months', 'one year', 'lifetime', 'other']
                    )) {
                        // do the totals since the latest stat is always on top
//                        var_dump($membershipStats->getStatsDate());
                        if ($membershipStats->getStatsDate() == $intervalEnd->copy()->startOfDay()) {
                            $totalMemberships += $membershipStats->getActiveState();
                        }
                    }

                    if (in_array(
                        $membershipStats->getIntervalType(),
                        ['other']
                    )) {
                        // do the totals since the latest stat is always on top
                        if ($membershipStats->getStatsDate() == $intervalEnd->copy()->startOfDay()) {
                            $newNonRecurringMemberships += $membershipStats->getActiveState();
                        }
                    }
                }

                $thisRow[ucwords($brand) . ' Total Memberships'] =+ $totalMemberships;
                $thisRow[ucwords($brand) . ' New Memberships'] = $newMemberships;
                $thisRow[ucwords($brand) . ' New Non-recurring Memberships'] = $newNonRecurringMemberships;

            }

            $csvData[] = array_values($thisRow);

            $dateInterval->addMonth();
        }

        $filePath = "membership_stats_" .
            time() .
            "_" .
            $startDate->toDateTimeString() .
            "_" .
            $endDate->toDateTimeString() .
            ".csv";

        $f = fopen($filePath, "w");

        foreach ($csvData as $line) {
            fputcsv($f, $line);
        }

        fclose($f);

        $this->info('Done!');
    }
}
