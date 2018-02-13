<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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

    /** Add products to cart; if the products are active and available(the product stock > requested quantity)
     * @param Request $request
     * @return array
     */
    public function addToCart(Request $request)
    {
        $input = $request->all();
        $errors = [];
        $addedProducts = [];

        if (!empty($input['locked']) && $input['locked'] == "true") {
            $this->cartService->lock();
        } elseif ($this->cartService->isLocked()) {
            $this->cartService->unlock();
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

                $product = $this->productService->getActiveProductFromSku($productSku);

                if (($product) && ($product['stock'] >= $quantityToAdd)) {
                    $addedProducts[] = $product;
                    $this->cartService->addItem($product['name'],
                        $product['description'],
                        $quantityToAdd,
                        $product['price'],
                        $product['is_physical'],
                        $product['is_physical'],
                        $product['subscription_interval_type'],
                        $product['subscription_interval_count'],
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
            'addedProducts' => $addedProducts,
            'cartNumberOfItems' => count($this->cartService->getAllCartItems()),
            'notAvailableProducts' => $errors
        ];

        return $response;
    }
}