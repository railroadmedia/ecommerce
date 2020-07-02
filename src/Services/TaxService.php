<?php

namespace Railroad\Ecommerce\Services;

use Doctrine\ORM\ORMException;
use Exception;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;

class TaxService
{
    /**
     * @var ShippingService
     */
    private $shippingService;

    const TAXABLE_COUNTRY = 'canada';

    /**
     * TaxService constructor.
     * @param ShippingService $shippingService
     */
    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

    /**
     * Calculate the product cost tax rate based on country and region
     *
     * @param Address|null $address
     *
     * @return float
     * @throws Exception
     */
    public function getProductTaxRate(?Address $address): float
    {
        if (empty($address) || empty($address->getCountry())) {
            return 0;
        }

        $rate = 0;
        $options = config('ecommerce.tax_rates_and_options', []);

        $countryOptions = $options[strtolower($address->getCountry())] ?? [];
        $regionOptions = $countryOptions[strtolower($address->getRegion())] ?? [];

        foreach ($regionOptions as $regionOption) {
            $rate += $regionOption['rate'];
        }

        return $rate;
    }

    /**
     * Calculate the shipping cost tax rate based on country and region
     *
     * @param Address|null $address
     *
     * @return float
     * @throws Exception
     */
    public function getShippingTaxRate(?Address $address): float
    {
        if (empty($address) || empty($address->getCountry())) {
            return 0;
        }

        $rate = 0;
        $options = config('ecommerce.tax_rates_and_options', []);

        $countryOptions = $options[strtolower($address->getCountry())] ?? [];
        $regionOptions = $countryOptions[strtolower($address->getRegion())] ?? [];

        foreach ($regionOptions as $regionOption) {

            if (isset($regionOption['applies_to_shipping_costs']) &&
                $regionOption['applies_to_shipping_costs'] == true) {

                $rate += $regionOption['rate'];
            }
        }

        return $rate;
    }

    /**
     * @param $productCosts
     * @param $shippingCosts
     * @param Address|null $address
     * @return array
     */
    public function getTaxesDuePerType($productCosts, $shippingCosts, ?Address $address)
    {
        if (empty($address) || empty($address->getCountry())) {
            return [];
        }

        $typeCosts = [];

        $options = config('ecommerce.tax_rates_and_options', []);
        $countryOptions = $options[strtolower($address->getCountry())] ?? [];
        $regionOptions = $countryOptions[strtolower($address->getRegion())] ?? [];

        foreach ($regionOptions as $regionOption) {
            $typeCosts[$regionOption['type']] = 0;

            if ($shippingCosts > 0 && isset($regionOption['applies_to_shipping_costs']) &&
                $regionOption['applies_to_shipping_costs'] == true) {

                $typeCosts[$regionOption['type']] += ($regionOption['rate'] * $shippingCosts);
            }

            $typeCosts[$regionOption['type']] += ($regionOption['rate'] * $productCosts);

            $typeCosts[$regionOption['type']] = round($typeCosts[$regionOption['type']], 2);
        }

        return $typeCosts;
    }

    /**
     * @param $productCosts
     * @param $shippingCosts
     * @param Address|null $address
     * @return float
     * @throws Exception
     */
    public function getTaxesDueTotal($productCosts, $shippingCosts, ?Address $address): float
    {
        return round(
            $this->getTaxesDueForProductCost($productCosts, $address) +
            $this->getTaxesDueForShippingCost($shippingCosts, $address),
            2
        );
    }

    /**
     * Calculate total taxes based on the address and the amount that should be paid.
     *
     * @param float $productCosts
     * @param Address|null $address
     *
     * @return float
     * @throws Exception
     */
    public function getTaxesDueForProductCost($productCosts, ?Address $address): float
    {
        return $productCosts * $this->getProductTaxRate($address);
    }

    /**
     * Calculate total taxes based on the address and the amount that should be paid.
     *
     * @param float $shippingCosts
     * @param Address|null $address
     *
     * @return float
     * @throws Exception
     */
    public function getTaxesDueForShippingCost($shippingCosts, ?Address $address): float
    {
        return $shippingCosts * $this->getShippingTaxRate($address);
    }

    /**
     * @param Cart $cart
     *
     * @return Address|null
     *
     * @throws ORMException
     */
    public function getAddressForTaxation(Cart $cart): ?Address
    {
        $taxableAddress = null;
        $billingAddress = $cart->getBillingAddress();
        $shippingAddress = $cart->getShippingAddress();

        // use the shipping address if set
        if (!empty($shippingAddress) &&
            !empty($shippingAddress->getCountry()) &&
            $this->shippingService->doesCartHaveAnyPhysicalItems($cart)) {

            $taxableAddress = $shippingAddress;
        }

        // otherwise use the billing address
        if (empty($taxableAddress) &&
            !empty($billingAddress) &&
            strtolower($billingAddress->getCountry()) == strtolower(self::TAXABLE_COUNTRY)) {

            $taxableAddress = $billingAddress;
        }

        return $taxableAddress;
    }
}