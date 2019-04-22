<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\OrderItem;

class OrderItemTransformer extends TransformerAbstract
{
    public static $transformedOrders = [];
    public static $transformedProducts = [];

    public function transform(OrderItem $orderItem)
    {
        if (
            $orderItem->getOrder() &&
            !isset(self::$transformedOrders[$orderItem->getOrder()->getId()])
        ) {
            $this->defaultIncludes[] = 'order';
        }

        if (
            $orderItem->getProduct() &&
            !isset(self::$transformedProducts[$orderItem->getProduct()->getId()])
        ) {
            $this->defaultIncludes[] = 'product';
        }

        return [
            'id' => $orderItem->getId(),
            'quantity' => $orderItem->getQuantity(),
            'weight' => $orderItem->getWeight(),
            'initial_price' => $orderItem->getInitialPrice(),
            'total_discounted' => $orderItem->getTotalDiscounted(),
            'final_price' => $orderItem->getFinalPrice(),
            'created_at' => $orderItem->getCreatedAt() ? $orderItem->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $orderItem->getUpdatedAt() ? $orderItem->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    public function includeOrder(OrderItem $orderItem)
    {
        self::$transformedOrders[$orderItem->getOrder()->getId()] = true;

        if ($orderItem->getOrder() instanceof Proxy) {
            return $this->item(
                $orderItem->getOrder(),
                new EntityReferenceTransformer(),
                'order'
            );
        } else {
            return $this->item(
                $orderItem->getOrder(),
                new OrderTransformer(),
                'order'
            );
        }
    }

    public function includeProduct(OrderItem $orderItem)
    {
        self::$transformedProducts[$orderItem->getProduct()->getId()] = true;

        if ($orderItem->getProduct() instanceof Proxy) {
            return $this->item(
                $orderItem->getProduct(),
                new EntityReferenceTransformer(),
                'product'
            );
        } else {
            return $this->item(
                $orderItem->getProduct(),
                new ProductTransformer(),
                'product'
            );
        }
    }
}
