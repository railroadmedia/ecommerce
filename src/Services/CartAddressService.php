<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Location\Services\LocationService;

class CartAddressService
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var LocationService
     */
    private $locationService;

    /**
     * CartAddressService constructor.
     *
     * @param CartService $cartService
     * @param LocationService $locationService
     */
    public function __construct(
        CartService $cartService,
        LocationService $locationService
    )
    {
        $this->cartService = $cartService;
        $this->locationService = $locationService;
    }

    /**
     * @return Address
     */
    public function getShippingAddress(): Address
    {
        $this->cartService->refreshCart();

        $cart = $this->cartService->getCart();

        $address = $cart->getShippingAddress();

        if (!$address) {

            $address = new Address($this->locationService->getCountry(), $this->locationService->getRegion());

            $cart->setShippingAddress($address);

            $cart->toSession();
        }

        return $address;
    }

    /**
     * @return Address
     */
    public function getBillingAddress(): Address
    {
        $this->cartService->refreshCart();

        $cart = $this->cartService->getCart();

        $address = $cart->getBillingAddress();

        if (!$address) {

            $address = new Address($this->locationService->getCountry(), $this->locationService->getRegion());

            $cart->setBillingAddress($address);

            $cart->toSession();
        }

        return $address;
    }

    /**
     * @param Address $address
     *
     * @return Address
     */
    public function updateShippingAddress(Address $address): Address
    {
        $this->cartService->refreshCart();

        $currentAddress = $this->getShippingAddress();

        $currentAddress->merge($address);

        $cart = $this->cartService->getCart();

        $cart->setShippingAddress($currentAddress);

        $cart->toSession();

        return $currentAddress;
    }

    /**
     * @param Address $address
     *
     * @return Address
     */
    public function updateBillingAddress(Address $address): Address
    {
        $this->cartService->refreshCart();

        $currentAddress = $this->getBillingAddress();

        $currentAddress->merge($address);

        $cart = $this->cartService->getCart();

        $cart->setBillingAddress($currentAddress);

        $cart->toSession();

        return $currentAddress;
    }
}
