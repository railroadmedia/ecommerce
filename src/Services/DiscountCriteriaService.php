<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Throwable;

class DiscountCriteriaService
{
    /**
     * @var CartAddressService
     */
    private $cartAddressService;


    /**
     * @var EcommerceEntityManager
     */
    protected $entityManager;

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
     * @param CartAddressService $cartAddressService
     * @param CartService $cartService
     * @param EcommerceEntityManager $entityManager
     * @param UserProductRepository $userProductRepository
     * @param UserProviderInterface $userProvider
     */
    public function __construct(
        CartAddressService $cartAddressService,
        EcommerceEntityManager $entityManager,
        UserProductRepository $userProductRepository,
        UserProviderInterface $userProvider
    ) {
        $this->cartAddressService = $cartAddressService;
        $this->entityManager = $entityManager;
        $this->userProductRepository = $userProductRepository;

        $this->currentUser = $userProvider->getCurrentUser();
    }

    /**
     * Check whether the discount criteria are met.
     *
     * @param DiscountCriteria $discountCriteria
     * @param string $promoCode
     *
     * @return bool
     *
     * @throws Throwable
     */
    public function discountCriteriaMetForOrder(
        DiscountCriteria $discountCriteria,
        ?string $promoCode = ''
    ): bool {
        switch ($discountCriteria->getType()) {
            case self::PRODUCT_QUANTITY_REQUIREMENT_TYPE:
                return $this->productQuantityRequirementMet($discountCriteria);
            case self::DATE_REQUIREMENT_TYPE:
                return $this->orderDateRequirement($discountCriteria);
            case self::ORDER_TOTAL_REQUIREMENT_TYPE:
                return $this->orderTotalRequirement($discountCriteria);
            case self::SHIPPING_TOTAL_REQUIREMENT_TYPE:
                return $this->orderShippingTotalRequirement($discountCriteria);
            case self::SHIPPING_COUNTRY_REQUIREMENT_TYPE:
                return $this->orderShippingCountryRequirement($discountCriteria);
            case self::PROMO_CODE_REQUIREMENT_TYPE:
                return $this->promoCodeRequirement($discountCriteria, $promoCode);
            case self::PRODUCT_OWN_TYPE:
                return $this->productOwnRequirement($discountCriteria);
            default:
                return false;
        }
    }

    /**
     * @param DiscountCriteria $discountCriteria
     *
     * @return bool
     */
    public function productQuantityRequirementMet(DiscountCriteria $discountCriteria): bool
    {
        $this->cartService->refreshCart();

        $cart = Cart::fromSession();

        $products = $this->productRepository->findBySkus(['sku' => $cart->listSkus()]);

        foreach ($products as $product) {
            $productCartItem = $cart->getItemBySku($product->getSku());

            if ($product->getId() == $discountCriteria->getProduct()->getId() &&
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
     *
     * @return bool
     */
    public function orderTotalRequirement(DiscountCriteria $discountCriteria): bool
    {
        $cartItemsTotalWithoutTaxAndShipping = $this->cartService->getProductsDue();

        if ($cartItemsTotalWithoutTaxAndShipping >= (float)$discountCriteria->getMin() &&
            $cartItemsTotalWithoutTaxAndShipping <= (float)$discountCriteria->getMax()) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     * @param float $shippingCosts
     *
     * @return bool
     */
    public function orderShippingTotalRequirement(DiscountCriteria $discountCriteria): bool
    {
        $initialShippingCosts = $this->cartService->getInitialShippingDue();

        if ($initialShippingCosts >= (float)$discountCriteria->getMin() &&
            $initialShippingCosts <= (float)$discountCriteria->getMax()) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     *
     * @return bool
     */
    public function orderShippingCountryRequirement(DiscountCriteria $discountCriteria): bool
    {
        /**
         * @var $shippingCountry \Railroad\Ecommerce\Entities\Structures\Address
         */
        $shippingCountry = $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        if (!empty($shippingCountry) &&
            !empty($shippingCountry->getCountry()) &&
            (strtolower($shippingCountry->getCountry()) == strtolower($discountCriteria->getMin()) ||
                $discountCriteria->getMin() == '*' ||
                strtolower($shippingCountry->getCountry()) == strtolower($discountCriteria->getMax()) ||
                $discountCriteria->getMax() == '*')) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria ,
     * @param string $promoCode
     *
     * @return bool
     */
    public function promoCodeRequirement(
        DiscountCriteria $discountCriteria,
        ?string $promoCode
    ): bool {
        if (!empty($promoCode) &&
            ($discountCriteria->getMin() == $promoCode || $discountCriteria->getMax() == $promoCode)) {
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
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb->select('COUNT(up)')
            ->where($qb->expr()
                ->eq('up.user', ':user'))
            ->andWhere($qb->expr()
                ->eq('up.product', ':product'))
            ->andWhere($qb->expr()
                ->orX($qb->expr()
                    ->gte('up.expirationDate', ':now'), $qb->expr()
                    ->isNull('up.expirationDate')))
            ->andWhere($qb->expr()
                ->between('up.quantity', ':min', ':max'))
            ->setParameter('user', $this->currentUser)
            ->setParameter('product', $discountCriteria->getProduct())
            ->setParameter('now', Carbon::now())
            ->setParameter('min', (integer)$discountCriteria->getMin())
            ->setParameter('max', (integer)$discountCriteria->getMax());

        return (integer)$qb->getQuery()
                ->getSingleScalarResult() > 0;
    }
}
