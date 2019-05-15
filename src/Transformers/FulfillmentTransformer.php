<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;

class FulfillmentTransformer extends TransformerAbstract
{
    public function transform(OrderItemFulfillment $orderItemFulfillment)
    {
        $this->defaultIncludes = ['order', 'orderItem'];

        return [
            'id' => $orderItemFulfillment->getId(),
            'status' => $orderItemFulfillment->getStatus(),
            'company' => $orderItemFulfillment->getCompany(),
            'tracking_number' => $orderItemFulfillment->getTrackingNumber(),
            'fulfilled_on' => $orderItemFulfillment->getFulfilledOn() ?
                $orderItemFulfillment->getFulfilledOn()
                    ->toDateTimeString() : null,
            'note' => $orderItemFulfillment->getNote(),
            'created_at' => $orderItemFulfillment->getCreatedAt() ?
                $orderItemFulfillment->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $orderItemFulfillment->getUpdatedAt() ?
                $orderItemFulfillment->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    public function includeOrder(OrderItemFulfillment $orderItemFulfillment)
    {
        if ($orderItemFulfillment->getOrder() instanceof Proxy) {
            return $this->item(
                $orderItemFulfillment->getOrder(),
                new EntityReferenceTransformer(),
                'order'
            );
        }
        else {
            return $this->item(
                $orderItemFulfillment->getOrder(),
                new OrderTransformer(),
                'order'
            );
        }
    }

    public function includeOrderItem(OrderItemFulfillment $orderItemFulfillment)
    {
        if ($orderItemFulfillment->getOrderItem() instanceof Proxy) {
            return $this->item(
                $orderItemFulfillment->getOrderItem(),
                new EntityReferenceTransformer(),
                'orderItem'
            );
        }
        else {
            return $this->item(
                $orderItemFulfillment->getOrderItem(),
                new OrderItemTransformer(),
                'orderItem'
            );
        }
    }
}
