<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;

class DiscountCriteriaService
{
    const PRODUCT_QUANTITY_REQUIREMENT_TYPE = 'product quantity requirement';
    const DATE_REQUIREMENT_TYPE             = 'date requirement';
    const ORDER_TOTAL_REQUIREMENT_TYPE      = 'order total requirement';
    const SHIPPING_TOTAL_REQUIREMENT_TYPE   = 'shipping total requirement';
    const SHIPPING_COUNTRY_REQUIREMENT_TYPE = 'shipping country requirement';
    const PROMO_CODE_REQUIREMENT_TYPE       = 'promo code requirement';

    public function discountCriteriaMetForOrder(
        $discountCriteria = [],
        $cartItems = [],
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
                    //TODO: validation for the rest of criteria
               // case self::SHIPPING_TOTAL_REQUIREMENT_TYPE:
               //     return $this->orderShippingTotalRequirement($cartItems, $criteria);
               // case self::SHIPPING_COUNTRY_REQUIREMENT_TYPE:
               //     return $this->orderShippingCountryRequirement($cartItems, $criteria);
               // case self::PROMO_CODE_REQUIREMENT_TYPE:
               //     return $this->promoCodeRequirement($promoCode, $criteria);
                default:
                    return false;
            }
        }
    }

    /**
     * @param array $cartItems
     * @param       $discountCriteria
     * @return bool
     */
    public function productQuantityRequirementMet(array $cartItems, $discountCriteria)
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
     * @param $discountCriteria
     * @return bool
     */
    public function orderDateRequirement($discountCriteria)
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
     * @param $cartItems
     * @param $discountCriteria
     * @return bool
     */
    public function orderTotalRequirement($cartItems, $discountCriteria)
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
}
