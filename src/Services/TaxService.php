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

        if ($address && array_key_exists(strtolower($address->getCountry()), config('ecommerce.product_tax_rate'))) {
            if (array_key_exists(
                strtolower($address->getRegion()),
                config('ecommerce.product_tax_rate')[strtolower($address->getCountry())]
            )) {
                $rate = config('ecommerce.product_tax_rate')[strtolower($address->getCountry())][strtolower(
                    $address->getRegion()
                )];
            }
        }

        if (
            $address->getCountry() &&
            $address->getRegion() &&
            isset(config('ecommerce.qst_tax_rate')[strtolower($address->getCountry())][strtolower($address->getRegion())])
        ) {
            $rate += config('ecommerce.qst_tax_rate')[strtolower($address->getCountry())][strtolower(
                    $address->getRegion()
                )];
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

        if ($address && array_key_exists(strtolower($address->getCountry()), config('ecommerce.shipping_tax_rate'))) {
            if (array_key_exists(
                strtolower($address->getRegion()),
                config('ecommerce.shipping_tax_rate')[strtolower($address->getCountry())]
            )) {
                $rate = config('ecommerce.shipping_tax_rate')[strtolower($address->getCountry())][strtolower(
                    $address->getRegion()
                )];
            }
        }

        if (
            $address->getCountry() &&
            $address->getRegion() &&
            isset(config('ecommerce.qst_tax_rate')[strtolower($address->getCountry())][strtolower($address->getRegion())])
        ) {
            $rate += config('ecommerce.qst_tax_rate')[strtolower($address->getCountry())][strtolower(
                    $address->getRegion()
                )];
        }

        return $rate;
    }

    /**
     * This is not used in calculating payment/orders totals, its only used to display GST on the invoice.
     *
     * @param Address|null $address
     * @return int
     * @throws Exception
     */
    public function getGSTTaxRate(?Address $address)
    {
        if (empty($address) || empty($address->getCountry())) {
            return 0;
        }

        if ($address &&
            array_key_exists(strtolower($address->getCountry()), config('ecommerce.gst_hst_tax_rate_display_only'))) {
            if (array_key_exists(
                strtolower($address->getRegion()),
                config('ecommerce.gst_hst_tax_rate_display_only')[strtolower($address->getCountry())]
            )) {
                return config('ecommerce.gst_hst_tax_rate_display_only')[strtolower($address->getCountry())][strtolower(
                    $address->getRegion()
                )];
            }
        }

        return 0;
    }


    /**
     * This is not used in calculating payment/orders totals, its only used to display PST on the invoice.
     *
     * @param Address|null $address
     * @return int
     * @throws Exception
     */
    public function getPSTTaxRate(?Address $address)
    {
        if (empty($address) || empty($address->getCountry())) {
            return 0;
        }

        if ($address &&
            array_key_exists(strtolower($address->getCountry()), config('ecommerce.pst_tax_rate_display_only', []))) {
            if (array_key_exists(
                strtolower($address->getRegion()),
                config('ecommerce.pst_tax_rate_display_only')[strtolower($address->getCountry())]
            )) {
                return config('ecommerce.pst_tax_rate_display_only')[strtolower($address->getCountry())][strtolower(
                    $address->getRegion()
                )];
            }
        }

        return 0;
    }

    /**
     * This is not used in calculating payment/orders totals, its only used to display QST on the invoice.
     *
     * @param Address|null $address
     * @return int
     * @throws Exception
     */
    public function getQSTTaxRate(?Address $address)
    {
        if (empty($address) || empty($address->getCountry())) {
            return 0;
        }

        if ($address &&
            array_key_exists(strtolower($address->getCountry()), config('ecommerce.qst_tax_rate', []))) {
            if (array_key_exists(
                strtolower($address->getRegion()),
                config('ecommerce.qst_tax_rate')[strtolower($address->getCountry())]
            )) {
                return config('ecommerce.qst_tax_rate')[strtolower($address->getCountry())][strtolower(
                    $address->getRegion()
                )];
            }
        }

        return 0;
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