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
     * PaymentPlanService constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /** Check if payment plan it's eligible: the order should not contain subscription product and
     *  totaling over a set amount (config paymentPlanMinimumPrice)
     * @param array $cartItems
     * @return bool
     */
    public function isPaymentPlanEligible(array $cartItems)
    {
        if((!$this->hasSubscriptionItems($cartItems['cartItems'])) &&
            (($cartItems['totalDue'] - $cartItems['totalTax'] - $cartItems['shippingCosts']) > config('ecommerce.paymentPlanMinimumPrice')))
        {
            return true;
        }

        return false;
    }

    /** Check if in the cart exists subscription products
     * @param array $cartItems
     * @return bool
     */
    public function hasSubscriptionItems(array $cartItems)
    {
        foreach($cartItems as $cartItem)
        {
            $product = $this->productRepository->read($cartItem['options']['product-id']);
            if($product['type'] == config('constants.TYPE_SUBSCRIPTION'))
            {
                return true;
            }
        }

        return false;
    }
}