<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Entities\DiscountCriteria;
use Railroad\Ecommerce\Entities\UserProduct;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;

class DiscountCriteriaService
{
    /**
     * @var \Railroad\Ecommerce\Services\CartAddressService
     */
    private $cartAddressService;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository
     */
    private $userProductRepository;

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
     * @param \Railroad\Ecommerce\Services\CartAddressService $cartAddressService
     * @param EntityManager $entityManager
     */
    public function __construct(
        CartAddressService $cartAddressService,
        EntityManager $entityManager
    ) {
        $this->cartAddressService = $cartAddressService;
        $this->entityManager = $entityManager;

        $this->userProductRepository = $this->entityManager
                ->getRepository(UserProduct::class);
    }

    /**
     * Check whether the discount criteria are met.
     *
     * @param Cart $cart
     * @param DiscountCriteria $discountCriteria
     * @param string $promoCode
     *
     * @return bool
     */
    public function discountCriteriaMetForOrder(
        Cart $cart,
        DiscountCriteria $discountCriteria,
        string $promoCode = ''
    ): bool {
        // todo - review calls to method, param order updated
        switch ($discountCriteria->getType()) {
            case self::PRODUCT_QUANTITY_REQUIREMENT_TYPE:
                return $this->productQuantityRequirementMet(
                    $cart,
                    $discountCriteria
                );
            case self::DATE_REQUIREMENT_TYPE:
                return $this->orderDateRequirement($discountCriteria);
            case self::ORDER_TOTAL_REQUIREMENT_TYPE:
                return $this->orderTotalRequirement($cart, $discountCriteria);
            case self::SHIPPING_TOTAL_REQUIREMENT_TYPE:
                return $this->orderShippingTotalRequirement(
                    $discountCriteria,
                    $cart->calculateShippingDue(false)
                );
            case self::SHIPPING_COUNTRY_REQUIREMENT_TYPE:
                return $this->orderShippingCountryRequirement(
                    $discountCriteria
                );
            case self::PROMO_CODE_REQUIREMENT_TYPE:
                return $this->promoCodeRequirement(
                    $discountCriteria,
                    $promoCode
                );
            case self::PRODUCT_OWN_TYPE:
                return $this->productOwnRequirement($discountCriteria);
            default:
                return false;
        }
    }

    /**
     * @param Cart $cart
     * @param DiscountCriteria $discountCriteria
     *
     * @return bool
     */
    public function productQuantityRequirementMet(
        Cart $cart,
        DiscountCriteria $discountCriteria
    ): bool {
        foreach ($cart->getItems() as $cartItem) {
            if (
                ($cartItem->getOptions()['product-id'] == $discountCriteria->getProduct()->getId()) &&
                ($cartItem->getQuantity() >= (integer)$discountCriteria->getMin()) &&
                ($cartItem->getQuantity() <= (integer)$discountCriteria->getMax())
            ) {
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
    public function orderDateRequirement(
        DiscountCriteria $discountCriteria
    ): bool {
        if (
            empty($discountCriteria->getMax()) ||
            empty($discountCriteria->getMin())
        ) {
            return false;
        }

        try {
            $maxDate = Carbon::parse($discountCriteria->getMax());
            $minDate = Carbon::parse($discountCriteria->getMin());
        } catch (Exception $e) {
            return false;
        }

        if (
            $maxDate !== false &&
            $minDate !== false &&
            Carbon::now() >= $minDate &&
            Carbon::now() <= $maxDate
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param Cart $cart
     * @param DiscountCriteria $discountCriteria
     *
     * @return bool
     */
    public function orderTotalRequirement(
        Cart $cart,
        DiscountCriteria $discountCriteria
    ): bool {
        $cartItemsTotalWithoutTaxAndShipping = $cart->getTotalDue();

        if (
            $cartItemsTotalWithoutTaxAndShipping >= (float)$discountCriteria->getMin() &&
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
    public function orderShippingTotalRequirement(
        DiscountCriteria $discountCriteria,
        float $shippingCosts
    ): bool {
        // todo - review calls to method, param order updated

        if (
            $shippingCosts >= (float)$discountCriteria->getMin() &&
            $shippingCosts <= (float)$discountCriteria->getMax()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     *
     * @return bool
     */
    public function orderShippingCountryRequirement(
        DiscountCriteria $discountCriteria
    ): bool {
        /**
         * @var $shippingCountry \Railroad\Ecommerce\Entities\Structures\Address
         */
        $shippingCountry = $this->cartAddressService
            ->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        if (
            !empty($shippingCountry) &&
            !empty($shippingCountry->getCountry()) &&
            (
                strtolower($shippingCountry->getCountry()) == strtolower($discountCriteria->getMin()) ||
                $discountCriteria->getMin() == '*' ||
                strtolower($shippingCountry->getCountry()) == strtolower($discountCriteria->getMax()) ||
                $discountCriteria->getMax() == '*'
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria,
     * @param string $promoCode
     *
     * @return bool
     */
    public function promoCodeRequirement(
        DiscountCriteria $discountCriteria,
        ?string $promoCode
    ): bool {
        // todo - review calls to method, param order updated

        if (
            !empty($promoCode) &&
            (
                $discountCriteria->getMin() == $promoCode ||
                $discountCriteria->getMax() == $promoCode
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param DiscountCriteria $discountCriteria
     *
     * @return bool
     */
    public function productOwnRequirement(
        DiscountCriteria $discountCriteria
    ): bool {
        if (!auth()->check()) {
            return false;
        }

        // todo - review on user entity updates if user related logic may be replaced with constructor injected dependency

        /**
         * @var $qb \Doctrine\ORM\QueryBuilder
         */
        $qb = $this->userProductRepository->createQueryBuilder('up');

        $qb
            ->select('COUNT(up)')
            ->where($qb->expr()->eq('IDENTITY(up.user)', ':userId'))
            ->andWhere($qb->expr()->eq('up.product', ':product'))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->gte('up.expirationDate', ':now'),
                    $qb->expr()->isNull('up.expirationDate')
                )
            )
            ->andWhere($qb->expr()->between('up.quantity', ':min', ':max'))
            ->setParameter('userId', auth()->id())
            ->setParameter('product', $discountCriteria->getProduct())
            ->setParameter('now', Carbon::now())
            ->setParameter('min', (integer)$discountCriteria->getMin())
            ->setParameter('max', (integer)$discountCriteria->getMax());

        return (integer) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
