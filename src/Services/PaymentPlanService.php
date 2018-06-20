<?php

namespace Railroad\Ecommerce\Services;

use Railroad\Ecommerce\Repositories\ProductRepository;

class PaymentPlanService
{
    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Railroad\Ecommerce\Services\CartService
     */
    private $cartService;

    /**
     * @var \Railroad\Ecommerce\Services\TaxService
     */
    private $taxService;

    /**
     * @var \Railroad\Ecommerce\Services\CartAddressService
     */
    private $cartAddressService;

    /**
     * PaymentPlanService constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\ProductRepository $productRepository
     * @param \Railroad\Ecommerce\Services\CartService           $cartService
     * @param \Railroad\Ecommerce\Services\CartAddressService    $cartAddressService
     * @param \Railroad\Ecommerce\Services\TaxService            $taxService
     */
    public function __construct(
        ProductRepository $productRepository,
        CartService $cartService,
        CartAddressService $cartAddressService,
        TaxService $taxService
    ) {
        $this->productRepository  = $productRepository;
        $this->cartService        = $cartService;
        $this->cartAddressService = $cartAddressService;
        $this->taxService         = $taxService;
    }

    /** Check if payment plan it's eligible: the order should not contain subscription product and
     *  totaling over a set amount (config paymentPlanMinimumPrice)
     *
     * @param array $cartItems
     * @return bool
     */
    public function isPaymentPlanEligible()
    {
        $billingAddress = $this->cartAddressService->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);
        $cartItems      = $this->taxService->calculateTaxesForCartItems(
            $this->cartService->getAllCartItems(),
            $billingAddress['country'],
            $billingAddress['region'],
            $this->cartService->getPromoCode()
        );

        if((!$this->hasSubscriptionItems($cartItems)) &&
            (($cartItems['totalDue'] - $cartItems['totalTax'] - $cartItems['shippingCosts']) > config('ecommerce.paymentPlanMinimumPrice')))
        {
            return true;
        }

        return false;
    }

    /** Check if in the cart exists subscription products
     *
     * @param array $cartItems
     * @return bool
     */
    public function hasSubscriptionItems(array $cartItems)
    {
        foreach($cartItems['cartItems'] as $cartItem)
        {
            $product = $this->productRepository->read($cartItem['options']['product-id']);
            if($product['type'] == config('constants.TYPE_SUBSCRIPTION'))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getPaymentPlanPricingForCartItems()
    {
        $paymentPlanPricing = [];
        if($this->isPaymentPlanEligible())
        {
            $cartItems      = $this->cartService->getAllCartItems();
            $billingAddress = $this->cartAddressService->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);
            foreach(config('ecommerce.paymentPlanOptions') as $paymentPlan)
            {
                $this->cartService->setPaymentPlanNumberOfPayments($paymentPlan);
                $costsAndTaxes                    = $this->taxService->calculateTaxesForCartItems(
                    $cartItems,
                    $billingAddress['country'],
                    $billingAddress['region'],
                    $this->cartService->getPromoCode()
                );
                $paymentPlanPricing[$paymentPlan] = $costsAndTaxes['pricePerPayment'];
            }
        }

        return $paymentPlanPricing;
    }
}