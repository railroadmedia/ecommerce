<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;

class TaxService
{
    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Railroad\Ecommerce\Services\DiscountCriteriaService
     */
    private $discountCriteriaService;

    /**
     * @var \Railroad\Ecommerce\Services\DiscountService
     */
    private $discountService;

    /**
     * @var \Railroad\Ecommerce\Services\CartService
     */
    private $cartService;

    /**
     * TaxService constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\ProductRepository   $productRepository
     * @param \Railroad\Ecommerce\Repositories\OrderItemRepository $orderItemRepository
     * @param \Railroad\Ecommerce\Repositories\OrderRepository     $orderRepository
     * @param \Railroad\Ecommerce\Services\DiscountCriteriaService $discountCriteriaService
     */
    public function __construct(
        ProductRepository $productRepository,
        OrderItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        DiscountCriteriaService $discountCriteriaService,
        DiscountService $discountService,
        CartService $cartService
    ) {
        $this->productRepository       = $productRepository;
        $this->orderItemRepository     = $orderItemRepository;
        $this->orderRepository         = $orderRepository;
        $this->discountCriteriaService = $discountCriteriaService;
        $this->discountService         = $discountService;
        $this->cartService             = $cartService;
    }

    /** Calculate the tax rate based on country and region
     *
     * @param string $country
     * @param string $region
     * @return float|int
     */
    public function getTaxRate($country, $region)
    {
        if(array_key_exists(strtolower($country), ConfigService::$taxRate))
        {
            if(array_key_exists(strtolower($region), ConfigService::$taxRate[strtolower($country)]))
            {
                return ConfigService::$taxRate[strtolower($country)][strtolower($region)];
            }
            else
            {
                return 0.05;
            }
        }
        else
        {
            return 0;
        }
    }

    /** Calculate the taxes on product and shipping costs for each cart item, the total due, the total taxes, the shipping costs and return an array with the following structure:
     *      'cartItems' => array
     *      'totalDue' => float
     *      'totalTax' => float
     *      'shippingCosts' => float
     *
     * @param array  $cartItems
     * @param string $country
     * @param string $region
     * @param int    $shippingCosts
     * @param null   $currency
     * @return array
     */
    public function calculateTaxesForCartItems($cartItems, $country, $region, $shippingCosts = 0, $currency = null)
    {
        if(is_null($currency))
        {
            $currency = ConfigService::$defaultCurrency;
        }

        $taxRate          = $this->getTaxRate($country, $region);
        $discountsToApply = [];

        foreach($cartItems as $key => $item)
        {
            $cartItems[$key]['totalPrice'] =
                ConfigService::$defaultCurrencyPairPriceOffsets[$currency][$item['totalPrice']] ?? $item['totalPrice'];
            $product                       = $this->productRepository->read($cartItems[$key]['options']['product-id']);
            if(!empty($product['discounts']))
            {
                //Check whether the discount criteria are met
                $meetCriteria = ($this->discountCriteriaService->discountCriteriaMetForOrder($product['discounts'], $cartItems, $shippingCosts));
                if($meetCriteria)
                {
                    $discountsToApply = array_merge($discountsToApply, $product['discounts']);
                }
            }
        }

        $cartItemsTotalDue = array_sum(array_column($cartItems, 'totalPrice'));

        $cartItems = $this->discountService->applyDiscounts($discountsToApply, $cartItems);
        $discount  = $this->discountService->getAmountDiscounted($discountsToApply, $cartItemsTotalDue, $cartItems);

        $shippingCostsWithDiscount = $this->discountService->getShippingCostsDiscounted($discountsToApply, $shippingCosts);

        $productsTaxAmount = round($cartItemsTotalDue * $taxRate, 2);

        $shippingTaxAmount = round((float) $shippingCostsWithDiscount * $taxRate, 2);

        $paymentPlan = $this->cartService->getPaymentPlanNumberOfPayments();

        $financeCharge = ($paymentPlan > 1) ? 1 : 0;

        $taxAmount = $productsTaxAmount + $shippingTaxAmount;

        $totalDue = $pricePerPayment = $initialPricePerPayment = round(
            $cartItemsTotalDue -
            $discount +
            $taxAmount +
            (float) $shippingCostsWithDiscount +
            $financeCharge,
            2
        );
        if(!empty($paymentPlan) && $paymentPlan > 1)
        {
            $initialPricePerPayment = round(
                $cartItemsTotalDue / $paymentPlan + $shippingCostsWithDiscount + $taxAmount,
                2
            );
            $pricePerPayment        = round(
                $cartItemsTotalDue / $paymentPlan + $taxAmount,
                2
            );
        }

        $cartItemsWeight = array_sum(array_column($cartItems, 'weight'));

        foreach($cartItems as $key => $item)
        {
            if($key == 'applyDiscount')
            {
                break;
            }
            if($item['totalPrice'] > 0)
            {
                $cartItems[$key]['itemTax'] = $item['totalPrice'] / ($totalDue - $taxAmount) * $taxAmount;
            }
            $cartItems[$key]['itemShippingCosts'] =
                ($cartItemsWeight != 0) ? ($shippingCostsWithDiscount * ($cartItems[$key]['weight'] / $cartItemsWeight)) : 0;
        }
        $results['cartItems']              = $cartItems;
        $results['totalDue']               = $totalDue;
        $results['totalTax']               = $taxAmount;
        $results['shippingCosts']          = (float) $shippingCostsWithDiscount;
        $results['pricePerPayment']        = $pricePerPayment;
        $results['initialPricePerPayment'] = $initialPricePerPayment;

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
}