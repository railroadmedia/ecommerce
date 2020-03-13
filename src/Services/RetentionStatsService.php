<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
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
                    continue; // todo - need to clarify this
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
}
