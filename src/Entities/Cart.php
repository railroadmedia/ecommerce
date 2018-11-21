<?php

namespace Railroad\Ecommerce\Entities;

use Illuminate\Support\Facades\Session;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Railcontent\Support\Collection;

class Cart
{
    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';
    const PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY = 'payment-plan-number-of-payments';
    const PAYMENT_PLAN_LOCKED_SESSION_KEY = 'order-form-payment-plan-locked';
    const PROMO_CODE_KEY = 'promo-code';

    private $items;
    private $shippingCosts;
    private $totalTax;
    private $totalDue;

    public function getItems()
    {
        $cartItems = [];
        $session = Session::all();

        foreach ($session as $sessionKey => $sessionValue) {
            if (substr($sessionKey, 0, strlen(ConfigService::$brand . '-' . self::SESSION_KEY)) ==
                ConfigService::$brand . '-' . self::SESSION_KEY) {
                $cartItem = $sessionValue;

                if (!empty($cartItem)) {
                    $cartItems = $cartItem->items;
                }
            }
        }

        return $cartItems;
    }

    public function addCartItem($cartItem)
    {
        $cart = $this->getCart();

        $cart->items[] = $cartItem;

        Session::put(ConfigService::$brand . '-' . self::SESSION_KEY, $cart);

        return $cart;
    }

    public function setTaxesDue($taxesDue)
    {
        $this->totalTax = $taxesDue;
    }

    public function calculateTaxesDue()
    {
        return $this->totalTax;
    }

    public function calculateShippingDue()
    {
        return $this->shippingCosts;

    }

    public function getFirstPaymentDue()
    {

    }

    public function getCart()
    {
        $session = Session::all();

        foreach ($session as $sessionKey => $sessionValue) {
            if (substr($sessionKey, 0, strlen(ConfigService::$brand . '-' . self::SESSION_KEY)) ==
                ConfigService::$brand . '-' . self::SESSION_KEY) {
                return $sessionValue;
            }
        }
        return new Cart();
    }

    public function getTotalDue()
    {
        if (!is_null($this->totalDue)) {
            return $this->totalDue;
        }

        $totalDueFromItems = 0;
        $financeCharge = 0;

        foreach (
            $this->getCart()
                ->getItems() as $cartItem
        ) {
            $totalDueFromItems += $cartItem->getTotalPrice();
        }
        return $totalDueFromItems;
        //        $financeCharge = 0;
        //        return round(
        //            $totalDueFromItems -
        //            $this->getAmountDiscounted($totalDueFromItems) +
        //            $this->getTax() +
        //            $this->getShipping() +
        //            $financeCharge,
        //            2
        //        );
    }

}