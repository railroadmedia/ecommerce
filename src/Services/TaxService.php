<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Repositories\DiscountRepository;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;

class TaxService
{
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
     * @var DiscountRepository
     */
    private $discountRepository;

    /**
     * @var \Railroad\Ecommerce\Services\CartService
     */
    private $cartService;

    /**
     * TaxService constructor.
     *
     * @param OrderItemRepository $orderItemRepository
     * @param OrderRepository $orderRepository
     * @param DiscountCriteriaService $discountCriteriaService
     * @param DiscountService $discountService
     * @param CartService $cartService
     * @param DiscountRepository $discountRepository
     */
    public function __construct(
        OrderItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        DiscountCriteriaService $discountCriteriaService,
        DiscountService $discountService,
        CartService $cartService,
        DiscountRepository $discountRepository
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->discountCriteriaService = $discountCriteriaService;
        $this->discountService = $discountService;
        $this->cartService = $cartService;
        $this->discountRepository = $discountRepository;
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
        $results = [
            'cartItemsSubTotal' => 0,
            'cartItems' => [],
            'totalDue' => 0,
            'totalTax' => 0,
            'shippingCosts' => 0,
            'pricePerPayment' => 0,
            'initialPricePerPayment' => 0,
        ];

        if (is_null($currency)) {
            $currency = ConfigService::$defaultCurrency;
        }

        $taxRate = $this->getTaxRate($country, $region);

        $discountsToApply = [];
        $activeDiscounts =
            $this->discountRepository->query()
                ->where('active', 1)
                ->get();

        foreach($cartItems as $key => $item)
        {
            $cartItems[$key]['totalPrice'] =
                ConfigService::$defaultCurrencyPairPriceOffsets[$currency][$item['totalPrice']] ?? $item['totalPrice'];

            $results['cartItemsSubTotal'] += ConfigService::$defaultCurrencyPairPriceOffsets[$currency][$item['totalPrice']]
                ??
                $item['totalPrice'];

            foreach ($activeDiscounts as $activeDiscount){
                $criteriaMet = true;
                foreach ($activeDiscount->criteria as $discountCriteria) {
                    if(!$this->discountCriteriaService->discountCriteriaMetForOrder(
                        $discountCriteria,
                        $cartItems,
                        $shippingCosts,
                        $this->cartService->getPromoCode()
                    )){
                        $criteriaMet = false;
                    }
                }

                if ($criteriaMet) {
                    $discountsToApply[$activeDiscount->id] =  $activeDiscount;

                    if ($activeDiscount['product_id'] == $item['options']['product-id']) {
                        $productDiscount = 0;

                        if ($activeDiscount->type == DiscountService::PRODUCT_AMOUNT_OFF_TYPE) {
                            $productDiscount = $activeDiscount->amount * $item['quantity'];
                        }

                        if ($activeDiscount->type == DiscountService::PRODUCT_PERCENT_OFF_TYPE) {
                            $productDiscount = $activeDiscount->amount / 100 * $item['price'] * $item['quantity'];
                        }

                        if ($activeDiscount->type == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                            $productDiscount = $activeDiscount->amount * $item['quantity'];
                        }

                        $cartItems[$key]['discountedPrice'] = $cartItems[$key]['totalPrice'] - $productDiscount;
                    }
                }
            }
        }

        $cartItems = $this->discountService->applyDiscounts($discountsToApply, $cartItems);

        $cartItemsTotalDue = array_sum(array_column($cartItems, 'totalPrice'));
        $discount = $this->discountService->getAmountDiscounted($discountsToApply, $cartItemsTotalDue, $cartItems);

        $shippingCostsWithDiscount =
            $this->discountService->getShippingCostsDiscounted($discountsToApply, $shippingCosts);
        $cartItemsTotalDueDiscounted = $cartItemsTotalDue - $discount;

        $productsTaxAmount = round($cartItemsTotalDueDiscounted * $taxRate, 2);

        $shippingTaxAmount = round((float)$shippingCostsWithDiscount * $taxRate, 2);

        $paymentPlan = $this->cartService->getPaymentPlanNumberOfPayments();

        $financeCharge = ($paymentPlan > 1) ? 1 : 0;

        $taxAmount = $productsTaxAmount + $shippingTaxAmount;

        $totalDue = $pricePerPayment = $initialPricePerPayment = round(
            $cartItemsTotalDueDiscounted +
            $taxAmount +
            (float) $shippingCostsWithDiscount +
            $financeCharge,
            2
        );

        if(!empty($paymentPlan) && $paymentPlan > 1)
        {

            $pricePerPayment = round(
                ($totalDue - $shippingCostsWithDiscount) / $paymentPlan,
                2
            );

            /*
             * We need to make sure we add any rounded off $$ back to the first payment.
             */
            $roundingFirstPaymentAdjustment = ($pricePerPayment * $paymentPlan) - ($totalDue - $shippingCostsWithDiscount);

            $initialPricePerPayment = round(
                $pricePerPayment - $roundingFirstPaymentAdjustment + $shippingCostsWithDiscount,
                2
            );
        }

        $cartItemsWeight = array_sum(array_column($cartItems, 'weight'));

        foreach($cartItems as $key => $item)
        {
            if($key === 'applyDiscount')
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
        $results['cartItemsSubTotalAfterDiscounts'] = $cartItemsTotalDueDiscounted;
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