<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\UserPaymentMethods;

class UserPaymentMethodsTransformer extends TransformerAbstract
{
    protected $creditCardsMap;
    protected $defaultIncludes = ['user'];
    protected $paypalAgreementsMap;

    public function __construct(
        $creditCardsMap = [],
        $paypalAgreementsMap = []
    )
    {

        $this->creditCardsMap = $creditCardsMap;
        $this->paypalAgreementsMap = $paypalAgreementsMap;
    }

    public function transform(UserPaymentMethods $userPaymentMethod)
    {
        return [
            'id' => $userPaymentMethod->getId(),
            'is_primary' => $userPaymentMethod->getIsPrimary(),
            'created_at' => $userPaymentMethod->getCreatedAt() ?
                $userPaymentMethod->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $userPaymentMethod->getUpdatedAt() ?
                $userPaymentMethod->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    public function includeUser(UserPaymentMethods $userPaymentMethod)
    {
        $userProvider = app()->make(UserProviderInterface::class);

        $userTransformer = $userProvider->getUserTransformer();

        return $this->item(
            $userPaymentMethod->getUser(),
            $userTransformer,
            'user'
        );
    }
}
