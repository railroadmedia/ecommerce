<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\UserProductRepository;

class DiscountCriteriaService
{
    /**
     * @var \Railroad\Ecommerce\Services\CartAddressService
     */
    private $cartAddressService;

    /**
     * @var UserProductRepository
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
     */
    public function __construct(CartAddressService $cartAddressService, UserProductRepository $userProductRepository)
    {
        $this->cartAddressService = $cartAddressService;
        $this->userProductRepository = $userProductRepository;
    }

    /**
     * Check whether the discount criteria are met.
     *
     * @param array $discountCriteria
     * @param array $cartItems
     * @param int $shippingCosts
     * @param string $promoCode
     * @return bool
     */
    public function discountCriteriaMetForOrder(
        $criteria = [],
        $cart,
        $promoCode = ''
    ) {
            switch ($criteria['type']) {
                case self::PRODUCT_QUANTITY_REQUIREMENT_TYPE:
                    return $this->productQuantityRequirementMet($cart->getItems(), $criteria);
                case self::DATE_REQUIREMENT_TYPE:
                    return $this->orderDateRequirement($criteria);
                case self::ORDER_TOTAL_REQUIREMENT_TYPE:
                    return $this->orderTotalRequirement($cart, $criteria);
                case self::SHIPPING_TOTAL_REQUIREMENT_TYPE:
                    return $this->orderShippingTotalRequirement($cart->calculateShippingDue(false), $criteria);
                case self::SHIPPING_COUNTRY_REQUIREMENT_TYPE:
                    return $this->orderShippingCountryRequirement($criteria);
                case self::PROMO_CODE_REQUIREMENT_TYPE:
                    return $this->promoCodeRequirement($promoCode, $criteria);
                case self::PRODUCT_OWN_TYPE:
                    return $this->productOwnRequirement($criteria);
                default:
                    return false;
            }
    }

    /**
     * @param array $cartItems
     * @param array $discountCriteria
     * @return bool
     */
    public function productQuantityRequirementMet(array $cartItems, array $discountCriteria)
    {

        foreach ($cartItems as $cartItem) {
            if (($cartItem['options']['product-id'] == $discountCriteria['product_id']) &&
                ($cartItem['quantity'] >= (integer)$discountCriteria['min']) &&
                ($cartItem['quantity'] <= (integer)$discountCriteria['max'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $discountCriteria
     * @return bool
     */
    public function orderDateRequirement(array $discountCriteria)
    {
        if (empty($discountCriteria['max']) || empty($discountCriteria['min'])) {
            return false;
        }

        try {
            $maxDate = Carbon::parse($discountCriteria['max']);
            $minDate = Carbon::parse($discountCriteria['min']);
        } catch (Exception $e) {
            return false;
        }

        if ($maxDate !== false && $minDate !== false && Carbon::now() >= $minDate && Carbon::now() <= $maxDate) {
            return true;
        }

        return false;
    }

    /**
     * @param array $cartItems
     * @param array $discountCriteria
     * @return bool
     */
    public function orderTotalRequirement($cart, array $discountCriteria)
    {
        $cartItemsTotalWithoutTaxAndShipping = $cart->getTotalDue();

        if ($cartItemsTotalWithoutTaxAndShipping >= (float)$discountCriteria['min'] &&
            $cartItemsTotalWithoutTaxAndShipping <= (float)$discountCriteria['max']) {
            return true;
        }

        return false;
    }

    /**
     * @param int $shippingCosts
     * @param array $discountCriteria
     * @return bool
     */
    public function orderShippingTotalRequirement($shippingCosts, array $discountCriteria)
    {
        if ($shippingCosts >= (float)$discountCriteria['min'] && $shippingCosts <= (float)$discountCriteria['max']) {
            return true;
        }

        return false;
    }

    /**
     * @param array $discountCriteria
     * @return bool
     */
    public function orderShippingCountryRequirement(array $discountCriteria)
    {
        $shippingCountry = $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        if (!empty($shippingCountry) &&
            (strtolower($shippingCountry['country']) == strtolower($discountCriteria['min']) ||
                $discountCriteria['min'] == '*' ||
                strtolower($shippingCountry['country']) == strtolower($discountCriteria['max']) ||
                $discountCriteria['max'] == '*')) {
            return true;
        }

        return false;
    }

    /**
     * @param string $promoCode
     * @param array $discountCriteria
     * @return bool
     */
    public function promoCodeRequirement($promoCode, array $discountCriteria)
    {
        if (!empty($promoCode) && ($discountCriteria['min'] == $promoCode || $discountCriteria['max'] == $promoCode)) {
            return true;
        }

        return false;
    }

    public function productOwnRequirement(array $discountCriteria)
    {
        if (auth()->check()) {
            $userProducts =
                $this->userProductRepository->query()
                    ->where('user_id', auth()->id())
                    ->where('product_id', $discountCriteria['product_id'])
                    ->where(function ($query) {
                        $query->whereDate('expiration_date', '>=', Carbon::now()->toDateTimeString())
                            ->orWhereNull('expiration_date');
                    })
                    ->whereBetween('quantity', [(integer)$discountCriteria['min'], (integer)$discountCriteria['max']])
                    ->get();
            return $userProducts->isNotEmpty();
        }
        return false;
    }
}
