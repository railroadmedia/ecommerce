<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;

/**
 * Generic entity reference transformer
 * Serializes Doctrine proxy entities without issuing db queries
 */
class EntityReferenceTransformer extends TransformerAbstract
{
    public function transform($entity)
    {
        return [
            'id' => $entity->getId()
        ];
    }
}
