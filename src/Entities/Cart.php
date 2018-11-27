<?php

namespace Railroad\Ecommerce\Entities;

use Illuminate\Support\Facades\Session;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\DiscountService;


class Cart
{
    const SESSION_KEY = 'shopping-cart-';
    const LOCKED_SESSION_KEY = 'order-form-locked';
    const PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY = 'payment-plan-number-of-payments';
    const PAYMENT_PLAN_LOCKED_SESSION_KEY = 'order-form-payment-plan-locked';
    const PROMO_CODE_KEY = 'promo-code';

    public $items;
    private $shippingCosts;
    private $totalTax;
    private $totalDue;
    private $discounts;
    private $totalDiscountAmount;
    public $appliedDiscounts;

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
        $cart->totalDue = $this->getTotalDue();
        $cart->discounts = $this->getDiscounts();
        $cart->shippingCosts = $this->calculateShippingDue();
        $cart->totalTax = $this->calculateTaxesDue();

        Session::put(ConfigService::$brand . '-' . self::SESSION_KEY, $cart);

        return $cart;
    }

    public function setTaxesDue($taxesDue)
    {
        $this->totalTax = $taxesDue;
    }

    public function calculateTaxesDue()
    {
        if (Session::has('cart-address-billing')) {
            $billingAddress = Session::get('cart-address-billing');
            $taxRate = $this->getTaxRate($billingAddress['country'], $billingAddress['region']);
            $this->totalTax =
                max(round(($this->getTotalDueForItems() - $this->getTotalDiscountAmount()) * $taxRate, 2), 0) +
                max(round($this->shippingCosts * $taxRate, 2), 0);
        }
        return max((float)($this->totalTax), 0);
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
        $financeCharge = ($this->getPaymentPlanNumberOfPayments() > 1) ? 1 : 0;
        $totalDueFromItems = $this->getTotalDueForItems();
        return round(
            $totalDueFromItems + $this->calculateTaxesDue() + $this->calculateShippingDue() + $financeCharge,
            2
        );
    }

    public function calculatePricePerPayment()
    {
        if ($this->getPaymentPlanNumberOfPayments() > 1) {
            /*
             * All shipping should always be paid in the first payment.
             */
            return round(
                (($this->getTotalDue() - $this->getTotalDiscountAmount() - $this->calculateShippingDue()) /
                    $this->getPaymentPlanNumberOfPayments()),
                2
            );
        }

        return $this->getTotalDue() - $this->getTotalDiscountAmount();
    }

    public function getPaymentPlanNumberOfPayments()
    {
        if (Session::has(self::PAYMENT_PLAN_LOCKED_SESSION_KEY) &&
            Session::get(self::PAYMENT_PLAN_LOCKED_SESSION_KEY) > 0) {
            return Session::get(self::PAYMENT_PLAN_LOCKED_SESSION_KEY, 1);
        }

        return Session::get(self::PAYMENT_PLAN_NUMBER_OF_PAYMENTS_SESSION_KEY, 1);
    }

    public function setShippingCosts($shipping)
    {
        $this->shippingCosts = $shipping;

        return $this;
    }

    /**
     * @param bool $applyDiscounts
     * @return float
     */
    public function calculateShippingDue($applyDiscounts = true)
    {
        $amountDiscounted = 0;

        if ($applyDiscounts) {
            foreach ($this->getDiscounts() as $discount) {
                if ($discount->type == DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE) {
                    $amountDiscounted += $discount->amount;
                } elseif ($discount->type == DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE) {
                    $amountDiscounted += $discount->amount / 100 * $this->shippingCosts;
                } elseif ($discount->type == DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {
                    return $discount->amount;
                }
            }
        }

        return max((float)($this->shippingCosts - $amountDiscounted), 0);
    }

    /**
     * @return Discount[]
     */
    public function getDiscounts()
    {
        return $this->discounts ?? [];
    }

    /**
     * @param Discount $discount
     */
    public function addDiscount($discount)
    {
        $this->discounts = $discount;
    }

    public function getTotalWeight()
    {
        $weight = 0.0;

        foreach (
            $this->getCart()
                ->getItems() as $cartItem
        ) {
            $weight += $cartItem->getProduct()->weight * $cartItem->getQuantity();
        }

        return $weight;
    }

    public function calculateInitialPricePerPayment()
    {
        if ($this->getPaymentPlanNumberOfPayments() > 1) {
            /*
             * We need to make sure we add any rounded off $$ back to the first payment.
             */
            $roundingFirstPaymentAdjustment =
                ($this->calculatePricePerPayment() * $this->getPaymentPlanNumberOfPayments()) -
                ($this->getTotalDue() - $this->calculateShippingDue());

            return round(
                $this->calculatePricePerPayment() - $roundingFirstPaymentAdjustment + $this->calculateShippingDue(),
                2
            );
        }

        return $this->calculatePricePerPayment();
    }

    public function calculateCartItemsSubTotalAfterDiscounts()
    {
        return max((float)($this->totalDue - $this->totalDiscountAmount + $this->shippingCosts + $this->totalTax), 0);
    }

    public function setTotalDiscountAmount($discount)
    {
        $this->totalDiscountAmount = $discount;
    }

    public function getTotalDiscountAmount()
    {
        return $this->totalDiscountAmount;
    }

    /**
     * @return Discount[]
     */
    public function getAppliedDiscounts()
    {
        return $this->appliedDiscounts ?? [];
    }

    /**
     * @param Discount $discount
     */
    public function addAppliedDiscount($discount)
    {
        $this->appliedDiscounts[] = $discount;
    }

    public function getTaxRate($country, $region)
    {
        if (array_key_exists(strtolower($country), ConfigService::$taxRate)) {
            if (array_key_exists(strtolower($region), ConfigService::$taxRate[strtolower($country)])) {
                return ConfigService::$taxRate[strtolower($country)][strtolower($region)];
            } else {
                return 0.05;
            }
        } else {
            return 0;
        }
    }

    public function getTotalDueForItems()
    {
        $totalDueFromItems = 0;

        foreach (
            $this->getCart()
                ->getItems() as $cartItem
        ) {
            $totalDueFromItems += $cartItem->getTotalPrice();
        }

        return $totalDueFromItems;
    }

}