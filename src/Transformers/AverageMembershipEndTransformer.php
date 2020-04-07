<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\AverageMembershipEnd;

class AverageMembershipEndTransformer extends TransformerAbstract
{
    /**
     * @param AverageMembershipEnd $averageMembershipEnd
     *
     * @return array
     */
    public function transform(AverageMembershipEnd $averageMembershipEnd)
    {
        return [
            'id' => $averageMembershipEnd->getId(),
            'brand' => $averageMembershipEnd->getBrand(),
            'subscription_type' => $averageMembershipEnd->getSubscriptionType(),
            'average_membership_end' => $averageMembershipEnd->getAverageMembershipEnd(),
            'interval_start_date' => $averageMembershipEnd->getIntervalStartDate() ?
                                        $averageMembershipEnd->getIntervalStartDate()
                                            ->toDateString()
                                        : null,
            'interval_end_date' => $averageMembershipEnd->getIntervalEndDate() ?
                                        $averageMembershipEnd->getIntervalEndDate()
                                            ->toDateString()
                                        :null,
        ];
    }
}
