<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;

class TaxService
{
    const TAXABLE_COUNTRY = 'canada';
    const DEFAULT_STATE_KEY = 'alberta';
    const DEFAULT_RATE = 0;

    /**
     * @var ShippingService
     */
    private $shippingService;

    /**
     * TaxService constructor.
     * @param ShippingService $shippingService
     */
    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    /**
     * Calculate the tax rate based on country and region
     *
     * @param Address|null $address
     *
     * @return float
     */
    public function getTaxRate(?Address $address): float
    {
        if ($address && array_key_exists(strtolower($address->getCountry()), ConfigService::$taxRate)) {
            if (array_key_exists(
                strtolower($address->getState()),
                ConfigService::$taxRate[strtolower($address->getCountry())]
            )) {
                return ConfigService::$taxRate[strtolower($address->getCountry())][strtolower($address->getState())];
            }
            else {
                if (array_key_exists(
                    strtolower(self::DEFAULT_STATE_KEY),
                    ConfigService::$taxRate[strtolower($address->getCountry())]
                )) {
                    return ConfigService::$taxRate[strtolower($address->getCountry())][self::DEFAULT_STATE_KEY];
                }
                else {
                    return self::DEFAULT_RATE;
                }
            }
        }

        return self::DEFAULT_RATE;
    }

    /**
     * Calculate total taxes based on billing address and the amount that should be paid.
     *
     * @param float $costs
     * @param Address|null $address
     *
     * @return float
     */
    public function vat($costs, ?Address $address): float
    {
        return $costs * $this->getTaxRate($address);
    }

    /**
     * @param Cart $cart
     * @return Address|null
     */
    public function getAddressForTaxation(Cart $cart): ?Address
    {
        $taxableAddress = null;
        $billingAddress = $cart->getBillingAddress();
        $shippingAddress = $cart->getShippingAddress();

        // use the shipping address if set
        if ($shippingAddress) {
            $taxableAddress = $shippingAddress;
        }

        // otherwise use the billing address
        if (!$taxableAddress &&
            $billingAddress &&
            strtolower($billingAddress->getCountry()) == strtolower(self::TAXABLE_COUNTRY)) {
            $taxableAddress = $billingAddress;
        }

        return $taxableAddress;
    }
}