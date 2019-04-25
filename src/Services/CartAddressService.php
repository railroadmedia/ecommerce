<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Session\Store;
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

        $this->cartService->refreshCart();
    }

    /**
     * @return Address
     */
    public function getShippingAddress(): Address
    {
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
     * @return Address
     */
    public function updateShippingAddress(Address $address): Address
    {
        $currentAddress = $this->getShippingAddress();

        $address->merge($currentAddress);

        $cart = $this->cartService->getCart();

        $cart->setShippingAddress($address);

        $cart->toSession();

        return $address;
    }

    /**
     * @return Address
     */
    public function updateBillingAddress(Address $address): Address
    {
        $currentAddress = $this->getBillingAddress();

        $address->merge($currentAddress);

        $cart = $this->cartService->getCart();

        $cart->setBillingAddress($address);

        $cart->toSession();

        return $address;
    }
}
