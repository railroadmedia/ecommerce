<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\MembershipStats;

class MembershipStatsTransformer extends TransformerAbstract
{
    public function transform(MembershipStats $membershipStats)
    {
        return [
            'id' => $membershipStats->getId(),
            'new' => $membershipStats->getNew(),
            'active_state' => $membershipStats->getActiveState(),
            'expired' => $membershipStats->getExpired(),
            'suspended_state' => $membershipStats->getSuspendedState(),
            'canceled' => $membershipStats->getCanceled(),
            'canceled_state' => $membershipStats->getCanceledState(),
            'interval_type' => $membershipStats->getIntervalType(),
            'stats_date' => $membershipStats->getStatsDate()
                                ->toDateString(),
            'brand' => $membershipStats->getBrand(),
            'created_at' => $membershipStats->getCreatedAt()
                                ->toDateTimeString(),
            'updated_at' => $membershipStats->getUpdatedAt() ?
                $membershipStats->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }
}
