<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\RetentionStats;
use Railroad\Ecommerce\Entities\Structures\AverageMembershipEnd;
use Railroad\Ecommerce\Entities\Structures\RetentionStatistic;
use Railroad\Ecommerce\Repositories\RetentionStatsRepository;
use Railroad\Ecommerce\Requests\RetentionStatsRequest;

class RetentionStatsService
{
    /**
     * @var RetentionStatsRepository
     */
    protected $retentionStatsRepository;

    /**
     * ShippingService constructor.
     *
     * @param RetentionStatsRepository $retentionStatsRepository
     */
    public function __construct(
        RetentionStatsRepository $retentionStatsRepository
    ) {
        $this->retentionStatsRepository = $retentionStatsRepository;
    }

    /**
     * @param RetentionStatsRequest $request
     *
     * @return array
     */
    public function getStats(RetentionStatsRequest $request): array
    {
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

        $intervalType = $request->get('interval_type');
        $brand = $request->get('brand');

        $stats = $this->retentionStatsRepository->getStats(
            $smallDateTime,
            $bigDateTime,
            $intervalType,
            $brand
        );

        $statsMap = [];

        foreach ($stats as $stat) {
            $brand = $stat->getBrand();
            $subType = $stat->getSubscriptionType();

            if (!isset($statsMap[$brand])) {
                $statsMap[$brand] = [];
            }

            if (!isset($statsMap[$brand][$subType])) {
                $statsMap[$brand][$subType] = [
                    'start' => null,
                    'end' => null,
                    'cs' => 0,
                    'cn' => 0,
                    'ce' => 0,
                ];
            }

            if (
                !$statsMap[$brand][$subType]['start']
                || $statsMap[$brand][$subType]['start'] > $stat->getIntervalStartDate()
            ) {
                $statsMap[$brand][$subType]['start'] = $stat->getIntervalStartDate();
                $statsMap[$brand][$subType]['cs'] = $stat->getCustomersStart();
            }

            if (
                !$statsMap[$brand][$subType]['end']
                || $statsMap[$brand][$subType]['end'] > $stat->getIntervalEndDate()
            ) {
                $statsMap[$brand][$subType]['end'] = $stat->getIntervalEndDate();
                $statsMap[$brand][$subType]['ce'] = $stat->getCustomersEnd();
            }

            $statsMap[$brand][$subType]['cn'] += $stat->getCustomersNew();
        }

        $result = [];

        foreach ($statsMap as $brand => $brandStats) {

            foreach ($brandStats as $subType => $stat) {

                if (!$stat['cs']) {
                    continue;
                }

                $statIdString = $brand
                    . $subType
                    . $stat['start']->toDateString()
                    . $stat['end']->toDateString();

                $statObj = new RetentionStatistic(md5($statIdString));

                $retRate = round((($stat['ce'] - $stat['cn']) / $stat['cs']) * 100, 2);

                $statObj->setBrand($brand);
                $statObj->setSubscriptionType($subType);
                $statObj->setRetentionRate($retRate);
                $statObj->setIntervalStartDate($stat['start']);
                $statObj->setIntervalEndDate($stat['end']);

                $result[] = $statObj;
            }
        }

        return $result;
    }

    /**
     * @param Carbon $smallDate
     * @param Carbon $bigDate
     *
     * @return array
     */
    public function getAverageMembershipEnd(Request $request): array
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

        $rawStatsOneMonth = $this->retentionStatsRepository->getAverageStatsOneMonth(
            $smallDate,
            $bigDate,
            $intervalType,
            $brand
        );

        $statsOneMonth = [];

        foreach ($rawStatsOneMonth as $rawOneMonth) {
            $brand = $rawOneMonth['brand'];
            if (!isset($statsOneMonth[$brand])) {
                $statsOneMonth[$brand] = [
                    'weightedSum' => 0,
                    'sum' => 0,
                ];
            }
            $statsOneMonth[$brand]['weightedSum'] += $rawOneMonth['totalCyclesPaid'] * $rawOneMonth['count'];
            $statsOneMonth[$brand]['sum'] += $rawOneMonth['count'];
        }

