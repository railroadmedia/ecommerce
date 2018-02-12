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


    public function addToCart(Request $request)
    {
        $input = $request->all();

        if (!empty($input['locked']) && $input['locked'] == "true") {
            $this->cartService->lock();
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

                throw_if(empty($product), new NotFoundException('The product could not be added to cart.'));
                throw_if(($product['stock'] == 0), new NotFoundException('The product stock is empty and can not be added to cart.'));

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
            }
        }

        return ($this->cartService->getAllCartItems());
    }
}