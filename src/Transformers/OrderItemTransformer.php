<?php

namespace Railroad\Ecommerce\Transformers;

use Doctrine\Common\Persistence\Proxy;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\OrderItem;

class OrderItemTransformer extends TransformerAbstract
{
    protected array $defaultIncludes = ['order', 'product'];

    /**
     * @param OrderItem $orderItem
     *
     * @return array
     */
    public function transform(OrderItem $orderItem)
    {
        return [
            'id' => $orderItem->getId(),
            'quantity' => $orderItem->getQuantity(),
            'weight' => $orderItem->getWeight(),
            'initial_price' => $orderItem->getInitialPrice(),
            'total_discounted' => $orderItem->getTotalDiscounted(),
            'final_price' => $orderItem->getFinalPrice(),
            'created_at' => $orderItem->getCreatedAt() ?
                $orderItem->getCreatedAt()
                    ->toDateTimeString() : null,
            'updated_at' => $orderItem->getUpdatedAt() ?
                $orderItem->getUpdatedAt()
                    ->toDateTimeString() : null,
        ];
    }

    /**
     * @param OrderItem $orderItem
     *
     * @return Item|null
     */
    public function includeOrder(OrderItem $orderItem)
    {
        if (empty($orderItem->getOrder())) {
            return null;
        }

        $transformer = new OrderTransformer();
        $defaultIncludes = $transformer->getDefaultIncludes();
        $transformer->setDefaultIncludes(array_diff($defaultIncludes, ['orderItem']));

        return $this->item(
            $orderItem->getOrder(),
            $transformer,
            'order'
        );
    }

    /**
     * @param OrderItem $orderItem
     *
     * @return Item|null
     */
    public function includeProduct(OrderItem $orderItem)
    {
        if (empty($orderItem->getProduct())) {
            return null;
        }
        
        $transformer = new ProductTransformer();
        $defaultIncludes = $transformer->getDefaultIncludes();
        $transformer->setDefaultIncludes(array_diff($defaultIncludes, ['product']));

        return $this->item(
            $orderItem->getProduct(),
            $transformer,
            'product'
        );
    }
}
