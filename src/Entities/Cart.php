<?php

namespace Railroad\Ecommerce\Entities;

use Railroad\Resora\Entities\Entity;

class Cart extends Entity
{
    /**
     * @var CartItem[]
     */
    private static $items = [];

    const CART_SESSION_KEY = 'shopping_cart';

    /**
     * @return CartItem[]
     */
    public static function getItems()
    {
        return self::$items;
    }

    public static function addItem(Product $product, $quantity)
    {
        if (session()->has(self::CART_SESSION_KEY)) {
            $cart = new Cart(unserialize(session()->get(self::CART_SESSION_KEY)));
        } else {
            $cart = new Cart();
        }

        foreach (self::$items as $itemIndex => $item) {
            if ($item->product['id'] == $product['id']) {
                self::$items[$itemIndex]->quantity += $quantity;

                return;
            }
        }

        self::$items[] = new CartItem($product, $quantity);

        return;
    }

    /**
     * Add item to cart. If the item already exists, just increase the quantity.
     *
     * @param string $productSku
     * @param int $quantity
     * @return Cart
     */
    public function addCartItem(
        $productSku,
        $quantity
    ) {
        if (session()->has(self::CART_SESSION_KEY)) {
            $cart = new Cart(unserialize(session()->get(self::CART_SESSION_KEY)));
        } else {
            $cart = new Cart();
        }

        $product = $this->productRepository->query()->where('sku', $productSku)->first();

        if (empty($product)) {
            return $cart;
        }

        $cart->addItem($product, $quantity);

        session()->put(self::CART_SESSION_KEY, serialize($cart));

        return $cart;
    }
}