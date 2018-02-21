<?php

namespace Railroad\Ecommerce\Services;


use Railroad\Ecommerce\Repositories\ShippingRepository;

class ShippingService
{
    private $shippingRepository;

    /**
     * ShippingService constructor.
     * @param $shippingRepository
     */
    public function __construct(ShippingRepository $shippingRepository)
    {
        $this->shippingRepository = $shippingRepository;
    }

    public function getShippingCosts($cartItems, $country = '')
    {
        $cartItemsWeight = array_sum(array_column($cartItems, 'weight'));

        return $this->shippingRepository->getShippingCosts($country, $cartItemsWeight)['price'] ?? 0;
    }
}