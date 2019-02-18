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
            'total_due' => $order->getTotalDue(),
            'product_due' => $order->getProductDue(),
            'taxes_due' => $order->getTaxesDue(),
            'shipping_due' => $order->getShippingDue(),
            'finance_due' => $order->getFinanceDue(),
            'total_paid' => $order->getTotalPaid(),
            'brand' => $order->getBrand(),
            'deleted_on' => $order->getDeletedOn() ? $order->getDeletedOn()->toDateTimeString() : null,
            'created_at' => $order->getCreatedAt() ? $order->getCreatedAt()->toDateTimeString() : null,
            'updated_at' => $order->getUpdatedAt() ? $order->getUpdatedAt()->toDateTimeString() : null,
        ];
    }

    // todo: add user, customer, shipping & billing addresses relations
}
