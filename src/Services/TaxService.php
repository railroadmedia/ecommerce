<?php

namespace Railroad\Ecommerce\Services;

use Doctrine\ORM\ORMException;
use Exception;
use Railroad\Ecommerce\Entities\Structures\Address;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Permissions\Services\PermissionService;

class TaxService
{
    /**
     * @var ShippingService
     */
    private $shippingService;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var PermissionService
     */
    private $permissionService;

    const TAXABLE_COUNTRY = 'canada';

    /**
     * TaxService constructor.
     * @param ShippingService $shippingService
     * @param PaymentMethodRepository $paymentMethodRepository
     */
    public function __construct(
        ShippingService $shippingService,
        PaymentMethodRepository $paymentMethodRepository,
        PermissionService $permissionService
    )
    {
        $this->shippingService = $shippingService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->permissionService = $permissionService;
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

            if ($this->isTaxTypeApplicable(
                $address->getCountry(),
                $address->getRegion(),
                $regionOption['type'],
                $this->getTaxablePaymentGateway())) {

                $rate += $regionOption['rate'];
            }
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
                $regionOption['applies_to_shipping_costs'] == true &&
                $this->isTaxTypeApplicable(
                    $address->getCountry(),
                    $address->getRegion(),
                    $regionOption['type'],
                    $this->getTaxablePaymentGateway()
                )
            ) {
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

        if (empty($options) || empty($countryOptions) || empty($regionOptions)) {
            return [];
        }

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

        // if not found and the user is going to use an existing payment method, use that methods address
        if (empty($taxableAddress) && !empty($cart->getPaymentMethodId())) {
            $paymentMethod = $this->paymentMethodRepository->byId($cart->getPaymentMethodId());

            if (!empty($paymentMethod) && !empty($paymentMethod->getBillingAddress())) {
                $taxableAddress = $paymentMethod->getBillingAddress()->toStructure();
            }
        }

        return $taxableAddress;
    }

    /**
     * @return string|null
     */
    private function getTaxablePaymentGateway()
    {
        if ($this->permissionService->can(auth()->id(), 'place-orders-for-other-users')) {
            return request()->get('brand', config('ecommerce.brand'));
        }

        return config('ecommerce.brand');
    }

    /**
     * @param $country
     * @param $region
     * @param $taxType
     * @param $paymentGatewayName
     * @return bool
     */
    private function isTaxTypeApplicable($country, $region, $taxType, $paymentGatewayName)
    {
        $country = strtolower($country);
        $region = strtolower($region);

        $taxOptions = config('ecommerce.tax_rates_and_options', []);

        if (!empty($taxOptions[$country][$region])) {
            foreach ($taxOptions[$country][$region] as $taxTypeData) {

                if ($taxTypeData['type'] == $taxType &&
                    !empty($taxTypeData['gateway_blacklist']) &&
                    is_array($taxTypeData['gateway_blacklist']) &&
                    in_array($paymentGatewayName, $taxTypeData['gateway_blacklist'])) {
                    return false;
                }
            }
        }

        return true;
    }
}