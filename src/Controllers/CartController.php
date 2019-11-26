<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Exceptions\Cart\AddToCartException;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\CartService;
use Throwable;

class CartController extends Controller
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var ProductRepository
     */
    private $productRepository;


    /**
     * AddToCartController constructor.
     *
     * @param CartService $cartService
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CartService $cartService,
        ProductRepository $productRepository
    )
    {
        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
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

        $this->cartService->refreshCart();

        $locked = (boolean)$request->get('locked', false);

        if ($locked == true) {
            $this->cartService->getCart()->replaceItems([]);
        }

        $addedProducts = [];

        foreach ($request->get('products', []) as $productSku => $quantityToAdd) {

            try {
                $product = $this->cartService->addToCart(
                    $productSku,
                    $quantityToAdd,
                    $locked,
                    $request->get('promo-code', '')
                );
            } catch (AddToCartException $addToCartException) {
                $errors[] = $addToCartException->getMessage();
                continue;
            }

            if (empty($product)) {
                $errors[] = 'Error adding product SKU ' . $productSku . ' to the cart.';
            } else {
                $addedProducts[] = [
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'thumbnail' => $product->getThumbnailUrl(),
                ];
            }
        }

        $bonusArray = [];
        foreach($request->get('bonuses', []) as $sku => $bonus) {
            $product = $this->productRepository->bySku($sku);

            if(!empty($product)){
                $bonusArray[] = [
                    'description' => $product->getDescription(),
                    'name' => $product->getName(),
                    'price_after_discounts' => 0,
                    'price_before_discounts' => $product->getPrice(),
                    'quantity' => 1,
                    'requires_shipping' => false,
                    'sku' => $product->getSku(),
                    'stock' => null,
                    'subscription_interval_type' => $product->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                        $product->getSubscriptionIntervalType() : null,
                    'subscription_interval_count' => $product->getType() == Product::TYPE_DIGITAL_SUBSCRIPTION ?
                        $product->getSubscriptionIntervalCount() : null,
                    'thumbnail_url' => $product->getThumbnailUrl(),
                ];
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

        session()->put('bonuses', $bonusArray);

        if (!empty($addedProducts)) {
            session()->flash('addedProducts', $addedProducts);
            session()->flash('cartNumberOfItems', count($cartArray['items']));
            session()->flash('cartSubTotal', $cartArray['totals']['due']);
        }

        return $redirectResponse;
    }
}
