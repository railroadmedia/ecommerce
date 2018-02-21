<?php

namespace Railroad\Ecommerce\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ShippingService;
use Railroad\Ecommerce\Services\TaxService;

class OrderFormJsonController extends Controller
{
    private $cartService;
    private $taxService;
    private $shippingService;

    /**
     * OrderFormJsonController constructor.
     * @param $cartService
     */
    public function __construct(CartService $cartService, TaxService $taxService, ShippingService $shippingService)
    {
        $this->cartService = $cartService;
        $this->taxService = $taxService;
        $this->shippingService = $shippingService;
    }

    public function index(Request $request)
    {
        $input = $request->all();

        //TODO: should be implemented
        $guessedCountry = "United states";
        $guessedRegion = "bc";

        $cartItems = $this->cartService->getAllCartItems();

        if (empty($cartItems)) {
            //TODO
            return 'Empty cart';
        }


        $shippingCosts = $this->shippingService->getShippingCosts($cartItems, $guessedCountry);

        $cartItemsWithTax = $this->taxService->getCartItemsWithTax($cartItems, $guessedCountry, $guessedRegion, $shippingCosts);

        dd($cartItemsWithTax);
    }
}