<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\AccessCode;

class AccessCodeTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    public function transform(AccessCode $accessCode)
    {
        if ($accessCode->getClaimer()) {
            // todo: refine this block to support full claimer include upon request
            $this->defaultIncludes[] = 'claimer';
        }

        // todo: add support for including full products upon request (known current usage in musora)
        // https://discuss.jsonapi.org/t/should-json-api-attributes-element-contain-nested-objects/893/2
        // https://jsonapi.org/format/#document-resource-object-attributes

        return [
            'id' => $accessCode->getId(),
            'code' => $accessCode->getCode(),
            'brand' => $accessCode->getBrand(),
            'product_ids' => $accessCode->getProductIds() ?? [],
            'is_claimed' => $accessCode->getIsClaimed(),
            'claimed_on' => $accessCode->getClaimedOn() ? $accessCode->getClaimedOn()->toDateTimeString() : null,
            'created_at' => $accessCode->getCreatedAt() ? $accessCode->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $accessCode->getUpdatedAt() ? $accessCode->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeClaimer(AccessCode $accessCode)
    {
        return $this->item($accessCode->getClaimer(), new EntityReferenceTransformer(), 'user');
    }
}
