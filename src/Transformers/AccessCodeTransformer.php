<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\AccessCode;

class AccessCodeTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];
    protected $productsMap;

    /**
     * AccessCodeTransformer constructor.
     *
     * @param array $productsMap
     */
    public function __construct(array $productsMap = [])
    {
        $this->productsMap = $productsMap;
    }

    /**
     * @param AccessCode $accessCode
     *
     * @return array
     */
    public function transform(AccessCode $accessCode)
    {
        $this->defaultIncludes = [];

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
            'source' => $accessCode->getSource(),
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

    /**
     * @param AccessCode $accessCode
     *
     * @return Item
     */
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

    /**
     * @param AccessCode $accessCode
     *
     * @return Collection
     */
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
