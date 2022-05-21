<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\UserPaymentMethods;

class UserPaymentMethodsTransformer extends TransformerAbstract
{
    protected $creditCardsMap;
    protected array $defaultIncludes = ['user'];
    protected $paypalAgreementsMap;

    /**
     * UserPaymentMethodsTransformer constructor.
     *
     * @param array $creditCardsMap
     * @param array $paypalAgreementsMap
     */
    public function __construct(
        $creditCardsMap = [],
        $paypalAgreementsMap = []
    )
    {

        $this->creditCardsMap = $creditCardsMap;
        $this->paypalAgreementsMap = $paypalAgreementsMap;
    }

    /**
     * @param UserPaymentMethods $userPaymentMethod
     *
     * @return array
     */
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

    /**
     * @param UserPaymentMethods $userPaymentMethod
     *
     * @return Item
     */
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
