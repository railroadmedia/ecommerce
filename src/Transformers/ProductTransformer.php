<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Product;

class ProductTransformer extends TransformerAbstract
{
    /**
     * @param Product $product
     *
     * @return array
     */
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
            'sales_page_url' => $product->getSalesPageUrl(),
            'is_physical' => $product->getIsPhysical(),
            'weight' => $product->getWeight(),
            'subscription_interval_type' => $product->getSubscriptionIntervalType(),
            'subscription_interval_count' => $product->getSubscriptionIntervalCount(),
            'stock' => $product->getStock(),
            'auto_decrement_stock' => $product->getAutoDecrementStock(),
            'note' => $product->getNote(),
            'created_at' => $product->getCreatedAt() ?
                $product->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $product->getUpdatedAt() ?
                $product->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }
}
