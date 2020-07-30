<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\MembershipAction;

class MembershipActionTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    /**
     * @param MembershipAction $membershipAction
     * @return array
     */
    public function transform(MembershipAction $membershipAction)
    {
        $this->defaultIncludes = [];

        if ($membershipAction->getUser()) {
            $this->defaultIncludes[] = 'user';
        }

        if ($membershipAction->getSubscription()) {
            $this->defaultIncludes[] = 'subscription';
        }

        return [
            'id' => $membershipAction->getId(),
            'action' => $membershipAction->getAction(),
            'action_amount' => $membershipAction->getActionAmount(),
            'action_reason' => $membershipAction->getActionReason(),
            'brand' => $membershipAction->getBrand(),
            'note' => $membershipAction->getNote(),
            'created_at' => $membershipAction->getCreatedAt() ?
                $membershipAction->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $membershipAction->getUpdatedAt() ?
                $membershipAction->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    /**
     * @param MembershipAction $membershipAction
     * @return Item
     */
    public function includeUser(MembershipAction $membershipAction)
    {
        $userProvider = app()->make(UserProviderInterface::class);

        $userTransformer = $userProvider->getUserTransformer();

        return $this->item(
            $membershipAction->getUser(),
            $userTransformer,
            'user'
        );
    }

    /**
     * @param MembershipAction $membershipAction
     *
     * @return Item|null
     */
    public function includeSubscription(MembershipAction $membershipAction)
    {
        if (empty($membershipAction->getSubscription())) {
            return null;
        }

        return $this->item(
            $membershipAction->getSubscription(),
            new SubscriptionTransformer(),
            'subscription'
        );
    }
}
