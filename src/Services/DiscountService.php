<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;

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
     * @param array $discountsToApply - array of \Railroad\Ecommerce\Entities\Discount
     * @param Cart $cartItems
     *
     * @return mixed
     */
    public function applyDiscounts(array $discountsToApply, Cart $cart)
    {
        $cartDiscounts = [];

        foreach ($discountsToApply as $discount) {
            /**
             * @var $discount \Railroad\Ecommerce\Entities\Discount
             */
            // save raw in order item discounts
            if (
                $discount->getType() == self::PRODUCT_AMOUNT_OFF_TYPE ||
                $discount->getType() == self::PRODUCT_PERCENT_OFF_TYPE ||
                $discount->getType() == self::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE ||
                $discount->getType() == self::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE
            ) {

                foreach ($cart->getItems() as $key => $cartItem) {
                    /**
                     * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
                     */

                    /**
                     * @var $cartProduct \Railroad\Ecommerce\Entities\Product
                     */
                    $cartProduct = $cartItem->getProduct();

                    /**
                     * @var $discountProduct \Railroad\Ecommerce\Entities\Product
                     */
                    $discountProduct = $discount->getProduct();

                    if (
                        ($cartProduct->getId() == $discountProduct->getId()) ||
                        (
                            $cartProduct->getCategory() ==
                            $discount->getProductCategory()
                        )
                    ) {
                        $cartItem->addAppliedDiscount($discount);
                    }
                }
            }

            // Order/shipping total discounts
            if (
                $discount->getType() == self::ORDER_TOTAL_AMOUNT_OFF_TYPE ||
                $discount->getType() == self::ORDER_TOTAL_PERCENT_OFF_TYPE ||
                $discount->getType() == self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE ||
                $discount->getType() == self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE ||
                $discount->getType() == self::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE
            ) {

                $cartDiscounts[] = $discount;
            }
        }

        $cart->setAppliedDiscounts($cartDiscounts);

        return $cart->getItems();
    }

    /**
     * @param array $discountsToApply - array of \Railroad\Ecommerce\Entities\Discount
     * @param float $cartItemsTotalDue
     *
     * @return float
     */
    public function getAmountDiscounted(
        array $discountsToApply,
        float $cartItemsTotalDue
    ) {
        $amountDiscounted = 0;

        foreach ($discountsToApply as $discount) {
            /**
             * @var $discount \Railroad\Ecommerce\Entities\Discount
             */

            if (
                $discount->getType() == self::ORDER_TOTAL_AMOUNT_OFF_TYPE
            ) {
                $amountDiscounted += $discount->getAmount();
            } elseif (
                $discount->getType() == self::ORDER_TOTAL_PERCENT_OFF_TYPE
            ) {
                $amountDiscounted += $discount->getAmount() / 100 * $cartItemsTotalDue;
            }
        }

        return $amountDiscounted;
    }
}
