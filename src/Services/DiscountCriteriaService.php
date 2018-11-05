<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Railroad\Ecommerce\Entities\Cart;
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
     * @param $criteria
     * @param Cart $cart
     * @return bool
     */
    public function discountCriteriaMetForOrder($criteria, Cart $cart)
    {
        switch ($criteria['type']) {
            case self::PRODUCT_QUANTITY_REQUIREMENT_TYPE:
                return $this->productQuantityRequirementMet($cart, $criteria);
            case self::DATE_REQUIREMENT_TYPE:
                return $this->orderDateRequirement($criteria);
            case self::ORDER_TOTAL_REQUIREMENT_TYPE:
                return $this->orderTotalRequirement($cart, $criteria);
            case self::SHIPPING_TOTAL_REQUIREMENT_TYPE:
                return $this->orderShippingTotalRequirement($cart, $criteria);
            case self::SHIPPING_COUNTRY_REQUIREMENT_TYPE:
                return $this->orderShippingCountryRequirement($cart, $criteria);
            case self::PROMO_CODE_REQUIREMENT_TYPE:
                return $this->promoCodeRequirement($cart, $criteria);
            case self::PRODUCT_OWN_TYPE:
                return $this->productOwnRequirement($criteria);
            default:
                return false;
        }
    }

    /**
     * @param Cart $cart
     * @param array $discountCriteria
     * @return bool
     */
    public function productQuantityRequirementMet(Cart $cart, array $discountCriteria)
    {
        foreach ($cart->getItems() as $cartItem) {
            if (($cartItem->product['id'] == $discountCriteria['product_id']) &&
                ($cartItem->quantity >= (integer)$discountCriteria['min']) &&
                ($cartItem->quantity <= (integer)$discountCriteria['max'])) {
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
     * @param Cart $cart
     * @param array $discountCriteria
     * @return bool
     */
    public function orderTotalRequirement(Cart $cart, array $discountCriteria)
    {
        if ($cart->getItemSubTotalAfterDiscounts() >= (float)$discountCriteria['min'] &&
            $cart->getItemSubTotalAfterDiscounts() <= (float)$discountCriteria['max']) {
            return true;
        }

        return false;
    }

    /**
     * @param Cart $cart
     * @param array $discountCriteria
     * @return bool
     */
    public function orderShippingTotalRequirement(Cart $cart, array $discountCriteria)
    {
        if ($cart->getShippingTotal() >= (float)$discountCriteria['min'] &&
            $cart->getShippingTotal() <= (float)$discountCriteria['max']) {
            return true;
        }

        return false;
    }

    /**
     * @param Cart $cart
     * @param array $discountCriteria
     * @return bool
     */
    public function orderShippingCountryRequirement(Cart $cart, array $discountCriteria)
    {
        $shippingCountry = $cart->getShippingAddress()['country'] ?? '';

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
     * @param Cart $cart
     * @param array $discountCriteria
     * @return bool
     */
    public function promoCodeRequirement(Cart $cart, array $discountCriteria)
    {
        if (!empty($cart->getPromoCode()) &&
            ($discountCriteria['min'] == $cart->getPromoCode() || $discountCriteria['max'] == $cart->getPromoCode())) {
            return true;
        }

        return false;
    }

    /**
     * @param array $discountCriteria
     * @return bool
     */
    public function productOwnRequirement(array $discountCriteria)
    {
        if (auth()->check()) {
            $userProducts = $this->userProductRepository->query()
                ->where('user_id', auth()->id())
                ->where('product_id', $discountCriteria['product_id'])
                ->where(
                    function ($query) {
                        $query->whereDate(
                            'expiration_date',
                            '>=',
                            Carbon::now()
                                ->toDateTimeString()
                        )
                            ->orWhereNull('expiration_date');
                    }
                )
                ->whereBetween('quantity', [(integer)$discountCriteria['min'], (integer)$discountCriteria['max']])
                ->get();
            return $userProducts->isNotEmpty();
        }
        return false;
    }
}
