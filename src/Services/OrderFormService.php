<?php

namespace Railroad\Ecommerce\Services;


class OrderFormService
{
    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * @var ShippingService
     */
    private $shippingService;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * OrderFormService constructor.
     * @param $cartAddressService
     */
    public function __construct(
        CartService $cartService,
        CartAddressService $cartAddressService,
        TaxService $taxService,
        ShippingService $shippingService
    )
    {
        $this->cartService = $cartService;
        $this->cartAddressService = $cartAddressService;
        $this->taxService = $taxService;
        $this->shippingService = $shippingService;
    }

    /** Get the taxes and shipping costs for all the cart items.
     * Return null if the cart it's empty;
     * otherwise an array with the following structure:
     *      'shippingAddress' => array|null
     *      'billingAddress'  => array|null
     *      'cartItems' => array
     *      'totalDue' => float
     *      'totalTax' => float
     *      'shippingCosts' => float
     * @return array|null
     */
    public function prepareOrderForm()
    {
        $cartItems = $this->cartService->getAllCartItems();
        if (empty($cartItems)) {
            return null;
        }

        $billingAddress = $this->cartAddressService->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);
        $shippingAddress = $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        $shippingCosts = $this->shippingService->getShippingCosts($cartItems, $shippingAddress);

        $taxes = $this->taxService->calculateTaxesForCartItems($cartItems, $billingAddress['country'], $billingAddress['region'], $shippingCosts);

        return array_merge(
            [
                'shippingAddress' => $shippingAddress,
                'billingAddress' => $billingAddress
            ],
            $taxes
        );
    }
}