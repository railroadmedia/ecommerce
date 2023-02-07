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
            'fulfillment_sku' => $product->getFulfillmentSku(),
            'inventory_control_sku' => $product->getInventoryControlSku(),
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
            'min_stock_level' => $product->getMinStockLevel(),
            'public_stock_count' => $product->getPublicStockCount(),
            'auto_decrement_stock' => $product->getAutoDecrementStock(),
            'digital_access_time_interval_length' =>$product->getDigitalAccessTimeIntervalLength(),
            'digital_access_time_type' => $product->getDigitalAccessTimeType(),
            'digital_access_time_interval_type' => $product->getDigitalAccessTimeIntervalType(),
            'digital_access_type' => $product->getDigitalAccessType(),
            'digital_membership_access_expiration_date' => !empty($product->getDigitalMembershipAccessExpirationDate()) ?
                $product->getDigitalMembershipAccessExpirationDate()
                    ->toDateString() : null,
            'digital_access_permission_names' => $product->getDigitalAccessPermissionNames(),
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
