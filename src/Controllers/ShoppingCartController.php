<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\Cart;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Resora\Entities\Entity;

class ShoppingCartController extends BaseController
{
    /**
     * @var Cart
     */
    private $cart;

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
     * @var TaxService
     */
    private $taxService;

    /**
     * ShoppingCartController constructor.
     *
     * @param \Railroad\Ecommerce\Services\CartService $cartService
     * @param \Railroad\Ecommerce\Repositories\ProductRepository $productRepository
     * @param \Railroad\Ecommerce\Services\CartAddressService $cartAddressService
     * @param \Railroad\Ecommerce\Services\TaxService $taxService
     */
    public function __construct(
        Cart $cart,
        CartService $cartService,
        ProductRepository $productRepository,
        CartAddressService $cartAddressService,
        PaymentPlanService $paymentPlanService,
        TaxService $taxService
    ) {
        parent::__construct();

        $this->cartService = $cartService;
        $this->productRepository = $productRepository;
        $this->cartAddressService = $cartAddressService;
        $this->paymentPlanService = $paymentPlanService;
        $this->taxService = $taxService;
        $this->cart = $cart;
    }

    /** Add products to cart; if the products are active and available(the product stock > requested quantity).
     *  The success field from response it's set to false if at least one product it's not active or available.
     *
     * @param Request $request
     * @return array
     */
    public function addToCart(Request $request)
    {
        $input = $request->all();
        $errors = [];
        $addedProducts = [];
        $success = false;

        $this->cart->fromSession();

        if (!empty($input['locked']) && $input['locked'] == "true") {
            $this->cart->lock();
        }

        if (!empty($input['products'])) {
            $products = $input['products'];

            foreach ($products as $productSku => $productInfo) {
                $productInfo = explode(',', $productInfo);
                $quantityToAdd = $productInfo[0];

                $product = $this->productRepository->query()
                    ->where(['sku' => $productSku])
                    ->first();

                if ($product && ($product['stock'] === null || $product['stock'] >= $quantityToAdd)) {
                    $success = true;
                    $addedProducts[] = $product;

                    $this->cart->addItem($product, $quantityToAdd);
                } else {
                    $message = 'Product with SKU:' . $productSku . ' could not be added to cart.';
                    $message .= (!is_null($product)) ?
                        ' The product stock(' .
                        $product['stock'] .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantityToAdd .
                        ')' : '';
                    $errors[] = ['message' => $message, 'product' => $product];
                }
            }
        }

        if (!empty($input['promo-code'])) {
            $this->cart->setPromoCode($input['promo-code']);
        } else {
            $this->cart->setPromoCode(null);
        }

        $response = [
            'addedProducts' => $addedProducts,
            'cartSubTotal' => $this->cart->getItemSubTotal(),
            'cartNumberOfItems' => count($this->cart->getItems()),
            'notAvailableProducts' => $errors,
        ];

        $this->cart->toSession();

        if (!empty($input['redirect'])) {
            return reply()->form(
                [$success],
                $input['redirect'],
                [],
                $response
            );
        }

        return reply()->form(
            [$success],
            null,
            [],
            $response
        );
    }

    /** Remove product from cart.
     *
     * @param int $productId
     * @return JsonResponse
     */
    public function removeCartItem($productId)
    {
        $this->cart->fromSession();

        $this->cart->removeItem($productId);

        $this->cart->toSession();

        return reply()->json(
            new Entity($this->cart->toArray()),
            [
                'code' => 201,
            ]
        );
    }

    /** Update the cart item quantity.
     * If the product it's not active or it's not available(the product stock it's smaller that the quantity)
     * an error message it's returned in notAvailableProducts, success = false and the cart item quantity it's not
     * modified.
     *
     * @param int $productId
     * @param int $quantity
     * @return JsonResponse
     */
    public function updateCartItemQuantity($productId, $quantity)
    {
        $this->cart->fromSession();

        $product = $this->productRepository->query()
            ->where(['id' => $productId])
            ->first();
        $errors = [];
        $success = false;

        if (($product) && ($product['stock'] >= $quantity)) {
            if ($quantity >= 0) {
                $success = true;

                $this->cart->updateItemQuantity($productId, $quantity);
            } else {
                $errors = ['Invalid quantity value.'];
            }
        } else {
            $message = 'The quantity can not be updated.';
            $message .= (is_object($product) && get_class($product) == Entity::class) ?
                ' The product stock(' .
                $product['stock'] .
                ') is smaller than the quantity you\'ve selected(' .
                $quantity .
                ')' : '';
            $errors[] = $message;
        }

        $this->cart->toSession();

        return reply()->json(
            new Entity($this->cart->toArray()),
            [
                'code' => 201,
            ]
        );
    }
}
