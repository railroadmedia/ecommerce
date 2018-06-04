<?php

namespace Railroad\Ecommerce\Services;

class DiscountService
{

    const PRODUCT_AMOUNT_OFF_TYPE                      = 'product amount off';
    const PRODUCT_PERCENT_OFF_TYPE                     = 'product percent off';
    const SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE            = 'subscription free trial days';
    const SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE = 'subscription recurring price amount off';
    const ORDER_TOTAL_AMOUNT_OFF_TYPE                  = 'order total amount off';
    const ORDER_TOTAL_PERCENT_OFF_TYPE                 = 'order total percent off';
    const ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE         = 'order total shipping amount off';
    const ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE        = 'order total shipping percent off';
    const ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE          = 'order total shipping overwrite';

    /**
     * @param $discountsToApply
     * @param $cartItems
     * @return mixed
     */
    public function applyDiscounts($discountsToApply, $cartItems)
    {
        foreach($discountsToApply as $discount)
        {
            // save raw in order item discounts
            if($discount['discount_type'] == self::PRODUCT_AMOUNT_OFF_TYPE ||
                $discount['discount_type'] == self::PRODUCT_PERCENT_OFF_TYPE ||
                $discount['discount_type'] == self::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE ||
                $discount['discount_type'] == self::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE
            )
            {
                foreach($cartItems as $key => $cartItem)
                {

                    if($cartItem['options']['product-id'] == $discount['product_id'])
                    {
                        $cartItems[$key]['applyDiscount'] = $discount;
                    }
                }
            }

            // Order/shipping total discounts
            if($discount['discount_type'] == self::ORDER_TOTAL_AMOUNT_OFF_TYPE ||
                $discount['discount_type'] == self::ORDER_TOTAL_PERCENT_OFF_TYPE ||
                $discount['discount_type'] == self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE ||
                $discount['discount_type'] == self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE ||
                $discount['discount_type'] == self::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE
            )
            {
                $cartItems['applyDiscount'] = $discount;
            }
        }

        return $cartItems;
    }

    /**
     * @param $discountsToApply
     * @param $cartItemsTotalDue
     * @return float|int
     */
    public function getAmountDiscounted($discountsToApply, $cartItemsTotalDue, $cartItems)
    {

        $amountDiscounted = 0;

        foreach($discountsToApply as $discount)
        {
            foreach($cartItems as $cartItem)
            {
                if($discount['discount_type'] == self::ORDER_TOTAL_AMOUNT_OFF_TYPE)
                {
                    $amountDiscounted += $discount['amount'];
                }
                elseif($discount['discount_type'] == self::ORDER_TOTAL_PERCENT_OFF_TYPE)
                {
                    $amountDiscounted += $discount['amount'] / 100 * $cartItemsTotalDue;
                }
                elseif($discount['discount_type'] == self::PRODUCT_AMOUNT_OFF_TYPE)
                {

                    if($cartItem['options']['product-id'] == $discount['product_id'])
                    {
                        $amountDiscounted += $discount['amount'] * $cartItem['quantity'];
                    }
                }
                elseif($discount['discount_type'] == self::PRODUCT_PERCENT_OFF_TYPE)
                {

                    if($cartItem['options']['product-id'] == $discount['product_id'])
                    {
                        $amountDiscounted += $discount['amount'] / 100 * $cartItem['price'];
                    }
                }
                else if($discount['discount_type'] == self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE)
                {
                    $amountDiscounted += $discount['amount'];
                }
                elseif($discount['discount_type'] == self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE)
                {
                    $amountDiscounted += $discount['amount'] / 100 * $cartItems['shippingCosts'];
                }
                elseif($discount['discount_type'] == self::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE)
                {
                    $amountDiscounted += $discount['amount'];
                }
                elseif($discount['discount_type'] == self::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE)
                {

                }
                elseif($discount['discount_type'] == self::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE)
                {

                }
            }
        }

        return $amountDiscounted;
    }

}