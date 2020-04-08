<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\RetentionStatistic;

class RetentionStatisticTransformer extends TransformerAbstract
{
    /**
     * @param RetentionStatistic $retentionStatistic
     * @return array
     */
    public function transform(RetentionStatistic $retentionStatistic)
    {
        return [
            'id' => md5(
                $retentionStatistic->getBrand() .
                $retentionStatistic->getSubscriptionType() .
                $retentionStatistic->getIntervalStartDateTime() .
                $retentionStatistic->getIntervalEndDateTime()
            ),
            'brand' => $retentionStatistic->getBrand(),
            'subscription_type' => str_replace('_', ' ', $retentionStatistic->getSubscriptionType()),
            'total_users_in_pool' => $retentionStatistic->getTotalUsersInPool(),
            'total_users_who_upgraded_or_repurchased' => $retentionStatistic->getTotalUsersWhoUpgradedOrRepurchased(),
            'total_users_who_renewed' => $retentionStatistic->getTotalUsersWhoRenewed(),
            'total_users_who_canceled_or_expired' => $retentionStatistic->getTotalUsersWhoCanceledOrExpired(),
            'retention_rate' => round($retentionStatistic->getRetentionRate() * 100, 2),
            'interval_start_date_time' => $retentionStatistic->getIntervalStartDateTime(),
            'interval_end_date_time' => $retentionStatistic->getIntervalEndDateTime(),
        ];
    }
}
