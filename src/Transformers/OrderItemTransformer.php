<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\OrderItem;

class OrderItemTransformer extends TransformerAbstract
{
    public function transform(OrderItem $orderItem)
    {
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

    // todo: add order and product relations
}
