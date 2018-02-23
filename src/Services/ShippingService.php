<?php

namespace Railroad\Ecommerce\Services;


use Railroad\Ecommerce\Repositories\ShippingRepository;

class ShippingService
{

    /**
     * @var ShippingRepository
     */
    private $shippingRepository;

    /**
     * ShippingService constructor.
     * @param $shippingRepository
     */
    public function __construct(ShippingRepository $shippingRepository)
    {
        $this->shippingRepository = $shippingRepository;
    }

    /** Calculate the shipping costs based on Country and cart items total weight.
     *  If the shipping address it's not defined return 0
     * @param array $cartItems
     * @param array $shippingAddress
     * @return int
     */
    public function getShippingCosts($cartItems, $shippingAddress)
    {
        if (empty($shippingAddress)) {
            return 0;
        }

        $cartItemsWeight = array_sum(array_column($cartItems, 'weight'));

        return $this->shippingRepository->getShippingCosts($shippingAddress['country'], $cartItemsWeight)['price'] ?? 0;
    }
}