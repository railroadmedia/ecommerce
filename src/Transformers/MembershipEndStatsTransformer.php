<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\MembershipEndStats;

class MembershipEndStatsTransformer extends TransformerAbstract
{
    public function transform(MembershipEndStats $membershipEndStats)
    {
        return [
            'id' => $membershipEndStats->getId(),
            'brand' => $membershipEndStats->getBrand(),
            'subscription_type' => $membershipEndStats->getSubscriptionType(),
            'cycles_paid' => $membershipEndStats->getCyclesPaid(),
            'count' => $membershipEndStats->getCount(),
            'interval_start_date' => $membershipEndStats->getIntervalStartDate() ?
                                        $membershipEndStats->getIntervalStartDate()
                                            ->toDateString()
                                        : null,
            'interval_end_date' => $membershipEndStats->getIntervalEndDate() ?
                                        $membershipEndStats->getIntervalEndDate()
                                            ->toDateString()
                                        :null,
        ];
    }
}
