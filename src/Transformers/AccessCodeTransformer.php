<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AccessCode;

class AccessCodeTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];
    protected $productsMap;

    public function __construct(array $productsMap = [])
    {
        $this->productsMap = $productsMap;
    }

    public function transform(AccessCode $accessCode)
    {
        if ($accessCode->getClaimer()) {
            $this->defaultIncludes[] = 'claimer';
        }

        if (count($this->productsMap)) {
            $this->defaultIncludes[] = 'product';
        }

        return [
            'id' => $accessCode->getId(),
            'code' => $accessCode->getCode(),
            'brand' => $accessCode->getBrand(),
            'product_ids' => $accessCode->getProductIds() ?? [],
            'is_claimed' => $accessCode->getIsClaimed(),
            'note' => $accessCode->getNote(),
            'claimed_on' => $accessCode->getClaimedOn() ?
                $accessCode->getClaimedOn()
                    ->toDateTimeString() : null,
            'created_at' => $accessCode->getCreatedAt() ?
                $accessCode->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $accessCode->getUpdatedAt() ?
                $accessCode->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    public function includeClaimer(AccessCode $accessCode)
    {
        $userProvider = app()->make(UserProviderInterface::class);

        $userTransformer = $userProvider->getUserTransformer();

        return $this->item(
            $accessCode->getClaimer(),
            $userTransformer,
            'user'
        );
    }

    public function includeProduct(AccessCode $accessCode)
    {
        // extract and include related products
        return $this->collection(
            array_intersect_key(
                $this->productsMap,
                array_flip($accessCode->getProductIds())
            ),
            new ProductTransformer(),
            'product'
        );
    }
}
