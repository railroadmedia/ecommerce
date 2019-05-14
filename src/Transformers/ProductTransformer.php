<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Product;

class ProductTransformer extends TransformerAbstract
{
    public function transform(Product $product)
    {
        return [
            'id' => $product->getId(),
            'brand' => $product->getBrand(),
            'name' => $product->getName(),
            'sku' => $product->getSku(),
            'price' => $product->getPrice(),
            'type' => $product->getType(),
            'active' => $product->getActive(),
            'category' => $product->getCategory(),
            'description' => $product->getDescription(),
            'thumbnail_url' => $product->getThumbnailUrl(),
            'is_physical' => $product->getIsPhysical(),
            'weight' => $product->getWeight(),
            'subscription_interval_type' => $product->getSubscriptionIntervalType(),
            'subscription_interval_count' => $product->getSubscriptionIntervalCount(),
            'stock' => $product->getStock(),
            'created_at' => $product->getCreatedAt() ?
                $product->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $product->getUpdatedAt() ?
                $product->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }
}
