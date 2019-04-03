<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\AddCartItemsResult;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\ResponseService;
use Spatie\Fractal\Fractal;

class ShoppingCartJsonController extends BaseController
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
     * @param CartService $cartService
     * @param CartAddressService $cartAddressService
     * @param EcommerceEntityManager $entityManager
     * @param PaymentPlanService $paymentPlanService
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CartService $cartService,
        CartAddressService $cartAddressService,
        EcommerceEntityManager $entityManager,
        PaymentPlanService $paymentPlanService,
        ProductRepository $productRepository
    ) {
        parent::__construct();

        $this->cartService = $cartService;
        $this->cartAddressService = $cartAddressService;
        $this->entityManager = $entityManager;
        $this->paymentPlanService = $paymentPlanService;
        $this->productRepository = $productRepository;
    }

    /**
     * Add products to cart; if the products are active and available(the product stock > requested quantity).
     * The success field from response it's set to false if at least one product it's not active or available.
     *
     * @param Request $request
     *
     * @return Fractal
     */
    public function addToCart(Request $request)
    {
        /** @var AddCartItemsResult $addCartItemsResult */
        $addCartItemsResult = $this->cartService->addToCart($request);

        return ResponseService::addCartItemsResult($addCartItemsResult);
    }

    /**
     * Remove product from cart.
     *
     * @param int $productId
     *
     * @return Fractal
     */
    public function removeCartItem($productId)
    {
        $product = $this->productRepository->find($productId);

        $this->cartService->removeProductFromCart($product);

        $cartItems = Cart::fromSession()->getItems();

        $cartMetaData = $this->getCartData();

        return ResponseService::cartData($cartItems, $cartMetaData);
    }

    /**
     * Update the cart item quantity.
     * If the product it's not active or it's not available(the product stock it's smaller that the quantity)
     * an error message it's returned in notAvailableProducts, success = false and the cart item quantity it's not
     * modified.
     *
     * @param int $productId
     * @param int $quantity
     *
     * @return Fractal
     */
    public function updateCartItemQuantity($productId, $quantity)
    {
        $product = $this->productRepository->find($productId);

        $error = $this->cartService->updateCartItemProductQuantity($product, $quantity);

        $cartItems = Cart::fromSession()->getItems();

        $cartMetaData = array_merge(
            [
                'success' => ($error === null),
                'cartNumberOfItems' => count($cartItems),
                'notAvailableProducts' => [$error],
            ],
            $this->getCartData()
        );

        return ResponseService::cartData($cartItems, $cartMetaData);
    }

    /**
     * @return array
     */
    protected function getCartData()
    {
        $cartData = [
            'tax' => 0,
            'total' => 0,
        ];

        $cartItems = Cart::fromSession()->getItems();

        if (count($cartItems)) {

            $cartData['tax'] = $this->cartService->getTotalTaxDue();
            $cartData['total'] = $this->cartService->getTotalDue();

            $isPaymentPlanEligible = $this->paymentPlanService
                ->isPaymentPlanEligible();

            $paymentPlanPricing = $this->paymentPlanService
                ->getPaymentPlanPricingForCartItems();

            $cartData['isPaymentPlanEligible'] = $isPaymentPlanEligible;
            $cartData['paymentPlanPricing'] = $paymentPlanPricing;
        }

        return $cartData;
    }
}
