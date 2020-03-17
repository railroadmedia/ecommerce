<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\RetentionStats;
use Railroad\Ecommerce\Entities\Structures\AverageMembershipEnd;
use Railroad\Ecommerce\Entities\Structures\MembershipEndStats;
use Railroad\Ecommerce\Entities\Structures\RetentionStatistic;
use Railroad\Ecommerce\Repositories\RetentionStatsRepository;
use Railroad\Ecommerce\Requests\AverageMembershipEndRequest;
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
     * @param AverageMembershipEndRequest $request
     *
     * @return array
     */
    public function getAverageMembershipEnd(AverageMembershipEndRequest $request): array
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

        $intervalTypes = [
            RetentionStats::TYPE_ONE_MONTH => [
                'type' => config('ecommerce.interval_type_monthly'),
                'count' => 1,
            ],
            RetentionStats::TYPE_SIX_MONTHS => [
                'type' => config('ecommerce.interval_type_monthly'),
                'count' => 6,
            ],
            RetentionStats::TYPE_ONE_YEAR => [
                'type' => config('ecommerce.interval_type_yearly'),
                'count' => 1,
            ]
        ];

        foreach ($intervalTypes as $type => $typeDetails) {

            if ($intervalType == null || $type == $intervalType) {

                $rawStats = $this->retentionStatsRepository->getAverageMembershipEnd(
                    $typeDetails['type'],
                    $typeDetails['count'],
                    $smallDate,
                    $bigDate,
                    $brand
                );

                $stats = [];

                foreach ($rawStats as $rawStat) {
                    $statBrand = $rawStat['brand'];
                    if (!isset($stats[$statBrand])) {
                        $stats[$statBrand] = [
                            'weightedSum' => 0,
                            'sum' => 0,
                        ];
                    }
                    $stats[$statBrand]['weightedSum'] += $rawStat['totalCyclesPaid'] * $rawStat['count'];
                    $stats[$statBrand]['sum'] += $rawStat['count'];
                }

                foreach ($stats as $statBrand => $brandStats) {

                    $id = md5($statBrand . $type);

                    $stat = new AverageMembershipEnd($id);

                    $stat->setBrand($statBrand);
                    $stat->setSubscriptionType($type);
                    $stat->setAverageMembershipEnd(round($brandStats['weightedSum'] / $brandStats['sum'], 2));
                    $stat->setIntervalStartDate($smallDate);
                    $stat->setIntervalEndDate($bigDate);

                    $result[] = $stat;
                }
            }
        }

        return $result;
    }

    /**
     * @param AverageMembershipEndRequest $request
     *
     * @return array
     */
    public function getMembershipEndStats(Request $request): array
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

        $intervalTypes = [
            RetentionStats::TYPE_ONE_MONTH => [
                'type' => config('ecommerce.interval_type_monthly'),
                'count' => 1,
            ],
            RetentionStats::TYPE_SIX_MONTHS => [
                'type' => config('ecommerce.interval_type_monthly'),
                'count' => 6,
            ],
            RetentionStats::TYPE_ONE_YEAR => [
                'type' => config('ecommerce.interval_type_yearly'),
                'count' => 1,
            ]
        ];

        foreach ($intervalTypes as $type => $typeDetails) {
            if ($intervalType == null || $type == $intervalType) {
                $rawStats = $this->retentionStatsRepository->getAverageMembershipEnd(
                    $typeDetails['type'],
                    $typeDetails['count'],
                    $smallDate,
                    $bigDate,
                    $brand
                );

                foreach ($rawStats as $rawStat) {

                    $id = md5($rawStat['brand'] . $type . $rawStat['totalCyclesPaid']);

                    $stat = new MembershipEndStats($id);

                    $stat->setBrand($rawStat['brand']);
                    $stat->setSubscriptionType($type);
                    $stat->setCyclesPaid($rawStat['totalCyclesPaid']);
                    $stat->setCount($rawStat['count']);
                    $stat->setIntervalStartDate($smallDate);
                    $stat->setIntervalEndDate($bigDate);

                    $result[] = $stat;
                }
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
