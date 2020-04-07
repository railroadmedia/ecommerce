<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\SubscriptionRenewal;

class SubscriptionRenewalTransformer extends TransformerAbstract
{
    /**
     * @param SubscriptionRenewal $subscriptionRenewal
     *
     * @return array
     */
    public function transform(SubscriptionRenewal $subscriptionRenewal)
    {
        return [
            'id' => $subscriptionRenewal->getId(),
            'user_id' => $subscriptionRenewal->getUserId(),
            'brand' => $subscriptionRenewal->getBrand(),
            'subscription_id' => $subscriptionRenewal->getSubscriptionId(),
            'subscription_type' => $subscriptionRenewal->getSubscriptionType(),
            'subscription_state' => $subscriptionRenewal->getSubscriptionState(),
            'next_renewal_due' => $subscriptionRenewal->getNextRenewalDue() ?
                                        $subscriptionRenewal->getNextRenewalDue()
                                            ->toDateString()
                                        : null,
        ];
    }
}
