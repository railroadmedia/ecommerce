<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\Cart\AddToCartException;
use Railroad\Ecommerce\Exceptions\Cart\ProductNotFoundException;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ResponseService;
use Spatie\Fractal\Fractal;
use Throwable;

class CartJsonController extends Controller
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * ShoppingCartController constructor.
     *
     * @param  CartService             $cartService
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
     * @param  Request  $request
     *
     * @throws Throwable
     *
     * @return Fractal
     */
    public function addCartItem(Request $request)
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

        return ResponseService::cart($cartArray);
    }

    /**
     * Remove product from cart.
     *
     * @param  string $productSku
     *
     * @throws Throwable
     *
     * @return Fractal
     */
    public function removeCartItem(string $productSku)
    {
        $errors = [];

        try {
            $this->cartService->removeFromCart($productSku);
        } catch (ProductNotFoundException $exception) {
            $errors[] = $exception->getMessage();
        }

        $cartArray = $this->cartService->toArray();

        if (!empty($errors)) {
            $cartArray['errors'] = $errors;
        }

        return ResponseService::cart($cartArray);
    }

    /**
     * Update the cart item quantity; if the product is active and available (the
     * product stock > requested quantity).
     * Errors are set in $response['meta']['cart']['errors']
     *
     * @param  string  $productSku
     * @param  int  $quantity
     *
     * @throws Throwable
     *
     * @return Fractal
     */
    public function updateCartItemQuantity(string $productSku, int $quantity)
    {
        $errors = [];

        try {
            $this->cartService->updateCartQuantity($productSku, $quantity);
        } catch (AddToCartException $addToCartException) {
            $errors[] = $addToCartException->getMessage();
        }

        $cartArray = $this->cartService->toArray();

        if (!empty($errors)) {
            $cartArray['errors'] = $errors;
        }

        return ResponseService::cart($cartArray);
    }
}
