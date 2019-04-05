<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Throwable;

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
     * @var DiscountCriteriaService
     */
    private $discountCriteriaService;

    /**
     * @var DiscountRepository
     */
    private $discountRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * PaymentMethodService constructor.
     *
     * @param DiscountCriteriaService $discountCriteriaService
     * @param DiscountRepository $discountRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        DiscountCriteriaService $discountCriteriaService,
        DiscountRepository $discountRepository,
        ProductRepository $productRepository
    ) {

        $this->discountCriteriaService = $discountCriteriaService;
        $this->discountRepository = $discountRepository;
        $this->productRepository = $productRepository;
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
     * Apply the cart discounts by populating discount amounts properties on cart and cart items
     *
     * @param Cart $cart
     *
     * @return Cart
     *
     * @throws Throwable
     */
    public function applyDiscountsToCart(Cart $cart): Cart
    {
        $orderDiscountAmount = 0;
        $shippingDiscountAmount = 0;
        $shippingOverwrite = false;
        $productsDiscountAmount = []; // keyed by product sku
        $discountNames = [];
        $shippingDiscountNames = []; // if exists and to be applied, ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE discount will truncate the array
        $products = $this->productRepository->findBySkus($cart->listSkus());

        foreach ($this->getDiscountsForCart($cart) as $discount) {

            if ($discount->getType() == self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE && !$shippingOverwrite) {

                $shippingDiscountAmount = round($shippingDiscountAmount + $discount->getAmount(), 2);
                $shippingDiscountNames[] = $discount->getName();

            } elseif ($discount->getType() == self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE && !$shippingOverwrite) {

                $shippingDiscountAmount =
                            round($shippingDiscountAmount + $discount->getAmount() / 100 * $cart->getShippingCost(), 2);
                $shippingDiscountNames[] = $discount->getName();

            } elseif ($discount->getType() == self::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {

                $shippingDiscountAmount = $cart->getShippingCost() - $discount->getAmount();
                $shippingDiscountNames = [$discount->getName()];
                $shippingOverwrite = true;

            } elseif ($discount->getType() == self::ORDER_TOTAL_AMOUNT_OFF_TYPE) {

                $orderDiscountAmount = round($orderDiscountAmount - $discount->getAmount(), 2);
                $discountNames[] = $discount->getName();

            } else if ($discount->getType() == self::ORDER_TOTAL_PERCENT_OFF_TYPE) {

                $amountDiscounted = $discount->getAmount() / 100 * $cart->getItemsCost();
                $orderDiscountAmount = round($orderDiscountAmount - $amountDiscounted, 2);
                $discountNames[] = $discount->getName();

            }

            foreach ($products as $product) {
                /** @var Product $product */

                /** @var CartItem $cartItem */
                $productCartItem = $cart->getItemBySku($product->getSku());

                /** @var Product $discountProduct */
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

                        $productsDiscountAmount[$product->getSku()] =
                            round($productsDiscountAmount[$product->getSku()] + $discountAmount, 2);

                        $discountNames[] = $discount->getName();

                    } else if ($discount->getType() == self::PRODUCT_PERCENT_OFF_TYPE) {

                        $discountAmount = $productCartItem->getQuantity() * $product->getPrice() * $discount->getAmount() / 100;

                        $productsDiscountAmount[$product->getSku()] =
                            round($productsDiscountAmount[$product->getSku()] + $discountAmount, 2);

                        $discountNames[] = $discount->getName();
                    }
                }

                // todo - add subscription type discount handling in order form service / subscription creation
            }
        }

        $cart->setOrderDiscountAmount($orderDiscountAmount);
        $cart->setShippingDiscountAmount($shippingDiscountAmount);
        $cart->setCartDiscountNames(array_merge($discountNames, $shippingDiscountNames));

        foreach ($cart->getItems() as $cartItem) {
            $cartItem->setDiscountAmount($productsDiscountAmount[$cartItem->getSku()] ?? 0);
        }

        return $cart;
    }

    /**
     * Returns the active discounts that meet criteria for $cart
     *
     * @param Cart $cart
     *
     * @return array
     *
     * @throws Throwable
     */
    public function getDiscountsForCart(Cart $cart)
    {
        $discountsToApply = [];

        $activeDiscounts = $this->discountRepository->getActiveDiscountsWithCriteria();

        foreach ($activeDiscounts as $activeDiscount) {
            /** @var $activeDiscount Discount */
            $criteriaMet = false;

            foreach ($activeDiscount->getDiscountCriterias() as $discountCriteria) {
                /** @var $discountCriteria DiscountCriteria */
                $discountCriteriaMet =
                    $this->discountCriteriaService->discountCriteriaMetForOrder($discountCriteria,
                        $cart);

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
