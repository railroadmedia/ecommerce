<?php

namespace Railroad\Ecommerce\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Requests\RetentionStatsRequest;
use Railroad\Ecommerce\Services\RetentionStatsService;
use Throwable;

class RetentionReportingTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * startDate and endDate can be any date or dateime string
     *
     * @var string
     */
    protected $signature = 'RetentionReportingTool {startDate} {endDate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull retention stats between dates per month.';

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var RetentionStatsService
     */
    private $retentionStatsService;

    /**
     * RetentionReportingTool constructor.
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
        $startDate = Carbon::parse($this->argument('startDate'))->startOfDay();
        $endDate = Carbon::parse($this->argument('endDate'))->endOfDay();
        $dateInterval = $startDate->copy();

        $csvData = [
            [
                'Date Start' => 'Date Start',
                'Date End' => 'Date End',
                'Drumeo 1 Month Rate' => 'Drumeo 1 Month Rate',
                'Drumeo 1 Year Rate' => 'Drumeo 1 Year Rate',
                'Pianote 1 Month Rate' => 'Pianote 1 Month Rate',
                'Pianote 1 Year Rate' => 'Pianote 1 Year Rate',
                'Guitareo 1 Month Rate' => 'Guitareo 1 Month Rate',
                'Guitareo 1 Year Rate' => 'Guitareo 1 Year Rate',
            ]
        ];

        while ($dateInterval <= $endDate) {
            $intervalStart = $dateInterval->copy()->startOfMonth();
            $intervalEnd = $dateInterval->copy()->endOfMonth();

            $this->info('Start date: ' . $intervalStart->toDateTimeString());
            $this->info('End date: ' . $intervalEnd->toDateTimeString());

            $request = new RetentionStatsRequest(
                [
                    'small_date_time' => $intervalStart->toDateTimeString(),
                    'big_date_time' => $intervalEnd,
                ]
            );

            $stats = $this->retentionStatsService->getStats($request);

            $thisRow = $csvData[0];

            foreach ($stats as $stat) {
                $this->info(
                    $stat->getBrand() .
                    ' - ' .
                    $stat->getSubscriptionType() .
                    ': ' .
                    round($stat->getRetentionRate() * 100)
                );


                $thisRow['Date Start'] = $intervalStart->toDateString();
                $thisRow['Date End'] = $intervalEnd->toDateString();

                if ($stat->getSubscriptionType() == 'one_month') {
                    $prettyIntervalName = '1 Month';
                } elseif ($stat->getSubscriptionType() == 'one_year') {
                    $prettyIntervalName = '1 Year';
                } else {
                    $prettyIntervalName = '';
                }

                $thisRow[ucwords($stat->getBrand()) . ' ' . $prettyIntervalName . ' Rate'] =
                    round($stat->getRetentionRate() * 100);

            }

            $csvData[] = array_values($thisRow);

            $dateInterval->addMonth();
        }

        $filePath = "retention_stats_" .
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
