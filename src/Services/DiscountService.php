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
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     *
     * @return float
     *
     * @throws Throwable
     * @throws ORMException
     */
    public function getTotalShippingDiscounted(Cart $cart, float $totalDueInItems, float $totalDueInShipping): float
    {
        $applicableShippingDiscounts = $this->getShippingDiscountsForCart($cart, $totalDueInItems, $totalDueInShipping);

        $totalShippingDiscount = 0;

        foreach ($applicableShippingDiscounts as $applicableShippingDiscount) {
            if ($applicableShippingDiscount->getType() == DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE) {
                $totalShippingDiscount = round($totalShippingDiscount + $applicableShippingDiscount->getAmount(), 2);
            }
            elseif ($applicableShippingDiscount->getType() == DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE) {
                $amountDiscounted = $applicableShippingDiscount->getAmount() * $totalDueInShipping / 100;
                $totalShippingDiscount = round($totalShippingDiscount + $amountDiscounted, 2);
            }
            elseif ($applicableShippingDiscount->getType() == DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {
                $totalShippingDiscount = round($totalDueInShipping - $applicableShippingDiscount->getAmount(), 2);
                break;
            }
        }

        return (float)$totalShippingDiscount;
    }

    /**
     * @param Cart $cart
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     *
     * @return float
     *
     * @throws Throwable
     * @throws ORMException
     */
    public function getTotalItemDiscounted(Cart $cart, float $totalDueInItems, float $totalDueInShipping): float
    {
        $applicableDiscounts = $this->getNonShippingDiscountsForCart($cart, $totalDueInItems, $totalDueInShipping);

        $totalItemDiscounts = 0;

        $cartItemsDiscounts = [];
        $orderDiscounts = 0;

        foreach ($applicableDiscounts as $applicableDiscount) {
            if ($applicableDiscount->getType() == DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE) {
                $orderDiscounts += $applicableDiscount->getAmount();
            }
            elseif ($applicableDiscount->getType() == DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE) {
                $amountDiscounted = $applicableDiscount->getAmount() / 100 * $totalDueInItems;
                $orderDiscounts += $amountDiscounted;
            }
            elseif ($applicableDiscount->getType() == DiscountService::PRODUCT_AMOUNT_OFF_TYPE
                || $applicableDiscount->getType() == DiscountService::PRODUCT_PERCENT_OFF_TYPE
                || $applicableDiscount->getType() == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {
                $products = $this->productRepository->bySkus($cart->listSkus());

                foreach ($products as $product) {
                    /** @var Product $product */
                    /** @var CartItem $productCartItem */
                    $productCartItem = $cart->getItemBySku($product->getSku());

                    if (!is_null($productCartItem->getDueOverride())) {
                        continue;
                    }

                    /** @var Product $discountProduct */
                    $discountProduct = $applicableDiscount->getProduct();

                    if (
                        ($discountProduct && $product->getId() == $discountProduct->getId()) ||
                        (
                            $applicableDiscount->getProductCategory() &&
                            $product->getCategory() == $applicableDiscount->getProductCategory()
                        )
                    ) {
                        $discountAmount = 0;
                        if ($applicableDiscount->getType() == DiscountService::PRODUCT_AMOUNT_OFF_TYPE) {
                            $discountAmount = $applicableDiscount->getAmount() * $productCartItem->getQuantity();
                        }
                        elseif ($applicableDiscount->getType() == DiscountService::PRODUCT_PERCENT_OFF_TYPE) {
                            $discountAmount =
                                $productCartItem->getQuantity() *
                                $product->getPrice() *
                                $applicableDiscount->getAmount() /
                                100;
                        } elseif ($applicableDiscount->getType() == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {
                            // subscription discount SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE starts charging the customer after the trial days, so subscription price is subtracted from order total
                            $discountAmount = $product->getPrice();
                        }

                        $sku  = $product->getSku();

                        if (!isset($cartItemsDiscounts[$sku])) {
                            $cartItemsDiscounts[$sku] = 0;
                        }

                        $cartItemsDiscounts[$sku] += $discountAmount;

                        if ($cartItemsDiscounts[$sku] > $productCartItem->getQuantity() * $product->getPrice()) {
                            // avoid negative cart item price
                            $cartItemsDiscounts[$sku] = $productCartItem->getQuantity() * $product->getPrice();
                        }
                    }
                }
            }
        }

        $totalItemDiscounts = array_sum($cartItemsDiscounts) + $orderDiscounts;

        if ($totalItemDiscounts > $totalDueInItems) {
            // avoid negative order price
            $totalItemDiscounts = $totalDueInItems;
        }

        return round((float) $totalItemDiscounts, 2);
    }

    /**
     * @param Cart $cart
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     *
     * @return array
     *
     * @throws Throwable
     * @throws ORMException
     */
    public function getApplicableDiscountsNames(Cart $cart, float $totalDueInItems, float $totalDueInShipping): array
    {
        $applicableDiscounts = $this->getApplicableDiscounts(
            $this->discountRepository->getActiveDiscounts(),
            $cart,
            $totalDueInItems,
            $totalDueInShipping
        );

        $discountNames = [];
        $shippingDiscountNames = [];
        $shippingOverwrite = false;

        $cartProductIdsMap = [];
        $cartProductCategoryMap = [];

        $products = $this->productRepository->bySkus($cart->listSkus());

        foreach ($products as $product) {
            $cartProductIdsMap[$product->getId()] = true;

            if (!empty($product->getCategory())) {
                $cartProductCategoryMap[$product->getCategory()] = true;
            }
        }

        foreach ($applicableDiscounts as $discount) {

            if (!$discount->getVisible()) {
                if ($discount->getType() == DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {
                    $shippingDiscountNames = [];
                    $shippingOverwrite = true;
                }

                continue;
            }

            if ($discount->getType() == DiscountService::PRODUCT_AMOUNT_OFF_TYPE ||
                $discount->getType() == DiscountService::PRODUCT_PERCENT_OFF_TYPE) {

                if (!empty($discount->getProductCategory()) &&
                    !isset($cartProductCategoryMap[$discount->getProductCategory()])) {
                    // if discount has set a category but no product in cart has it
                    continue;
                }

                if (!empty($discount->getProduct()) && !isset(
                        $cartProductIdsMap[$discount->getProduct()
                            ->getId()]
                    )) {
                    // if discount has set a product, but product not in cart
                    continue;
                }
            }

            if (in_array(
                $discount->getType(),
                [
                    DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE
                ]
            )) {
                if ($this->doesCartHaveAnyPhysicalItems($cart)) {
                    if ($discount->getType() == DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE) {
                        $shippingDiscountNames = [
                            [
                                'id' => $discount->getId(),
                                'name' => $discount->getName()
                            ]
                        ];
                        $shippingOverwrite = true;
                    }
                    elseif (!$shippingOverwrite) {
                        $shippingDiscountNames[] = [
                            'id' => $discount->getId(),
                            'name' => $discount->getName()
                        ];
                    }
                }
            }
            else {
                $discountNames[] = [
                    'id' => $discount->getId(),
                    'name' => $discount->getName()
                ];
            }
        }

        return array_merge($discountNames, $shippingDiscountNames);
    }

    /**
     * @param Cart $cart
     *
     * @return bool
     *
     * @throws ORMException
     */
    public function doesCartHaveAnyPhysicalItems(Cart $cart): bool
    {
        $products = $this->productRepository->byCart($cart);

        foreach ($products as $product) {
            if ($product->getIsPhysical()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the active discounts that meet criteria for $cart
     *
     * @param Cart $cart
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     *
     * @return array
     *
     * @throws Throwable
     * @throws ORMException
     */
    public function getNonShippingDiscountsForCart(Cart $cart, float $totalDueInItems, float $totalDueInShipping): array
    {
        $discountsToApply = $this->getApplicableDiscounts(
            $this->discountRepository->getActiveDiscounts(),
            $cart,
            $totalDueInItems,
            $totalDueInShipping
        );

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

        return array_values($discountsToApply);
    }

    /**
     * @param Cart $cart
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     *
     * @return Discount[]
     *
     * @throws Throwable
     * @throws ORMException
     */
    public function getShippingDiscountsForCart(Cart $cart, float $totalDueInItems, float $totalDueInShipping): array
    {
        return $this->getApplicableDiscounts(
            $this->discountRepository->getActiveShippingDiscounts(),
            $cart,
            $totalDueInItems,
            $totalDueInShipping
        );
    }

    /**
     * @param Cart $cart
     * @param string $itemSku
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     *
     * @return float
     *
     * @throws Throwable
     * @throws ORMException
     */
    public function getItemDiscountedAmount(
        Cart $cart,
        string $itemSku,
        float $totalDueInItems,
        float $totalDueInShipping
    ): float
    {
        $activeDiscounts = $this->getApplicableDiscounts(
            $this->discountRepository->getActiveCartItemDiscounts(),
            $cart,
            $totalDueInItems,
            $totalDueInShipping
        );

        $product = $this->productRepository->bySku($itemSku);

        /** @var CartItem $productCartItem */
        $productCartItem = $cart->getItemBySku($product->getSku());

        $discountedAmount = 0;

        if (!empty($product) && $product->getActive()) {

            foreach ($activeDiscounts as $discount) {

                /** @var Product $discountProduct */
                $discountProduct = $discount->getProduct();

                if (($discountProduct && $product->getId() == $discountProduct->getId()) ||
                    ($discount->getProductCategory() && $product->getCategory() == $discount->getProductCategory())) {
                    if ($discount->getType() == DiscountService::PRODUCT_AMOUNT_OFF_TYPE) {
                        $discountAmount = $discount->getAmount() * $productCartItem->getQuantity();
                        $discountedAmount = round($discountedAmount + $discountAmount, 2);

                    }
                    elseif ($discount->getType() == DiscountService::PRODUCT_PERCENT_OFF_TYPE) {
                        $discountAmount =
                            $productCartItem->getQuantity() * $product->getPrice() * $discount->getAmount() / 100;
                        $discountedAmount = round($discountedAmount + $discountAmount, 2);
                    }
                }
            }
        }

        return $discountedAmount;
    }

    /**
     * @param Cart $cart
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     *
     * @return Discount[]
     *
     * @throws Throwable
     * @throws ORMException
     */
    public function getOrderDiscounts(
        Cart $cart,
        float $totalDueInItems,
        float $totalDueInShipping
    ): array
    {
        $applicableDiscounts = $this->getApplicableDiscounts(
            $this->discountRepository->getActiveDiscounts(),
            $cart,
            $totalDueInItems,
            $totalDueInShipping
        );

        $orderDiscounts = [];

        foreach ($applicableDiscounts as $applicableDiscount) {
            if (in_array(
                $applicableDiscount->getType(),
                [
                    DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE,
                    DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE,
                    DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE,
                ]
            )) {
                $orderDiscounts[] = $applicableDiscount;
            }
        }

        return $orderDiscounts;
    }

    /**
     * @param Cart $cart
     * @param string $itemSku
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     *
     * @return Discount[]
     *
     * @throws ORMException
     * @throws Throwable
     */
    public function getItemDiscounts(
        Cart $cart,
        string $itemSku,
        float $totalDueInItems,
        float $totalDueInShipping
    ): array
    {
        $activeDiscounts = $this->getApplicableDiscounts(
            $this->discountRepository->getActiveCartItemDiscounts(),
            $cart,
            $totalDueInItems,
            $totalDueInShipping
        );

        $product = $this->productRepository->bySku($itemSku);

        $itemDiscounts = [];

        if (!empty($product) && $product->getActive()) {

            foreach ($activeDiscounts as $discount) {

                $discountProduct = $discount->getProduct();

                if (($discountProduct && $product->getId() == $discountProduct->getId()) ||
                    $product->getCategory() == $discount->getProductCategory()) {

                    if ($discount->getType() == DiscountService::PRODUCT_AMOUNT_OFF_TYPE ||
                        $discount->getType() == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE ||
                        $discount->getType() == DiscountService::PRODUCT_PERCENT_OFF_TYPE ||
                        $discount->getType() == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {

                        $itemDiscounts[] = $discount;
                    }
                }
            }
        }

        return $itemDiscounts;
    }

    /**
     * Filters an active discounts array using discount criteria service
     *
     * @param Discount[] $activeDiscounts
     * @param Cart $cart
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     *
     * @return Discount[]
     *
     * @throws Throwable
     */
    public function getApplicableDiscounts(
        array $activeDiscounts,
        Cart $cart,
        float $totalDueInItems,
        float $totalDueInShipping
    ): array
    {
        $discountsToApply = [];
        $productsBySku = [];
        $products = $this->productRepository->bySkus($cart->listSkus());

        foreach ($products as $product) {
            $productsBySku[$product->getSku()] = $product;
        }

        foreach ($activeDiscounts as $activeDiscount) {
            /** @var $activeDiscount Discount */
            $criteriaMet = true;

            foreach ($activeDiscount->getDiscountCriterias() as $discountCriteria) {
                /** @var $discountCriteria DiscountCriteria */
                $discountCriteriaMet = $this->discountCriteriaService->discountCriteriaMetForOrder(
                    $discountCriteria,
                    $cart,
                    $totalDueInItems,
                    $totalDueInShipping
                );

                if (!$discountCriteriaMet) {
                    $criteriaMet = false;
                    break;
                }
            }

            // if the discount has a product id or category set, that product or a product with that
            // category must be in the cart for it to apply
            $hasDiscountProductInCart = false;

            if (!empty($activeDiscount->getProduct()) || !empty($activeDiscount->getProductCategory())) {

                foreach ($cart->getItems() as $cartItem) {

                    if (!empty($activeDiscount->getProduct()) &&
                        $activeDiscount->getProduct()
                            ->getSku() == $cartItem->getSku()) {

                        $hasDiscountProductInCart = true;
                    }

                    if (!empty($activeDiscount->getProductCategory()) &&
                        $activeDiscount->getProductCategory() == $productsBySku[$cartItem->getSku()]->getCategory()) {

                        $hasDiscountProductInCart = true;
                    }
                }
            }
            else {
                $hasDiscountProductInCart = true;
            }

            if (!$hasDiscountProductInCart) {
                $criteriaMet = false;
                continue;
            }

            if ($criteriaMet || empty(
                $activeDiscount->getDiscountCriterias()
                    ->count()
                )) {
                $discountsToApply[$activeDiscount->getId()] = $activeDiscount;
            }
        }

        return $discountsToApply;
    }
}
