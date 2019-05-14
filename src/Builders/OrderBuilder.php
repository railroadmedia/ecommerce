<?php

namespace Railroad\Ecommerce\Builders;

use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Services\CartService;

class OrderBuilder
{
    // todo: WIP

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * OrderBuilder constructor.
     * @param CartService $cartService
     */
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function buildTotalsFromCart(Order $order, Cart $cart)
    {
        return $this->cartService->populateOrderTotals($order);
    }

    public function buildAddresses(Order $order, ?Address $billingAddress = null, ?Address $shippingAddress = null)
    {
        if (empty($billingAddress) &&
            !empty(
            $this->cartService->getCart()
                ->getBillingAddress()
            )) {
            $billingAddress =
                $this->cartService->getCart()
                    ->getBillingAddress()
                    ->toEntity();

            $order->setBillingAddress(
                $this->cartService->getCart()
                    ->getBillingAddress()
                    ->toEntity()
            );
        }
    }
}