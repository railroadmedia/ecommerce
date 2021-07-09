<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\ORMException;
use Exception;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Throwable;

class DiscountCriteriaService
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var User
     */
    private static $purchaser;

    /**
     * @var UserProductRepository
     */
    private $userProductRepository;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    const PRODUCT_QUANTITY_REQUIREMENT_TYPE = 'product quantity requirement';
    const DATE_REQUIREMENT_TYPE = 'date requirement';
    const ORDER_TOTAL_REQUIREMENT_TYPE = 'order total requirement';
    const SHIPPING_TOTAL_REQUIREMENT_TYPE = 'shipping total requirement';
    const SHIPPING_COUNTRY_REQUIREMENT_TYPE = 'shipping country requirement';
    const PROMO_CODE_REQUIREMENT_TYPE = 'promo code requirement';
    const PRODUCT_OWN_TYPE = 'product own requirement';
    const CART_ITEMS_TOTAL_REQUIREMENT_TYPE = 'total cart items requirement';
    const IS_MEMBER_OF_BRAND_REQUIREMENT_TYPE = 'is member of brand requirement';

    /**
     * DiscountCriteriaService constructor.
     *
     * @param ProductRepository $productRepository
     * @param UserProductRepository $userProductRepository
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        ProductRepository $productRepository,
        UserProductRepository $userProductRepository,
        UserProviderInterface $userProvider
    )
    {
        $this->productRepository = $productRepository;
        $this->userProductRepository = $userProductRepository;
        $this->userProvider = $userProvider;
    }

    /**
     * @param User $purchaser
     */
    public static function setPurchaser(User $purchaser)
    {
        self::$purchaser = $purchaser;
    }

    /**
     * Check whether the discount criteria are met.
     *
     * @param DiscountCriteria $discountCriteria
     * @param Cart $cart
     *
     * @param float $totalDueInItems
     * @param float $totalDueInShipping
     * @return bool
     *
     * @throws Throwable
     */
    public function discountCriteriaMetForOrder(
        DiscountCriteria $discountCriteria,
        Cart $cart,
        float $totalDueInItems,
        float $totalDueInShipping
    ): bool
    {
        switch ($discountCriteria->getType()) {
            case self::PRODUCT_QUANTITY_REQUIREMENT_TYPE:
                return $this->productQuantityRequirementMet($discountCriteria, $cart);
            case self::DATE_REQUIREMENT_TYPE:
                return $this->orderDateRequirement($discountCriteria);
            case self::ORDER_TOTAL_REQUIREMENT_TYPE:
                return $this->orderTotalRequirement($discountCriteria, $totalDueInItems);
            case self::SHIPPING_TOTAL_REQUIREMENT_TYPE:
                return $this->orderShippingTotalRequirement($discountCriteria, $totalDueInShipping);
            case self::SHIPPING_COUNTRY_REQUIREMENT_TYPE:
                return $this->orderShippingCountryRequirement($discountCriteria, $cart);
            case self::PROMO_CODE_REQUIREMENT_TYPE:
                return $this->promoCodeRequirement($discountCriteria, $cart);
            case self::PRODUCT_OWN_TYPE:
                return $this->productOwnRequirement($discountCriteria);
            case self::CART_ITEMS_TOTAL_REQUIREMENT_TYPE:
                return $this->cartItemsTotalRequirement($discountCriteria, $cart);
            case self::IS_MEMBER_OF_BRAND_REQUIREMENT_TYPE:
                return $this->isMemberOfBrandRequirement($discountCriteria);
            default:
                return false;
        }
    }

    /**
     * @param DiscountCriteria $discountCriteria
     * @param Cart $cart
     *
     * @return bool
     *
     * @throws ORMException
     */
    public function productQuantityRequirementMet(DiscountCriteria $discountCriteria, Cart $cart): bool
    {
        $products = $this->productRepository->bySkus($cart->listSkus());

        $productsMap = [];

        foreach ($products as $product) {
            $productsMap[$product->getId()] = $product;
        }

        foreach ($discountCriteria->getProducts() as $dcProduct) {

            $productCartItem = $cart->getItemBySku($dcProduct->getSku());

            if (
            (isset($productsMap[$dcProduct->getId()]) &&
                $productCartItem &&
                ($productCartItem->getQuantity() >= (integer)$discountCriteria->getMin()) &&
                ($productCartItem->getQuantity() <= (integer)$discountCriteria->getMax())
                ||
                ((integer)$discountCriteria->getMin() == 0 &&
                (integer)$discountCriteria->getMax() == 0 &&
                empty($productCartItem))
            )
            ) {
                // if dcProduct is in cart with valid quantity
                if ($discountCriteria->getProductsRelationType() == DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY) {
                    return true;
                }
            }
            elseif ($discountCriteria->getProductsRelationType() == DiscountCriteria::PRODUCTS_RELATION_TYPE_ALL) {
                // if dcProduct is not in cart with valid quantity & discount criteria relation to producs == ALL
                return false;
            }
        }

        // discount criteria relation 'ANY' should have matched in above foreach block & already returned true
        // unsatisfied discount criteria relation 'ALL' should have matched in above foreach block & already returned false
        return $discountCriteria->getProductsRelationType() &&
            $discountCriteria->getProductsRelationType() != DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     *
     * @return bool
     */
    public function orderDateRequirement(DiscountCriteria $discountCriteria): bool
    {
        if (empty($discountCriteria->getMax()) || empty($discountCriteria->getMin())) {
            return false;
        }

        try {
            $maxDate = Carbon::parse($discountCriteria->getMax());
            $minDate = Carbon::parse($discountCriteria->getMin());
        } catch (Exception $e) {
            return false;
        }

        if ($maxDate !== false && $minDate !== false && Carbon::now() >= $minDate && Carbon::now() <= $maxDate) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     * @param float $totalDueInItems
     * @return bool
     */
    public function orderTotalRequirement(DiscountCriteria $discountCriteria, float $totalDueInItems): bool
    {
        if ($totalDueInItems >= (float)$discountCriteria->getMin() &&
            $totalDueInItems <= (float)$discountCriteria->getMax()) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     * @param float $totalDueInShipping
     * @return bool
     */
    public function orderShippingTotalRequirement(DiscountCriteria $discountCriteria, float $totalDueInShipping): bool
    {
        if ($totalDueInShipping >= (float)$discountCriteria->getMin() &&
            $totalDueInShipping <= (float)$discountCriteria->getMax()) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     * @param Cart $cart
     *
     * @return bool
     */
    public function orderShippingCountryRequirement(DiscountCriteria $discountCriteria, Cart $cart): bool
    {
        /**
         * @var $shippingAddress Address
         */
        $shippingAddress = $cart->getShippingAddress();

        if (!empty($shippingAddress) &&
            !empty($shippingAddress->getCountry()) &&
            (strtolower($shippingAddress->getCountry()) == strtolower($discountCriteria->getMin()) ||
                $discountCriteria->getMin() == '*' ||
                strtolower($shippingAddress->getCountry()) == strtolower($discountCriteria->getMax()) ||
                $discountCriteria->getMax() == '*')) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria ,
     * @param Cart $cart
     *
     * @return bool
     */
    public function promoCodeRequirement(DiscountCriteria $discountCriteria, Cart $cart): bool
    {
        if (!empty($cart->getPromoCode()) &&
            ($discountCriteria->getMin() == $cart->getPromoCode() ||
                $discountCriteria->getMax() == $cart->getPromoCode())) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     *
     * @return bool
     *
     * @throws Throwable
     */
    public function productOwnRequirement(DiscountCriteria $discountCriteria): bool
    {
        if ((integer)$discountCriteria->getMax() === 0 && !auth()->check()) {
            return true;
        }

   		if (!auth()->check() || !$discountCriteria->getProductsRelationType()) {
            return false;
        }

        $purchaser = $this->getPurchaser();

        $userProductsCount = $this->userProductRepository->getCountByUserDiscountCriteriaProducts(
            $purchaser,
            $discountCriteria,
            (integer)$discountCriteria->getMax() === 0 ? PHP_INT_MAX : null
        );

        if ((integer)$discountCriteria->getMax() === 0) {
            return $userProductsCount === 0;
        }

		return $discountCriteria->getProductsRelationType() == DiscountCriteria::PRODUCTS_RELATION_TYPE_ANY ?
            $userProductsCount > 0 : $userProductsCount == count($discountCriteria->getProducts());
    }

    /**
     * @param DiscountCriteria $discountCriteria ,
     * @param Cart $cart
     *
     * @return bool
     */
    public function cartItemsTotalRequirement(DiscountCriteria $discountCriteria, Cart $cart): bool
    {
        $cartItemsCount = 0;

        foreach ($cart->getItems() as $cartItem) {
            $cartItemsCount += $cartItem->getQuantity();
        }

        return $cartItemsCount >= $discountCriteria->getMin() && $cartItemsCount <= $discountCriteria->getMax();
    }

    /**
     * @param DiscountCriteria $discountCriteria ,
     * @param Cart $cart
     *
     * @return bool
     */
    public function isMemberOfBrandRequirement(DiscountCriteria $discountCriteria): bool
    {
        if (!auth()->check() || empty($discountCriteria->getMax()) || empty($discountCriteria->getMin())) {
            return false;
        }

        $purchaser = $this->getPurchaser();

        $brandUserIsAMemberOf = $this->userProvider->getBrandsUserIsAMemberOf($purchaser->getId());

        if (in_array((string)$discountCriteria->getMax(), $brandUserIsAMemberOf) ||
            in_array((string)$discountCriteria->getMin(), $brandUserIsAMemberOf)) {
            return true;
        }

        return false;
    }

    /**
     * @return User
     */
    protected function getPurchaser(): User
    {
        return self::$purchaser ?: $this->userProvider->getCurrentUser();
    }
}
