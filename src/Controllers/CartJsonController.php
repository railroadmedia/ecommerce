<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Cart\Exceptions\AddToCartException;
use Railroad\Ecommerce\Cart\Exceptions\ProductNotFoundException;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\ResponseService;
use Spatie\Fractal\Fractal;

class CartJsonController extends BaseController
{
    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var PaymentPlanService
     */
    private $paymentPlanService;

    /**
     * ShoppingCartController constructor.
     *
     * @param  CartService             $cartService
     * @param  CartAddressService      $cartAddressService
     * @param  EcommerceEntityManager  $entityManager
     * @param  ProductRepository       $productRepository
     */
    public function __construct(
        CartService $cartService,
        CartAddressService $cartAddressService,
        EcommerceEntityManager $entityManager,
        ProductRepository $productRepository
    ) {
        parent::__construct();

        $this->cartService = $cartService;
        $this->cartAddressService = $cartAddressService;
        $this->entityManager = $entityManager;
        $this->productRepository = $productRepository;
    }

    /**
     * Add products to cart; if the products are active and available(the
     * product stock > requested quantity). The success field from response
     * it's set to false if at least one product it's not active or available.
     *
     * @param  Request  $request
     *
     * @throws ProductNotFoundException
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

        return ResponseService::create($cartArray, 'cart', null, null);
    }

    /**
     * Remove product from cart.
     *
     * @param  string $productSku
     *
     * @throws ProductNotFoundException
     * @return Fractal
     */
    public function removeCartItem(Request $request, $productSku)
    {
        $this->cartService->removeFromCart($productSku);

        return ResponseService::create($this->cartService->toArray(), 'cart', null, null);
    }

    /**
     * Update the cart item quantity.
     * If the product it's not active or it's not available(the product stock
     * it's smaller that the quantity) an error message it's returned in
     * notAvailableProducts, success = false and the cart item quantity it's
     * not modified.
     *
     * @param  int  $productId
     * @param  int  $quantity
     *
     * @throws ProductNotFoundException
     * @return Fractal
     */
    public function updateCartItemQuantity($productSku, $quantity)
    {
        $product = $this->productRepository->find($productId);

        $error = $this->cartService->updateCartItemProductQuantity(
            $product,
            $quantity
        );

        $cartItems = Cart::fromSession()->getItems();

        $cartMetaData = array_merge(
            [
                'success'              => ($error === null),
                'cartNumberOfItems'    => count($cartItems),
                'notAvailableProducts' => [$error],
            ],
            $this->getCartData()
        );

        return ResponseService::create($this->cartService->toArray(), 'cart', null, null);
    }

    /**
     * @return array
     */
    protected function getCartData()
    {
        $cartData = [
            'tax'   => 0,
            'total' => 0,
        ];

        $cartItems = Cart::fromSession()->getItems();

        if (count($cartItems)) {

            $cartData['tax'] = $this->cartService->getTotalTaxDue();
            $cartData['total'] = $this->cartService->getTotalDue();

            $isPaymentPlanEligible
                = $this->paymentPlanService->isPaymentPlanEligible();

            $paymentPlanPricing
                = $this->paymentPlanService->getPaymentPlanPricingForCartItems(
            );

            $cartData['isPaymentPlanEligible'] = $isPaymentPlanEligible;
            $cartData['paymentPlanPricing'] = $paymentPlanPricing;
        }

        return $cartData;
    }
}
