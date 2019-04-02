<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Repositories\ProductRepository;

class AddToCartController extends BaseController
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * ShoppingCartController constructor.
     *
     * @param ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        parent::__construct();

        $this->productRepository = $productRepository;
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
        $input = $request->all();
        $errors = [];
        $addedProducts = [];
        $success = false;

        $cart = Cart::fromSession();

        // cart locking
        if (!empty($input['locked']) && $input['locked'] == "true") {
            $cart->setLocked(true);
        } elseif ($cart->getLocked()) {

            // if the cart is locked and a new item is added, we should wipe it first
            $cart = new Cart();
            $cart->toSession();
        }

        // promo code
        if (!empty($input['promo-code'])) {
            $cart->setPromoCode($input['promo-code']);
        }

        // products
        if (!empty($input['products'])) {
            $products = $input['products'];

            foreach ($products as $productSku => $productInfo) {
                $productInfo = explode(',', $productInfo);

                $quantityToAdd = $productInfo[0];

                $product = $this->productRepository->findOneBySku($productSku);

                if ($product && ($product->getStock() === null || $product->getStock() >= $quantityToAdd)) {
                    $cart->setItem(new CartItem($productSku, $quantityToAdd));

                    $success = true;
                    $addedProducts[] = $product;
                } else {
                    $message = 'Product with SKU:' . $productSku . ' could not be added to cart.';
                    $message .= (!is_null($product)) ?
                        ' The product stock(' .
                        $product->getStock() .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantityToAdd .
                        ')' : '';
                    $errors[] = ['message' => $message, 'product' => $product];
                }
            }
        }

        // save the cart to the session
        $cart->toSession();

        /** @var RedirectResponse $redirectResponse */
        $redirectResponse =
            $request->get('redirect') ? redirect()->away($request->get('redirect')) : redirect()->back();

        $redirectResponse->with('success', $success);
        $redirectResponse->with('addedProducts', $addedProducts);
        $redirectResponse->with('cartNumberOfItems', count($cart->getItems()));
        $redirectResponse->with('notAvailableProducts', $errors);

        return $redirectResponse;
    }
}
