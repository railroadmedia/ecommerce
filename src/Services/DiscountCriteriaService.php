<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
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
     * @var UserProductRepository
     */
    private $userProductRepository;

    /**
     * @var User
     */
    protected $currentUser;

    const PRODUCT_QUANTITY_REQUIREMENT_TYPE = 'product quantity requirement';
    const DATE_REQUIREMENT_TYPE = 'date requirement';
    const ORDER_TOTAL_REQUIREMENT_TYPE = 'order total requirement';
    const SHIPPING_TOTAL_REQUIREMENT_TYPE = 'shipping total requirement';
    const SHIPPING_COUNTRY_REQUIREMENT_TYPE = 'shipping country requirement';
    const PROMO_CODE_REQUIREMENT_TYPE = 'promo code requirement';
    const PRODUCT_OWN_TYPE = 'product own requirement';

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

        $this->currentUser = $userProvider->getCurrentUser();
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
            default:
                return false;
        }
    }

    /**
     * @param DiscountCriteria $discountCriteria
     * @param Cart $cart
     *
     * @return bool
     */
    public function productQuantityRequirementMet(DiscountCriteria $discountCriteria, Cart $cart): bool
    {
        $products = $this->productRepository->bySkus($cart->listSkus());

        foreach ($products as $product) {
            $productCartItem = $cart->getItemBySku($product->getSku());

            if ($product->getId() ==
                $discountCriteria->getProduct()
                    ->getId() &&
                ($productCartItem->getQuantity() >= (integer)$discountCriteria->getMin()) &&
                ($productCartItem->getQuantity() <= (integer)$discountCriteria->getMax())) {
                return true;
            }
        }

        return false;
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
     * @param float $totalDueInShipping
     * @return bool
     */
    public function orderTotalRequirement(DiscountCriteria $discountCriteria, float $totalDueInShipping): bool
    {
        if ($totalDueInShipping >= (float)$discountCriteria->getMin() &&
            $totalDueInShipping <= (float)$discountCriteria->getMax()) {
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
        if (!auth()->check()) {
            return false;
        }

        /**
         * @var $qb QueryBuilder
         */
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb->select('COUNT(up)')
            ->where(
                $qb->expr()
                    ->eq('up.user', ':user')
            )
            ->andWhere(
                $qb->expr()
                    ->eq('up.product', ':product')
            )
            ->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->gte('up.expirationDate', ':now'),
                        $qb->expr()
                            ->isNull('up.expirationDate')
                    )
            )
            ->andWhere(
                $qb->expr()
                    ->between('up.quantity', ':min', ':max')
            )
            ->setParameter('user', $this->currentUser)
            ->setParameter('product', $discountCriteria->getProduct())
            ->setParameter('now', Carbon::now())
            ->setParameter('min', (integer)$discountCriteria->getMin())
            ->setParameter('max', (integer)$discountCriteria->getMax());

        return (integer)$qb->getQuery()
                ->getSingleScalarResult() > 0;
    }
}
