<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Structures\AddCartItemsResult;
use Railroad\Ecommerce\Services\CartService;

class AddToCartController extends BaseController
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * AddToCartController constructor.
     *
     * @param CartService $cartService
     */
    public function __construct(CartService $cartService)
    {
        parent::__construct();

        $this->cartService = $cartService;
    }

    /**
     * Add products to cart; if the products are active and available(the product stock > requested quantity).
     * The success field from response it's set to false if at least one product it's not active or available.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function addToCart(Request $request)
    {
        /** @var AddCartItemsResult $addCartItemsResult */
        $addCartItemsResult = $this->cartService->addToCart($request);

        /** @var RedirectResponse $redirectResponse */
        $redirectResponse =
            $request->get('redirect') ? redirect()->away($request->get('redirect')) : redirect()->back();

        $redirectResponse->with('success', $addCartItemsResult->getSuccess());
        $redirectResponse->with('addedProducts', $addCartItemsResult->getAddedProducts());
        $redirectResponse->with('cartNumberOfItems', count(Cart::fromSession()->getItems()));
        $redirectResponse->with('notAvailableProducts', $addCartItemsResult->getErrors());

        return $redirectResponse;
    }
}
