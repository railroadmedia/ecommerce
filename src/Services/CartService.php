<?php

namespace Railroad\Ecommerce\Services;


use Illuminate\Session\Store;

class CartService
{
    private $session;

    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';
    const PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY = 'payment-plan-number-of-payments';
    const PAYMENT_PLAN_LOCKED_SESSION_KEY = 'order-form-payment-plan-locked';
    const PROMO_CODE_KEY = 'promo-code';

    /**
     * CartService constructor.
     * @param $session
     */
    public function __construct(Store $session)
    {
        $this->session = $session;
    }

    /** Add item to cart. If the item already exists, just increase the quantity.
     * @param string $name
     * @param string $description
     * @param int $quantity
     * @param int $price
     * @param boolean $requiresShippingAddress
     * @param boolean $requiresBillingAddress
     * @param null $subscriptionIntervalType
     * @param null $subscriptionIntervalCount
     * @param array $options
     * @return array
     */
    public function addCartItem(
        $name,
        $description,
        $quantity,
        $price,
        $requiresShippingAddress,
        $requiresBillingAddress,
        $subscriptionIntervalType = null,
        $subscriptionIntervalCount = null,
        $weight,
        $options = []
    )
    {
        $cartItems = $this->getAllCartItems();

        // If the item already exists, just increase the quantity
        foreach ($cartItems as $cartItem) {
            if (!empty($cartItem['options']['product-id']) &&
                $cartItem['options']['product-id'] == $options['product-id']
            ) {
                $cartItem['quantity'] = ($cartItem['quantity'] + $quantity);
                $cartItem['totalPrice'] = $cartItem['quantity'] * $cartItem['price'];
                $cartItem['weight'] = $cartItem['quantity'] * $weight;

                $this->session->put(ConfigService::$brand . '-' . self::SESSION_KEY . $cartItem['id'], $cartItem);

                return $this->getAllCartItems();
            }
        }

       $cartItem = [
           'id' => (bin2hex(openssl_random_pseudo_bytes(32))),
           'name' => $name,
           'description' => $description,
           'quantity' => $quantity,
           'price' => $price,
           'totalPrice' => $quantity * $price,
           'requiresShippingAddress' => $requiresShippingAddress,
           'requiresBillinggAddress' => $requiresBillingAddress,
           'subscriptionIntervalType' => $subscriptionIntervalType,
           'subscriptionIntervalCount' => $subscriptionIntervalCount,
           'weight' => $quantity * $weight,
           'options' => $options
       ];

        $this->session->put(ConfigService::$brand . '-' . self::SESSION_KEY . $cartItem['id'], $cartItem);

        return $this->getAllCartItems();
    }

    /** Return an array with the cart items.
     * @return array
     */
    public function getAllCartItems()
    {
        $cartItems = [];

        foreach ($this->session->all() as $sessionKey => $sessionValue) {
            if (substr($sessionKey, 0, strlen(ConfigService::$brand . '-' . self::SESSION_KEY)) == ConfigService::$brand . '-' . self::SESSION_KEY) {
                $cartItem = $sessionValue;

                if (!empty($cartItem['id'])) {
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
            if (substr($sessionKey, 0, strlen(ConfigService::$brand . '-' . self::SESSION_KEY)) == ConfigService::$brand . '-' . self::SESSION_KEY) {
                $this->session->remove($sessionKey);
            }
        }
    }

    /** Clear and lock the cart
     */
    public function lockCart()
    {
        $this->removeAllCartItems();

        $this->session->put(ConfigService::$brand . '-' . self::LOCKED_SESSION_KEY, true);
    }

    /** Check if the cart it's in locked state
     * @return bool
     */
    public function isLocked()
    {
        return $this->session->get(ConfigService::$brand . '-' . self::LOCKED_SESSION_KEY) == true;
    }

    /**
     * Clear and unlock the cart
     */
    public function unlockCart()
    {
        $this->removeAllItems();
        $this->unlockPaymentPlan();

        $this->session->put(ConfigService::$brand . '-' . self::LOCKED_SESSION_KEY, false);
    }

    /** Remove the cart item
     * @param $id
     */
    public function removeCartItem($id)
    {
        if ($this->session->has(ConfigService::$brand . '-' . self::SESSION_KEY . $id)) {
            $this->session->remove(ConfigService::$brand . '-' . self::SESSION_KEY . $id);
        }
    }

    /** Update cart item quantity and total price.
     * @param $cartItemId
     * @param $quantity
     */
    public function updateCartItemQuantity($cartItemId, $quantity)
    {
        $cartItem = $this->getCartItem($cartItemId);
        $cartItem['quantity'] = $quantity;
        $cartItem['totalPrice'] = $quantity * $cartItem['price'];

        $this->session->put(ConfigService::$brand . '-' . self::SESSION_KEY . $cartItemId, $cartItem);
    }

    /** Get a cart item from the session based on cart item id
     * @param $id
     * @return mixed|null
     */
    public function getCartItem($id)
    {
        if ($this->session->has(ConfigService::$brand . '-' . self::SESSION_KEY . $id)) {
            $cartItem = $this->session->get(ConfigService::$brand . '-' . self::SESSION_KEY . $id);

            if (!empty($cartItem)) {
                return $cartItem;
            }
        }
        return null;
    }

    /** Set on the session the number of payments
     * @param $numberOfPayments
     */
    public function setPaymentPlanNumberOfPayments($numberOfPayments)
    {
        if (empty($numberOfPayments) || $numberOfPayments == 1) {
            $this->session->put(self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY, 1);
        } else {
            $this->session->put(self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY, $numberOfPayments);
        }
    }

    /** Get the number of payments
     * @return mixed|integer
     */
    public function getPaymentPlanNumberOfPayments()
    {
        if ($this->session->has(self::PAYMENT_PLAN_LOCKED_SESSION_KEY) &&
            $this->session->get(self::PAYMENT_PLAN_LOCKED_SESSION_KEY) > 0
        ) {
            return $this->session->get(self::PAYMENT_PLAN_LOCKED_SESSION_KEY, 1);
        }

        return $this->session->get(self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY, 1);
    }

    /** Lock payment plan
     * @param $numberOfPaymentsToForce
     */
    public function lockPaymentPlan($numberOfPaymentsToForce)
    {
        $this->session->put(self::PAYMENT_PLAN_LOCKED_SESSION_KEY, $numberOfPaymentsToForce);
    }

    /**
     * Unlock payment plan
     */
    public function unlockPaymentPlan()
    {
        $this->session->remove(self::PAYMENT_PLAN_LOCKED_SESSION_KEY);
    }

   /** Set promo code on the session
     * @param string $promoCode
     */
    public function setPromoCode($promoCode)
    {
        $this->session->put(self::PROMO_CODE_KEY, $promoCode);
    }

    /** Get promo code from the session
     * @return mixed
     */
    public function getPromoCode()
    {
        return $this->session->get(self::PROMO_CODE_KEY);
    }

}