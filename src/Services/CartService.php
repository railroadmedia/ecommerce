<?php

namespace Railroad\Ecommerce\Services;


use Illuminate\Session\Store;

class CartService
{
    private $session;
    private $items = null;
    private $totalPrice = 0;

    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';

    /**
     * CartService constructor.
     * @param $session
     */
    public function __construct(Store $session)
    {
        $this->session = $session;
    }


    public function addCartItem($name, $description, $quantity, $price, $requiresShippingAddress,
                            $requiresBillingAddress,
                            $subscriptionIntervalType = null,
                            $subscriptionIntervalCount = null,
                            $options = [])
    {
        $cartItems = $this->getAllCartItems();

        // If the item already exists, just increase the quantity
        foreach ($cartItems as $cartItem) {
            if (!empty($cartItem->options['product-id']) &&
                $cartItem->options['product-id'] == $options['product-id']
            ) {
                $cartItem->quantity = ($cartItem->quantity + $quantity);
                $cartItem->totalPrice = $cartItem->quantity * $cartItem->price;

                $this->session->put(self::SESSION_KEY . $cartItem->id, $cartItem);

                return $this->getAllCartItems();
            }
        }
        $cartItem = new \stdClass();

        $cartItem->id = (bin2hex(openssl_random_pseudo_bytes(32)));
        $cartItem->name = $name;
        $cartItem->description = $description;
        $cartItem->quantity = $quantity;
        $cartItem->price = $price;
        $cartItem->totalPrice = $cartItem->quantity * $cartItem->price;
        $cartItem->requiresShippingAddress = $requiresShippingAddress;
        $cartItem->requiresBillinggAddress = $requiresBillingAddress;
        $cartItem->subscriptionIntervalType = $subscriptionIntervalType;
        $cartItem->subscriptionIntervalCount = $subscriptionIntervalCount;
        $cartItem->options = $options;

        $this->session->put(self::SESSION_KEY . $cartItem->id, $cartItem);

        return $this->getAllCartItems();
    }

    /** Return an array with the cart items
     * @return array
     */
    public function getAllCartItems()
    {
        $cartItems = [];

        foreach ($this->session->all() as $sessionKey => $sessionValue) {
            if (substr($sessionKey, 0, strlen(self::SESSION_KEY)) == self::SESSION_KEY) {
                $cartItem = $sessionValue;

                if (!empty($cartItem->id)) {
                    $cartItems[] = $cartItem;
                }
            }
        }

        return $cartItems;
    }

    /** Clear the cart items
     */
    public function removeAllCartItems()
    {
        foreach ($this->session->all() as $sessionKey => $sessionValue) {
            if (substr($sessionKey, 0, strlen(self::SESSION_KEY)) == self::SESSION_KEY) {
                $this->session->remove($sessionKey);
            }
        }
    }

    /** Clear and lock the cart
     */
    public function lockCart()
    {
        $this->removeAllCartItems();

        $this->session->put(self::LOCKED_SESSION_KEY, true);
    }

    /** Check if the cart it's in locked state
     * @return bool
     */
    public function isLocked()
    {
        return $this->session->get(self::LOCKED_SESSION_KEY) == true;
    }

    /**
     * Clear and unlock the cart
     */
    public function unlockCart()
    {
        $this->removeAllItems();

        $this->session->put(self::LOCKED_SESSION_KEY, false);
    }

    /** Remove the cart item
     * @param $id
     */
    public function removeCartItem($id)
    {
        if ($this->session->has(self::SESSION_KEY . $id)) {
            $this->session->remove(self::SESSION_KEY . $id);
        }
    }
}