        $type = RetentionStats::TYPE_ONE_MONTH;

        foreach ($statsOneMonth as $brand => $brandStats) {

            $id = md5($brand . $type);

            $stat = new AverageMembershipEnd($id);

            $stat->setBrand($brand);
            $stat->setSubscriptionType($type);
            $stat->setAverageMembershipEnd(round($brandStats['weightedSum'] / $brandStats['sum'], 2));
            $stat->setIntervalStartDate($smallDate);
            $stat->setIntervalEndDate($bigDate);

            $result[] = $stat;
        }

        $rawStatsSixMonths = $this->retentionStatsRepository->getAverageStatsSixMonths(
            $smallDate,
            $bigDate,
            $intervalType,
            $brand
        );

        $statsSixMonths = [];

        foreach ($rawStatsSixMonths as $rawSixMonths) {
            $brand = $rawSixMonths['brand'];
            if (!isset($statsSixMonths[$brand])) {
                $statsSixMonths[$brand] = [
                    'weightedSum' => 0,
                    'sum' => 0,
                ];
            }
            $statsSixMonths[$brand]['weightedSum'] += $rawSixMonths['totalCyclesPaid'] * $rawSixMonths['count'];
            $statsSixMonths[$brand]['sum'] += $rawSixMonths['count'];
        }

        $type = RetentionStats::TYPE_SIX_MONTHS;

        foreach ($statsSixMonths as $brand => $brandStats) {

            $id = md5($brand . $type);

            $stat = new AverageMembershipEnd($id);

            $stat->setBrand($brand);
            $stat->setSubscriptionType($type);
            $stat->setAverageMembershipEnd(round($brandStats['weightedSum'] / $brandStats['sum'], 2));
            $stat->setIntervalStartDate($smallDate);
            $stat->setIntervalEndDate($bigDate);

            $result[] = $stat;
        }

        $rawStatsOneYear = $this->retentionStatsRepository->getAverageStatsOneYear(
            $smallDate,
            $bigDate,
            $intervalType,
            $brand
        );

        $statsOneYear = [];

        foreach ($rawStatsOneYear as $rawOneYear) {
            $brand = $rawOneYear['brand'];
            if (!isset($statsOneYear[$brand])) {
                $statsOneYear[$brand] = [
                    'weightedSum' => 0,
                    'sum' => 0,
                ];
            }
            $statsOneYear[$brand]['weightedSum'] += $rawOneYear['totalCyclesPaid'] * $rawOneYear['count'];
            $statsOneYear[$brand]['sum'] += $rawOneYear['count'];
        }

        $type = RetentionStats::TYPE_ONE_YEAR;

        foreach ($statsOneYear as $brand => $brandStats) {

            $id = md5($brand . $type);

            $stat = new AverageMembershipEnd($id);

            $stat->setBrand($brand);
            $stat->setSubscriptionType($type);
            $stat->setAverageMembershipEnd(round($brandStats['weightedSum'] / $brandStats['sum'], 2));
            $stat->setIntervalStartDate($smallDate);
            $stat->setIntervalEndDate($bigDate);

            $result[] = $stat;
        }

        return $result;
    }

    /**
     * @param Carbon $smallDate
     * @param Carbon $bigDate
     *
     * @return array
     */
    public function getIntervals(Carbon $smallDate, Carbon $bigDate): array
    {
        $intervalStart = $smallDate->copy()->subDays($smallDate->dayOfWeek)->startOfDay();
        $intervalEnd = $intervalStart->copy()->addDays(6)->endOfDay();

        $lastDay = $bigDate->copy()->addDays(6 - $bigDate->dayOfWeek)->endOfDay();

        $result = [
            [
                'start' => $intervalStart,
                'end' => $intervalEnd,
            ]
        ];

        while ($intervalEnd < $lastDay) {

            $intervalStart = $intervalStart->copy()->addDays(7);
            $intervalEnd = $intervalEnd->copy()->addDays(7);

            $result[] = [
                'start' => $intervalStart,
                'end' => $intervalEnd,
            ];
        }

        return $result;
    }
}
