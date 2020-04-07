<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\CartItem;

class CartItemTransformer extends TransformerAbstract
{
    /**
     * @param CartItem $cartItem
     *
     * @return array
     */
    public function transform(CartItem $cartItem)
    {
        return [
            'id' => $cartItem->getId(),
            'name' => $cartItem->getName(),
            'description' => $cartItem->getDescription(),
            'quantity' => $cartItem->getQuantity(),
            'price' => $cartItem->getPrice(),
            'totalPrice' => $cartItem->getTotalPrice(),
            'requiresShippingAddress' => $cartItem->getRequiresShippingAddress(),
            'requiresBillingAddress' => $cartItem->getRequiresBillingAddress(),
            'subscriptionIntervalType' => $cartItem->getSubscriptionIntervalType(),
            'subscriptionIntervalCount' => $cartItem->getSubscriptionIntervalCount(),
            'discountedPrice' => $cartItem->getDiscountedPrice(),
            'options' => $cartItem->getOptions()
        ];
    }
}
