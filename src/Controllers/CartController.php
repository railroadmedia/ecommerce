<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\Cart\AddToCartException;
use Railroad\Ecommerce\Services\CartService;
use Throwable;

class CartController extends Controller
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
        $this->cartService = $cartService;
    }

    /**
     * Add products to cart; if the products are active and available (the
     * product stock > requested quantity).
     * Errors are set in $response['meta']['cart']['errors']
     *
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws Throwable
     */
    public function addToCart(Request $request)
    {
        $errors = [];

        foreach ($request->get('products', []) as $productSku => $quantityToAdd) {

            try {
                $product = $this->cartService->addToCart(
                    $productSku,
                    $quantityToAdd,
                    $request->get('locked', false) == 'true',
                    $request->get('promo-code', '')
                );
            } catch (AddToCartException $addToCartException) {
                $errors[] = $addToCartException->getMessage();
                continue;
            }

            if (empty($product)) {
                $errors[] = 'Error adding product SKU '.$productSku
                    .' to the cart.';
            }
        }

        $cartArray = $this->cartService->toArray();

        if (!empty($errors)) {
            $cartArray['errors'] = $errors;
        }

        /** @var RedirectResponse $redirectResponse */
        $redirectResponse =
            $request->get('redirect') ? redirect()->away($request->get('redirect')) : redirect()->back();

        $redirectResponse->with('cart', $cartArray);

        return $redirectResponse;
    }
}
