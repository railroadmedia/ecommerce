<?php

namespace Railroad\Ecommerce\Services;

class DiscountService
{
    const PRODUCT_AMOUNT_OFF_TYPE = 'product amount off';
    const PRODUCT_PERCENT_OFF_TYPE = 'product percent off';
    const SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE = 'subscription free trial days';
    const SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE = 'subscription recurring price amount off';
    const ORDER_TOTAL_AMOUNT_OFF_TYPE = 'order total amount off';
    const ORDER_TOTAL_PERCENT_OFF_TYPE = 'order total percent off';
    const ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE = 'order total shipping amount off';
    const ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE = 'order total shipping percent off';
    const ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE = 'order total shipping overwrite';

    /**
     * @param $discountsToApply
     * @param $cartItems
     * @return mixed
     */
    public function applyDiscounts($discountsToApply, $cart)
    {
        foreach ($discountsToApply as $discount) {
            // save raw in order item discounts
            if ($discount['type'] == self::PRODUCT_AMOUNT_OFF_TYPE ||
                $discount['type'] == self::PRODUCT_PERCENT_OFF_TYPE ||
                $discount['type'] == self::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE ||
                $discount['type'] == self::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {

                foreach ($cart->getItems() as $key => $cartItem) {
                     if (($cartItem->getProduct()['id'] == $discount['product_id']) ||
                        ($cartItem->getProduct()['category'] == $discount['product_category'])) {
                        $cartItem->addAppliedDiscount($discount);
                    }
                }
            }

            // Order/shipping total discounts
            if ($discount['type'] == self::ORDER_TOTAL_AMOUNT_OFF_TYPE ||
                $discount['type'] == self::ORDER_TOTAL_PERCENT_OFF_TYPE ||
                $discount['type'] == self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE ||
                $discount['type'] == self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE ||
                $discount['type'] == self::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {

                $cart->addAppliedDiscount($discount);

            }
        }

        return $cart->getItems();
    }

    /**
     * @param $discountsToApply
     * @param $cartItemsTotalDue
     * @return float|int
     */
    public function getAmountDiscounted($discountsToApply, $cartItemsTotalDue, $cartItems)
    {
        $amountDiscounted = 0;

        foreach ($discountsToApply as $discount) {
            foreach ($cartItems as $cartItem) {
                if ($discount['type'] == self::ORDER_TOTAL_AMOUNT_OFF_TYPE) {
                    $amountDiscounted += $discount['amount'];
                    break;
                } elseif ($discount['type'] == self::ORDER_TOTAL_PERCENT_OFF_TYPE) {
                    $amountDiscounted += $discount['amount'] / 100 * $cartItemsTotalDue;
                    break;
                } elseif ($discount['type'] == self::PRODUCT_AMOUNT_OFF_TYPE ||
                    $discount['type'] == self::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                    if (($cartItem->getProduct()['id'] == $discount['product_id']) ||
                        ($cartItem->getProduct()['category'] == $discount['product_category'])) {
                        //Check product price and discount amount.
                        //IF discount amount it's greater that product price we use product price as discounted amount to avoid negative value
                        $amountDiscounted += ($discount['amount'] > $cartItem->getPrice()) ? $cartItem->getPrice() :
                            $discount['amount'] * $cartItem->getQuantity();
                       // $cartItem->addDiscount($discount);
                    }
                } elseif ($discount['type'] == self::PRODUCT_PERCENT_OFF_TYPE) {

                    if (($cartItem->getProduct()['id'] == $discount['product_id']) ||
                        ($cartItem->getProduct()['category'] == $discount['product_category'])) {
                        $amountDiscounted += $discount['amount'] / 100 * $cartItem->getPrice() * $cartItem->getQuantity();
                        //$cartItem->addDiscount($discount);
                    }
                }
            }
        }

        return $amountDiscounted;
    }

    /**
     * @param $discountsToApply
     * @param $initialShippingCosts
     * @return mixed
     */
    public function getShippingCostsDiscounted($discountsToApply, $initialShippingCosts)
    {
        $amountDiscounted = 0;

        if ($discountsToApply) {
            foreach ($discountsToApply as $discount) {
                if ($discount['type'] == self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE) {
                    $amountDiscounted += $discount['amount'];
                } elseif ($discount['type'] == self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE) {
                    $amountDiscounted += $discount['amount'] / 100 * $initialShippingCosts;
                } elseif ($discount['type'] == self::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {
                    return $discount['amount'];
                }
            }
        }

        return max((float)($initialShippingCosts - $amountDiscounted), 0);
    }
}