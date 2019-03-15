<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Session\Store;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Location\Services\LocationService;

class CartAddressService
{
    /**
     * @var Store
     */
    private $session;

    /**
     * @var LocationService
     */
    private $locationService;

    CONST SESSION_KEY = 'cart-address-';

    CONST BILLING_ADDRESS_TYPE = 'billing';
    CONST SHIPPING_ADDRESS_TYPE = 'shipping';

    /**
     * CartAddressService constructor.
     *
     * @param Store $session
     * @param LocationService $locationService
     */
    public function __construct(
        Store $session,
        LocationService $locationService
    ) {
        $this->session = $session;
        $this->locationService = $locationService;
    }

    /**
     * Get from the session the address (shipping or billing address - based on address type).
     * If the billing address it's not set on the session call the method that set on the session the guessed billing address
     *
     * @param string $addressType
     * @return Address|null
     */
    public function getAddress(string $addressType)
    {
        if ($this->session->has(self::SESSION_KEY . $addressType)) {
            return $this->session->get(self::SESSION_KEY . $addressType);
        }

        if ($addressType == self::BILLING_ADDRESS_TYPE) {

            return $this->setAddress(
                new Address(
                    $this->locationService->getCountry(),
                    $this->locationService->getRegion()
                ),
                CartAddressService::BILLING_ADDRESS_TYPE
            );
        }

        return null;
    }

    /**
     * Set the address on the session and return it
     *
     * @param Address $address
     * @param string $addressType
     *
     * @return Address
     */
    public function setAddress(Address $address, string $addressType)
    {
        $this->session->put(
            self::SESSION_KEY . $addressType,
            $address
        );

        return $this->getAddress($addressType);
    }

    /**
     * Update the address stored on the session and return it
     *
     * @param Address $address
     * @param string $addressType
     *
     * @return Address
     */
    public function updateAddress(Address $address, string $addressType)
    {
        if ($currentAddress = $this->getAddress($addressType)) {
            $address->merge($currentAddress);
        }

        $this->session->put(self::SESSION_KEY . $addressType, $address);

        return $this->getAddress($addressType);
    }
}
