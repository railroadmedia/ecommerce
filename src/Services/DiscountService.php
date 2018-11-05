<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Cart;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Repositories\DiscountRepository;

class DiscountService
{
    /**
     * @var DiscountRepository
     */
    private $discountRepository;

    /**
     * @var DiscountCriteriaService
     */
    private $discountCriteriaService;

    private $activeDiscountsCache = null;

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
     * DiscountService constructor.
     *
     * @param DiscountRepository $discountRepository
     * @param DiscountCriteriaService $discountCriteriaService
     */
    public function __construct(
        DiscountRepository $discountRepository,
        DiscountCriteriaService $discountCriteriaService
    ) {
        $this->discountRepository = $discountRepository;
        $this->discountCriteriaService = $discountCriteriaService;
    }

    /**
     * @param Cart $cart
     * @return Discount[]
     */
    public function getApplicableDiscounts(Cart $cart)
    {
        $discountsToApply = [];

        $activeDiscounts = $this->activeDiscountsCache
            ??
            $this->discountRepository->query()
                ->where('active', 1)
                ->get();

        $this->activeDiscountsCache = $activeDiscounts;

        foreach ($cart->getItems() as $key => $item) {
            foreach ($activeDiscounts as $activeDiscount) {
                $criteriaMet = false;

                foreach ($activeDiscount->criteria as $discountCriteria) {
                    if ($this->discountCriteriaService->discountCriteriaMetForOrder($discountCriteria, $cart)) {
                        $discountsToApply[] = $activeDiscount;
                    }
                }
            }
        }

        return $discountsToApply;
    }

    /**
     * @param $discountsToApply
     * @param $cartItems
     * @return mixed
     */
    public function applyDiscounts($discountsToApply, $cartItems)
    {
        $subTotal = 0;

        foreach ($this->getItems() as $item) {
            foreach ($discountsToApply as $discount) {
                // save raw in order item discounts
                if ($discount['type'] == self::PRODUCT_AMOUNT_OFF_TYPE ||
                    $discount['type'] == self::PRODUCT_PERCENT_OFF_TYPE ||
                    $discount['type'] == self::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE ||
                    $discount['type'] == self::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                    foreach ($cartItems as $key => $cartItem) {
                        if ($cartItem['options']['product-id'] == $discount['product_id']) {
                            $cartItems[$key]['applyDiscount'][] = $discount;
                        }
                    }
                }

                // Order/shipping total discounts
                if ($discount['type'] == self::ORDER_TOTAL_AMOUNT_OFF_TYPE ||
                    $discount['type'] == self::ORDER_TOTAL_PERCENT_OFF_TYPE ||
                    $discount['type'] == self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE ||
                    $discount['type'] == self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE ||
                    $discount['type'] == self::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {
                    $cartItems['applyDiscount'][] = $discount;
                }
            }

            $subTotal += $item->product['price'];
        }

        return $subTotal;
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
                    if ($cartItem['options']['product-id'] == $discount['product_id']) {
                        //Check product price and discount amount.
                        //IF discount amount it's greater that product price we use product price as discounted amount to avoid negative value
                        $amountDiscounted += ($discount['amount'] > $cartItem['price']) ? $cartItem['price'] :
                            $discount['amount'] * $cartItem['quantity'];
                    }
                } elseif ($discount['type'] == self::PRODUCT_PERCENT_OFF_TYPE) {

                    if ($cartItem['options']['product-id'] == $discount['product_id']) {
                        $amountDiscounted += $discount['amount'] / 100 * $cartItem['price'] * $cartItem['quantity'];
                    }
                }
            }
        }

        return $amountDiscounted;
    }
}