<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Repositories\DiscountRepository;

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
     * @var DiscountRepository
     */
    private $discountRepository;

    /**
     * PaymentMethodService constructor.
     *
     * @param DiscountRepository $discountRepository
     */
    public function __construct(DiscountRepository $discountRepository)
    {

        $this->discountRepository = $discountRepository;
    }

    /**
     * @param array $discountsToApply - array of \Railroad\Ecommerce\Entities\Discount
     * @param Cart $cart
     *
     * @return mixed
     */
    public function applyDiscounts(array $discountsToApply, Cart $cart)
    {
        // todo - check usage and remove if not used
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
                        $cartProduct &&
                        (
                            (
                                $discountProduct &&
                                $cartProduct->getId() == $discountProduct->getId()
                            )
                            || $cartProduct->getCategory() == $discount->getProductCategory()
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
        // todo - check usage and remove if not used
        $amountDiscounted = 0;

        foreach ($discountsToApply as $discount) {
            /**
             * @var $discount \Railroad\Ecommerce\Entities\Discount
             */

            if (
                $discount->getType() == self::ORDER_TOTAL_AMOUNT_OFF_TYPE
            ) {
                $amountDiscounted = round($amountDiscounted + $discount->getAmount(), 2);
            } elseif (
                $discount->getType() == self::ORDER_TOTAL_PERCENT_OFF_TYPE
            ) {
                $amountDiscounted = round($amountDiscounted + $discount->getAmount() / 100 * $cartItemsTotalDue, 2);
            }
        }

        return $amountDiscounted;
    }

    /**
     * Applys the cart discounts by populating discount amounts properties on cart and cart items
     */
    public function applyDiscountForCart()
    {
        // wip
        $orderDiscountAmount = 0;
        $shippingDiscountAmount = 0;
        $shippingOverwrite = false;
        $productsDiscountAmount = []; // keyd by product sku
        $discountNames = [];
        $shippingDiscountNames = []; // ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE will truncate the array, if discount exists

        $cart = Cart::fromSession();

        foreach ($this->getDiscountsForCart() as $discount) {

            switch ($discount->getType()) {
                case self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE:
                    if (!$shippingOverwrite) {
                        $shippingDiscountAmount = round($shippingDiscountAmount + $discount->getAmount(), 2);
                    }
                    break;

                case self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE:
                    if (!$shippingOverwrite) {
                    }
                    break;

                default:
                    # code...
                    break;
            }
        }

        $cart->setOrderDiscountAmount($orderDiscountAmount);
        $cart->setShippingDiscountAmount($shippingDiscountAmount);
        $cart->setCartDiscountNames(array_merge($discountNames, $shippingDiscountNames));

        $cart->toSession();
    }

    /**
     * @return float
     */
    public function getShippingDiscountAmount($initialShippingDue)
    {
        // todo - remove after applyDiscountForCart is complete

        $shippingDiscountAmount = 0;

        foreach ($this->getDiscountsForCart() as $discount) {
            /**
             * @var $discount \Railroad\Ecommerce\Entities\Discount
             */
            if ($discount->getType() == self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE) {
                $shippingDiscountAmount = round($shippingDiscountAmount + $discount->getAmount(), 2);
            } elseif ($discount->getType() == self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE) {
                $shippingDiscountAmount =
                    round($shippingDiscountAmount + $discount->getAmount() / 100 * $initialShippingCosts, 2);
            } elseif ($discount->getType() == self::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {
                // this makes the final shipping cost:
                // $initialShippingDue - ($initialShippingDue - $discount->getAmount()) =  $discount->getAmount()
                $shippingDiscountAmount = $initialShippingDue - $discount->getAmount();
                break;
            }
        }

        return $shippingDiscountAmount;
    }

    /**
     * @return float
     */
    public function getDiscountedItemsCostDue($initialProductsDue)
    {
        // todo - remove after applyDiscountForCart is complete

        $discountedProductsCosts = $initialProductsDue;

        $this->cartService->refreshCart();

        $cart = Cart::fromSession();

        $products = $this->productRepository->findBySkus(['sku' => $cart->listSkus()]);

        // todo - review
        foreach ($this->getDiscountsForCart() as $discount) {
            /** @var \Railroad\Ecommerce\Entities\Discount $discount */

            foreach ($products as $product) {
                /** @var \Railroad\Ecommerce\Entities\Product $product */

                /** @var \Railroad\Ecommerce\Entities\Structures\CartItem $cartItem */
                $productCartItem = $cart->getItemBySku($product->getSku());

                /** @var \Railroad\Ecommerce\Entities\Product $discountProduct */
                $discountProduct = $discount->getProduct();

                if (
                    (
                        $discountProduct &&
                        $product->getId() == $discountProduct->getId()
                    )
                    || $product->getCategory() == $discount->getProductCategory()
                ) {

                    if ($discount->getType() == self::PRODUCT_AMOUNT_OFF_TYPE) {
                        $discountAmount = $discount->getAmount() * $productCartItem->getQuantity();

                        $discountedProductsCosts = round($discountedProductsCosts - $discountAmount, 2);
                    } else if ($discount->getType() == self::PRODUCT_PERCENT_OFF_TYPE) {

                        $discountAmount = $discount->getAmount() / 100 * ($productCartItem->getQuantity() * $products->getPrice());

                        $discountedProductsCosts = round($discountedProductsCosts - $discountAmount, 2);
                    }
                }

                // todo - add subscription type discount handling in order form service / subscription creation
            }

            if ($discount->getType() == self::ORDER_TOTAL_AMOUNT_OFF_TYPE) {
                $discountedProductsCosts = round($discountedProductsCosts - $discount->getAmount(), 2);
            } else if ($discount->getType() == self::ORDER_TOTAL_PERCENT_OFF_TYPE) {
                $amountDiscounted = $discount->getAmount() / 100 * $initialProductsDue;
                $discountedProductsCosts = round($discountedProductsCosts - $discountAmount, 2);
            }
        }

        return $discountedProductsCosts;
    }

    /**
     * @return array
     */
    public function getDiscountsForCart()
    {
        $discountsToApply = [];

        $activeDiscounts = $this->discountRepository->getActiveDiscountsWithCriteria();

        foreach ($activeDiscounts as $activeDiscount) {
            /**
             * @var $activeDiscount \Railroad\Ecommerce\Entities\Discount
             */
            $criteriaMet = false;

            foreach ($activeDiscount->getDiscountCriterias() as $discountCriteria) {
                /**
                 * @var $discountCriteria \Railroad\Ecommerce\Entities\DiscountCriteria
                 */
                $discountCriteriaMet =
                    $this->discountCriteriaService->discountCriteriaMetForOrder($discountCriteria,
                        $this->getPromoCode());

                if ($discountCriteriaMet) {
                    $criteriaMet = true;
                    break;
                }
            }

            if ($criteriaMet) {
                $discountsToApply[$activeDiscount->getId()] = $activeDiscount;
            }
        }

        return $discountsToApply;
    }
}
