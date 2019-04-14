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
    )
    {
        $this->discountCriteriaService = $discountCriteriaService;
        $this->discountRepository = $discountRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @param Cart $cart
     * @param $totalDueInItems
     * @param $totalDueInShipping
     * @return int
     * @throws Throwable
     * @throws \Doctrine\ORM\ORMException
     */
    public function getTotalShippingDiscounted(Cart $cart, $totalDueInItems, $totalDueInShipping)
    {
        $applicableShippingDiscounts = $this->getShippingDiscountsForCart($cart, $totalDueInItems, $totalDueInShipping);

        $totalShippingDiscount = 0;

        foreach ($applicableShippingDiscounts as $applicableShippingDiscount) {
            $totalShippingDiscount += $applicableShippingDiscount->getAmount();
        }

        return (float)$totalShippingDiscount;
    }

    /**
     * @param Cart $cart
     * @param $totalDueInItems
     * @param $totalDueInShipping
     * @return float
     * @throws Throwable
     * @throws \Doctrine\ORM\ORMException
     */
    public function getTotalItemDiscounted(Cart $cart, $totalDueInItems, $totalDueInShipping)
    {
        $applicableDiscounts = $this->getNonShippingDiscountsForCart($cart, $totalDueInItems, $totalDueInShipping);

        $totalItemDiscounts = 0;

        foreach ($applicableDiscounts as $applicableDiscount) {
            $totalItemDiscounts += $applicableDiscount->getAmount();
        }

        return (float)$totalItemDiscounts;
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
        $shippingDiscountNames =
            []; // if exists and to be applied, ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE discount will truncate the array
        $products = $this->productRepository->bySkus($cart->listSkus());

        foreach ($this->getNonShippingDiscountsForCart($cart) as $discount) {

            if ($discount->getType() == self::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE && !$shippingOverwrite) {

                $shippingDiscountAmount = round($shippingDiscountAmount + $discount->getAmount(), 2);

                if ($discount->getVisible()) {
                    $shippingDiscountNames[] = $discount->getName();
                }

            }
            elseif ($discount->getType() == self::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE && !$shippingOverwrite) {

                $shippingDiscountAmount =
                    round($shippingDiscountAmount + $discount->getAmount() / 100 * $cart->getShippingCost(), 2);

                if ($discount->getVisible()) {
                    $shippingDiscountNames[] = $discount->getName();
                }

            }
            elseif ($discount->getType() == self::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {

                $shippingDiscountAmount = $cart->getShippingCost() - $discount->getAmount();

                if ($discount->getVisible()) {
                    $shippingDiscountNames = [$discount->getName()];
                }

                $shippingOverwrite = true;

            }
            elseif ($discount->getType() == self::ORDER_TOTAL_AMOUNT_OFF_TYPE) {

                $orderDiscountAmount = round($orderDiscountAmount + $discount->getAmount(), 2);

                if ($discount->getVisible()) {
                    $discountNames[] = $discount->getName();
                }

            }
            else {
                if ($discount->getType() == self::ORDER_TOTAL_PERCENT_OFF_TYPE) {

                    $amountDiscounted = $discount->getAmount() / 100 * $cart->getItemsCost();
                    $orderDiscountAmount = round($orderDiscountAmount + $amountDiscounted, 2);

                    if ($discount->getVisible()) {
                        $discountNames[] = $discount->getName();
                    }
                }
            }

            foreach ($products as $product) {
                /** @var Product $product */

                /** @var CartItem $productCartItem */
                $productCartItem = $cart->getItemBySku($product->getSku());

                /** @var Product $discountProduct */
                $discountProduct = $discount->getProduct();

                if (($discountProduct && $product->getId() == $discountProduct->getId()) ||
                    $product->getCategory() == $discount->getProductCategory()) {

                    if ($discount->getType() == self::PRODUCT_AMOUNT_OFF_TYPE) {

                        $discountAmount = $discount->getAmount() * $productCartItem->getQuantity();

                        $productsDiscountAmount[$product->getSku()] = round(
                            ($productsDiscountAmount[$product->getSku()] ?? 0) + $discountAmount,
                            2
                        );

                        if ($discount->getVisible()) {
                            $discountNames[] = $discount->getName();
                        }

                    }
                    else {
                        if ($discount->getType() == self::PRODUCT_PERCENT_OFF_TYPE) {

                            $discountAmount =
                                $productCartItem->getQuantity() * $product->getPrice() * $discount->getAmount() / 100;

                            $productsDiscountAmount[$product->getSku()] = round(
                                ($productsDiscountAmount[$product->getSku()] ?? 0) + $discountAmount,
                                2
                            );

                            if ($discount->getVisible()) {
                                $discountNames[] = $discount->getName();
                            }
                        }
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
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     * @return array
     *
     * @throws Throwable
     * @throws \Doctrine\ORM\ORMException
     */
    public function getNonShippingDiscountsForCart(Cart $cart, float $totalDueInItems, float $totalDueInShipping)
    {
        $discountsToApply = [];

        $activeDiscounts = $this->discountRepository->getActiveDiscounts();

        foreach ($activeDiscounts as $activeDiscount) {
            /** @var $activeDiscount Discount */
            $criteriaMet = false;

            foreach ($activeDiscount->getDiscountCriterias() as $discountCriteria) {
                /** @var $discountCriteria DiscountCriteria */
                $discountCriteriaMet = $this->discountCriteriaService->discountCriteriaMetForOrder(
                    $discountCriteria,
                    $cart,
                    $totalDueInItems,
                    $totalDueInShipping
                );

                if ($discountCriteriaMet) {
                    $criteriaMet = true;
                    break;
                }
            }

            if ($criteriaMet) {
                $discountsToApply[$activeDiscount->getId()] = $activeDiscount;
            }
        }

        // we don't want the shipping discount
        foreach ($discountsToApply as $discountToApplyIndex => $discountToApply) {
            if (in_array(
                $discountToApply->getType(),
                [
                    DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE
                ]
            )) {
                unset($discountsToApply[$discountToApplyIndex]);
            }
        }

        return $discountsToApply;
    }

    /**
     * @param Cart $cart
     *
     * @param $totalDueInItems
     * @param $totalDueInShipping
     * @return Discount[]
     * @throws Throwable
     * @throws \Doctrine\ORM\ORMException
     */
    public function getShippingDiscountsForCart(Cart $cart, $totalDueInItems, $totalDueInShipping)
    {
        $discountsToApply = $this->getNonShippingDiscountsForCart($cart, $totalDueInItems, $totalDueInShipping);

        foreach ($discountsToApply as $discountToApplyIndex => $discountToApply) {
            if (!in_array(
                $discountToApply->getType(),
                [
                    DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE
                ]
            )) {
                unset($discountsToApply[$discountToApplyIndex]);
            }
        }

        return $discountsToApply;
    }

    /**
     * @return Discount[]
     */
    public function getAllActiveDiscounts()
    {
        return $this->discountRepository->getActiveDiscounts();
    }
}
