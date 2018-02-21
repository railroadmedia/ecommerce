<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ProductService;
use Railroad\Ecommerce\Exceptions\NotFoundException;

class ShoppingCartController extends Controller
{
    private $cartService;
    private $productService;

    /**
     * ShoppingCartController constructor.
     * @param $cartService
     * @param $productService
     */
    public function __construct(CartService $cartService, ProductService $productService)
    {
        $this->cartService = $cartService;
        $this->productService = $productService;
    }

    /** Add products to cart; if the products are active and available(the product stock > requested quantity).
     *  The success field from response it's set to false if at least one product it's not active or available.
     * @param Request $request
     * @return array
     */
    public function addToCart(Request $request)
    {
        $input = $request->all();
        $errors = [];
        $addedProducts = [];
        $success = false;
        $redirect = false;

        if (!empty($input['locked']) && $input['locked'] == "true") {
            $this->cartService->lockCart();
        } elseif ($this->cartService->isLocked()) {
            $this->cartService->unlockCart();
        }

        if (array_key_exists('redirect', $input) && $input['redirect'] == "/shop") {
            $redirect = $input['redirect'];
        }

        if (!empty($input['products'])) {
            $products = $input['products'];

            foreach ($products as $productSku => $productInfo) {
                $productInfo = explode(',', $productInfo);

                $quantityToAdd = $productInfo[0];

                if (!empty($productInfo[1])) {
                    $subscriptionType = !empty($productInfo[1]) ? $productInfo[1] : null;
                    $subscriptionFrequency = !empty($productInfo[2]) ? $productInfo[2] : null;
                    $productSku .= '-' . $subscriptionFrequency . '-' . $subscriptionType;
                }

                $product = $this->productService->getProductByConditions(['sku' => $productSku]);
                if (($product) && ($product['stock'] >= $quantityToAdd)) {
                    $success = true;
                    $addedProducts[] = $product;
                    $this->cartService->addCartItem($product['name'],
                        $product['description'],
                        $quantityToAdd,
                        $product['price'],
                        $product['is_physical'],
                        $product['is_physical'],
                        $product['subscription_interval_type'],
                        $product['subscription_interval_count'],
                        $product['weight'],
                        [
                            'product-id' => $product['id']
                        ]);
                } else {
                    $message = 'Product with SKU:' . $productSku . ' could not be added to cart.';
                    $message .= (is_array($product)) ? ' The product stock(' . $product['stock'] . ') is smaller than the quantity you\'ve selected(' . $quantityToAdd . ')' : '';
                    $errors[] = $message;
                }
            }
        }

        $response = [
            'success' => $success,
            'addedProducts' => $addedProducts,
            'cartNumberOfItems' => count($this->cartService->getAllCartItems()),
            'notAvailableProducts' => $errors
        ];

        if ($redirect) {
            $response['redirect'] = $redirect;
        }

        return new JsonResponse($response, 200);
    }

    /** Remove product from cart.
     * @param int $productId
     * @return JsonResponse
     */
    public function removeCartItem($productId)
    {
        $cartItems = $this->cartService->getAllCartItems();

        foreach ($cartItems as $cartItem) {
            if ($cartItem->options['product-id'] == $productId) {
                $this->cartService->removeCartItem($cartItem->id);
            }
        }

        return new JsonResponse(null, 204);
    }

    /** Update the cart item quantity.
     * If the product it's not active or it's not available(the product stock it's smaller that the quantity)
     * an error message it's returned in notAvailableProducts, success = false and the cart item quantity it's not modified.
     * @param int $productId
     * @param int $quantity
     * @return JsonResponse
     */
    public function updateCartItemQuantity($productId, $quantity)
    {
        $product = $this->productService->getProductByConditions(['id' => $productId]);
        $errors = [];
        $success = false;

        if (($product) && ($product['stock'] >= $quantity)) {
            $success = true;
            $cartItems = $this->cartService->getAllCartItems();

            foreach ($cartItems as $cartItem) {
                if ($cartItem->options['product-id'] == $productId) {
                    $this->cartService->updateCartItemQuantity($cartItem->id, $quantity);
                }
            }
        } else {
            $message = 'The quantity can not be updated.';
            $message .= (is_array($product)) ? ' The product stock(' . $product['stock'] . ') is smaller than the quantity you\'ve selected(' . $quantity . ')' : '';
            $errors[] = $message;
        }

        $response = [
            'success' => $success,
            'addedProducts' => $this->cartService->getAllCartItems(),
            'cartNumberOfItems' => count($this->cartService->getAllCartItems()),
            'notAvailableProducts' => $errors
        ];

        return new JsonResponse($response, 201);
    }
}