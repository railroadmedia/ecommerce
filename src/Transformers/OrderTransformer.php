<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Order;

class OrderTransformer extends TransformerAbstract
{
    public function transform(Order $order)
    {
        return [
            'id' => $order->getId(),
            'due' => $order->getDue(),
            'tax' => $order->getTax(),
            'shipping_costs' => $order->getShippingCosts(),
            'paid' => $order->getPaid(),
            'brand' => $order->getBrand(),
            'deleted_on' => $order->getDeletedOn() ? $order->getDeletedOn()->toDateTimeString() : null,
            'created_at' => $order->getCreatedAt() ? $order->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $order->getUpdatedAt() ? $order->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    // todo: add user, customer, shipping & billing addresses relations
}
