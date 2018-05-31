<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Repositories\ProductRepository;

class TaxService
{
    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Railroad\Ecommerce\Services\DiscountCriteriaService
     */
    private $discountCriteriaService;

    const PRODUCT_AMOUNT_OFF_TYPE = 'product amount off';
    const PRODUCT_PERCENT_OFF_TYPE = 'product percent off';
    const SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE = 'subscription free trial days';
    const SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE = 'subscription recurring price amount off';
    const ORDER_TOTAL_AMOUNT_OFF_TYPE = 'order total amount off';
    const ORDER_TOTAL_PERCENT_OFF_TYPE = 'order total percent off';
    const ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE = 'order total shipping amount off';
    const ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE = 'order total shipping percent off';
    const ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE = 'order total shipping overwrite';

    /**
     * TaxService constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository, DiscountCriteriaService $discountCriteriaService)
    {
        $this->productRepository = $productRepository;
        $this->discountCriteriaService = $discountCriteriaService;
    }

    /** Calculate the tax rate based on country and region
     *
     * @param string $country
     * @param string $region
     * @return float|int
     */
    public function getTaxRate($country, $region)
    {
        if (array_key_exists(strtolower($country), ConfigService::$taxRate)) {
            if (array_key_exists(strtolower($region), ConfigService::$taxRate[strtolower($country)])) {
                return ConfigService::$taxRate[strtolower($country)][strtolower($region)];
            } else {
                return 0.05;
            }
        } else {
            return 0;
        }
    }

    /** Calculate the taxes on product and shipping costs for each cart item, the total due, the total taxes, the shipping costs and return an array with the following structure:
     *      'cartItems' => array
     *      'totalDue' => float
     *      'totalTax' => float
     *      'shippingCosts' => float
     *
     * @param array $cartItems
     * @param string $country
     * @param string $region
     * @param int $shippingCosts
     * @param null $currency
     * @return array
     */
    public function calculateTaxesForCartItems($cartItems, $country, $region, $shippingCosts = 0, $currency = null)
    {
        if (is_null($currency)) {
            $currency = ConfigService::$defaultCurrency;
        }

        $taxRate = $this->getTaxRate($country, $region);
        $discountsToApply = [];
        foreach ($cartItems as $key => $item) {
            $cartItems[$key]['totalPrice'] =
                ConfigService::$defaultCurrencyPairPriceOffsets[$currency][$item['totalPrice']] ?? $item['totalPrice'];
            $product = $this->productRepository->read($cartItems[$key]['options']['product-id']);
            if(!empty($product['discounts'])){
                $meetCriteria = ($this->discountCriteriaService->discountCriteriaMetForOrder($product['discounts'], $cartItems));
                if($meetCriteria){

                    $discountsToApply = array_merge($discountsToApply, $product['discounts']);
                }
            }
        }

        $cartItemsTotalDue = array_sum(array_column($cartItems, 'totalPrice'));

        //TODO: should be implemented
        if(empty($discountsToApply)){
            $discount = 0;
        } else{
            $discount = $this->getAmountDiscounted($discountsToApply, $cartItemsTotalDue);
        }

        $productsTaxAmount = round($cartItemsTotalDue * $taxRate, 2);

        $shippingTaxAmount = round((float)$shippingCosts * $taxRate, 2);

        $financeCharge = 0;

        $taxAmount = $productsTaxAmount + $shippingTaxAmount;

        $totalDue = round(
            $cartItemsTotalDue -
            $discount +
            $taxAmount +
            (float)$shippingCosts +
            $financeCharge,
            2
        );
        $cartItemsWeight = array_sum(array_column($cartItems, 'weight'));

        foreach ($cartItems as $key => $item) {
            if ($item['totalPrice'] > 0) {
                $cartItems[$key]['itemTax'] = $item['totalPrice'] / ($totalDue - $taxAmount) * $taxAmount;
            }
            $cartItems[$key]['itemShippingCosts'] =
                ($cartItemsWeight != 0) ? ($shippingCosts * ($cartItems[$key]['weight'] / $cartItemsWeight)) : 0;
        }
        $results['cartItems'] = $cartItems;
        $results['totalDue'] = $totalDue;
        $results['totalTax'] = $taxAmount;
        $results['shippingCosts'] = (float)$shippingCosts;

        return $results;
    }

    /** Calculate total taxes based on billing address and the amount that should be paid.
     *
     * @param integer $costs
     * @return float|int
     */
    public function getTaxTotal($costs, $country, $region)
    {
        return $costs * $this->getTaxRate($country, $region);
    }

    /**
     * @param $discountsToApply
     * @param $cartItemsTotalDue
     * @return float|int
     */
    public function getAmountDiscounted($discountsToApply, $cartItemsTotalDue)
    {
        $amountDiscounted = 0;

        foreach ($discountsToApply as $discount) {
            if ($discount['discount_type'] == self::ORDER_TOTAL_AMOUNT_OFF_TYPE) {
                $amountDiscounted += $discount['amount'];
            } elseif ($discount['discount_type'] == self::ORDER_TOTAL_PERCENT_OFF_TYPE) {
                $amountDiscounted += $discount['amount'] / 100 * $cartItemsTotalDue;
            }
        }

        return $amountDiscounted;
    }
}