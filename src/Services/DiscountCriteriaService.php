<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;

class DiscountCriteriaService
{
    /**
     * @var \Railroad\Ecommerce\Services\CartAddressService
     */
    private $cartAddressService;

    const PRODUCT_QUANTITY_REQUIREMENT_TYPE = 'product quantity requirement';
    const DATE_REQUIREMENT_TYPE             = 'date requirement';
    const ORDER_TOTAL_REQUIREMENT_TYPE      = 'order total requirement';
    const SHIPPING_TOTAL_REQUIREMENT_TYPE   = 'shipping total requirement';
    const SHIPPING_COUNTRY_REQUIREMENT_TYPE = 'shipping country requirement';
    const PROMO_CODE_REQUIREMENT_TYPE       = 'promo code requirement';

    /**
     * DiscountCriteriaService constructor.
     *
     * @param \Railroad\Ecommerce\Services\CartAddressService $cartAddressService
     */
    public function __construct(CartAddressService $cartAddressService)
    {
        $this->cartAddressService = $cartAddressService;
    }

    /**
     * Check whether the discount criteria are met.
     * @param array  $discountCriteria
     * @param array  $cartItems
     * @param int    $shippingCosts
     * @param string $promoCode
     * @return bool
     */
    public function discountCriteriaMetForOrder(
        $discountCriteria = [],
        $cartItems = [],
        $shippingCosts = 0,
        $promoCode = ''
    ) {
        foreach($discountCriteria as $criteria)
        {
            switch($criteria['type'])
            {
                case self::PRODUCT_QUANTITY_REQUIREMENT_TYPE:
                    return $this->productQuantityRequirementMet($cartItems, $criteria);
                case self::DATE_REQUIREMENT_TYPE:
                    return $this->orderDateRequirement($criteria);
                case self::ORDER_TOTAL_REQUIREMENT_TYPE:
                    return $this->orderTotalRequirement($cartItems, $criteria);
                case self::SHIPPING_TOTAL_REQUIREMENT_TYPE:
                    return $this->orderShippingTotalRequirement($shippingCosts, $criteria);
                case self::SHIPPING_COUNTRY_REQUIREMENT_TYPE:
                    return $this->orderShippingCountryRequirement($criteria);
                case self::PROMO_CODE_REQUIREMENT_TYPE:
                    return $this->promoCodeRequirement($promoCode, $criteria);
                default:
                    return false;
            }
        }
    }

    /**
     * @param array $cartItems
     * @param array $discountCriteria
     * @return bool
     */
    public function productQuantityRequirementMet(array $cartItems, array $discountCriteria)
    {

        foreach($cartItems as $cartItem)
        {
            if(($cartItem['options']['product-id'] == $discountCriteria['product_id']) &&
                ($cartItem['quantity'] >= (integer) $discountCriteria['min']) &&
                ($cartItem['quantity'] <= (integer) $discountCriteria['max'])
            )
            {
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
        if(empty($discountCriteria['max']) || empty($discountCriteria['min']))
        {
            return false;
        }

        try
        {
            $maxDate = Carbon::parse($discountCriteria['max']);
            $minDate = Carbon::parse($discountCriteria['min']);
        }
        catch(Exception $e)
        {
            return false;
        }

        if($maxDate !== false &&
            $minDate !== false &&
            Carbon::now() >= $minDate &&
            Carbon::now() <= $maxDate
        )
        {
            return true;
        }

        return false;
    }

    /**
     * @param array $cartItems
     * @param array $discountCriteria
     * @return bool
     */
    public function orderTotalRequirement(array $cartItems, array $discountCriteria)
    {
        $cartItemsTotalWithoutTaxAndShipping = array_sum(array_column($cartItems, 'totalPrice'));

        if($cartItemsTotalWithoutTaxAndShipping >= (float) $discountCriteria['min'] &&
            $cartItemsTotalWithoutTaxAndShipping <= (float) $discountCriteria['max']
        )
        {
            return true;
        }

        return false;
    }

    /**
     * @param int   $shippingCosts
     * @param array $discountCriteria
     * @return bool
     */
    public function orderShippingTotalRequirement($shippingCosts, array $discountCriteria)
    {
        if($shippingCosts >= (float) $discountCriteria['min'] &&
            $shippingCosts <= (float) $discountCriteria['max']
        )
        {
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

        if(
            !empty($shippingCountry) &&
            (strtolower($shippingCountry['country']) ==
                strtolower($discountCriteria['min']) ||
                $discountCriteria['min'] == '*' ||
                strtolower($shippingCountry['country']) ==
                strtolower($discountCriteria['max']) ||
                $discountCriteria['max'] == '*')
        )
        {
            return true;
        }

        return false;
    }

    /**
     * @param string $promoCode
     * @param array  $discountCriteria
     * @return bool
     */
    public function promoCodeRequirement($promoCode, array $discountCriteria)
    {
        if(!empty($promoCode) &&
            ($discountCriteria['min'] == $promoCode || $discountCriteria['max'] == $promoCode)
        )
        {
            return true;
        }

        return false;
    }
}
