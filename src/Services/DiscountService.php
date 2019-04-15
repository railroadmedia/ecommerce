<?php

namespace Railroad\Ecommerce\Services;

use Doctrine\ORM\ORMException;
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
     * @throws ORMException
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
     * @throws ORMException
     */
    public function getTotalItemDiscounted(Cart $cart, $totalDueInItems, $totalDueInShipping)
    {
        $applicableDiscounts = $this->getNonShippingDiscountsForCart($cart, $totalDueInItems, $totalDueInShipping);

        $totalItemDiscounts = 0;

        foreach ($applicableDiscounts as $applicableDiscount) {
            if ($applicableDiscount->getType() == DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE) {
                $totalItemDiscounts = round($totalItemDiscounts + $applicableDiscount->getAmount(), 2);
            }
            elseif ($applicableDiscount->getType() == DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE) {
                $amountDiscounted = $applicableDiscount->getAmount() / 100 * $totalDueInItems;
                $totalItemDiscounts = round($totalItemDiscounts + $amountDiscounted, 2);
            }
            elseif (
                $applicableDiscount->getType() == DiscountService::PRODUCT_AMOUNT_OFF_TYPE ||
                $applicableDiscount->getType() == DiscountService::PRODUCT_PERCENT_OFF_TYPE
            ) {
                $products = $this->productRepository->bySkus($cart->listSkus());

                foreach ($products as $product) {
                    /** @var Product $product */
                    /** @var CartItem $productCartItem */
                    $productCartItem = $cart->getItemBySku($product->getSku());

                    /** @var Product $discountProduct */
                    $discountProduct = $applicableDiscount->getProduct();

                    if (($discountProduct && $product->getId() == $discountProduct->getId()) ||
                        $product->getCategory() == $applicableDiscount->getProductCategory()) {
                        if ($applicableDiscount->getType() == DiscountService::PRODUCT_AMOUNT_OFF_TYPE) {
                            $discountAmount = $applicableDiscount->getAmount() * $productCartItem->getQuantity();
                            $totalItemDiscounts = round($totalItemDiscounts + $discountAmount, 2);
                        }
                        elseif ($applicableDiscount->getType() == DiscountService::PRODUCT_PERCENT_OFF_TYPE) {
                            $discountAmount =
                                $productCartItem->getQuantity() * $product->getPrice() * $applicableDiscount->getAmount() / 100;
                            $totalItemDiscounts = round($totalItemDiscounts + $discountAmount, 2);
                        }
                    }
                }
            }
        }

        return (float)$totalItemDiscounts;
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
     * @throws ORMException
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
     * @throws ORMException
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
}
