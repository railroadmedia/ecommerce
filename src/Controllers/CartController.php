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
    ) {
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
            // clear cart
            $this->cartService->clearCart();
        }

        $products = $this->parseProducts($request, $locked);
        $addedProducts = [];

        foreach ($products as $productSku => $quantityToAdd) {
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
        foreach ($request->get('bonuses', []) as $sku => $bonus) {
            $product = $this->productRepository->bySku($sku);

            if (!empty($product)) {
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
                    'sales_page_url' => $product->getSalesPageUrl(),
                ];
            }
        }

        if ($request->has('payment-plan') &&
            is_numeric($request->get('payment-plan', 1)) &&
            in_array($request->get('payment-plan', 1), config('ecommerce.payment_plan_options'))) {
            $this->cartService->getCart()->setPaymentPlanNumberOfPayments($request->get('payment-plan', 1));
        }

        $cartArray = $this->cartService->toArray();

        if (!empty($errors)) {
            $cartArray['errors'] = $errors;
        }

        /** @var RedirectResponse $redirectResponse */
        $redirectResponse =
            $request->get('redirect') ? redirect()->away($request->get('redirect')) : redirect()->to(
                config('ecommerce.post_add_to_cart_redirect', '/order')
            );

        $redirectResponse->with('cart', $cartArray);
        $redirectResponse->with('referralCode', $request->get('referralCode'));

        session()->put('bonuses', $bonusArray);

        if (!empty($addedProducts)) {
            session()->flash('addedProducts', $addedProducts);
            session()->flash('cartNumberOfItems', count($cartArray['items']));
            session()->flash('cartSubTotal', $cartArray['totals']['due']);
        }

        return $redirectResponse;
    }

    /**
     * @param Request $request
     * @param bool $locked
     * @return array
     * @throws Throwable
     */
    private function parseProducts(Request $request, bool $locked): array
    {
        $products = [];
        $productsArrayString = $request->get('product-array', []);
        if (empty($productsArrayString)) {
            foreach ($request->get('products', []) as $productSku => $quantityToAdd) {
                $products[$productSku] = $quantityToAdd;
            }
        } else {
            $products = [];
            foreach (explode(",", $productsArrayString) as $productString) {
                $productArray = explode(":", $productString);
                $products[$productArray[0]] = $productArray[1];
            }
        }
        return $products;
    }
}
