<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\RetentionStatistic;

class RetentionStatsTransformer extends TransformerAbstract
{
    public function transform(RetentionStatistic $retentionStats)
    {
        return [
            'id' => $retentionStats->getId(),
            'brand' => $retentionStats->getBrand(),
            'subscription_type' => $retentionStats->getSubscriptionType(),
            'retention_rate' => $retentionStats->getRetentionRate(),
            'interval_start_date' => $retentionStats->getIntervalStartDate()
                                        ->toDateString(),
            'interval_end_date' => $retentionStats->getIntervalEndDate()
                                        ->toDateString(),
        ];
    }
}
