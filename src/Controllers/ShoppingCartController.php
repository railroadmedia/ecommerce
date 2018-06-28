<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Resora\Entities\Entity;

class ShoppingCartController extends BaseController
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
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var TaxService
     */
    private $taxService;

    /**
     * ShoppingCartController constructor.
     *
     * @param \Railroad\Ecommerce\Services\CartService           $cartService
     * @param \Railroad\Ecommerce\Repositories\ProductRepository $productRepository
     * @param \Railroad\Ecommerce\Services\CartAddressService    $cartAddressService
     * @param \Railroad\Ecommerce\Services\TaxService            $taxService
     */
    public function __construct(
        CartService $cartService,
        ProductRepository $productRepository,
        CartAddressService $cartAddressService,
        TaxService $taxService
    ) {
        parent::__construct();

        $this->cartService        = $cartService;
        $this->productRepository  = $productRepository;
        $this->cartAddressService = $cartAddressService;
        $this->taxService         = $taxService;
    }

    /** Add products to cart; if the products are active and available(the product stock > requested quantity).
     *  The success field from response it's set to false if at least one product it's not active or available.
     *
     * @param Request $request
     * @return array
     */
    public function addToCart(Request $request)
    {
        $input         = $request->all();
        $errors        = [];
        $addedProducts = [];
        $success       = false;
        $redirect      = false;

        if(!empty($input['locked']) && $input['locked'] == "true")
        {
            $this->cartService->lockCart();
        }
        elseif($this->cartService->isLocked())
        {
            $this->cartService->unlockCart();
        }

        if(array_key_exists('redirect', $input) && $input['redirect'] == "/shop")
        {
            $redirect = $input['redirect'];
        }

        if(!empty($input['products']))
        {
            $products = $input['products'];

            foreach($products as $productSku => $productInfo)
            {
                $productInfo = explode(',', $productInfo);

                $quantityToAdd = $productInfo[0];

                if(!empty($productInfo[1]))
                {
                    $subscriptionType      = !empty($productInfo[1]) ? $productInfo[1] : null;
                    $subscriptionFrequency = !empty($productInfo[2]) ? $productInfo[2] : null;
                }

                $product = $this->productRepository->query()->where(['sku' => $productSku])->first();

                if(($product) && ($product['stock'] >= $quantityToAdd))
                {
                    $success         = true;
                    $addedProducts[] = $product;
                    $this->cartService->addCartItem(
                        $product['name'],
                        $product['description'],
                        $quantityToAdd,
                        $product['price'],
                        $product['is_physical'],
                        $product['is_physical'],
                        $product['subscription_interval_type'],
                        $product['subscription_interval_count'],
                        $product['weight'],
                        [
                            'product-id'                => $product['id'],
                            'requires-shipping-address' => $product['is_physical'],
                        ]
                    );
                }
                else
                {

                    $message  = 'Product with SKU:' . $productSku . ' could not be added to cart.';
                    $message  .= (!is_null($product)) ?
                        ' The product stock(' .
                        $product['stock'] .
                        ') is smaller than the quantity you\'ve selected(' .
                        $quantityToAdd .
                        ')' : '';
                    $errors[] = $message;
                }
            }
        }

        $billingAddress = $this->cartAddressService->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);

        //if the promo code exists on the requests, set it on the session
        if(!empty($input['promo-code']))
        {
            $this->cartService->setPromoCode($input['promo-code']);
        }
        else
        {
            $this->cartService->setPromoCode(null);
        }

        $cartItemsPriceWithTax =
            $this->taxService->calculateTaxesForCartItems(
                $this->cartService->getAllCartItems(),
                $billingAddress['country'],
                $billingAddress['region']
            );

        $response = [
            'addedProducts'        => $addedProducts,
            'cartSubTotal'         => $cartItemsPriceWithTax['totalDue'],
            'cartNumberOfItems'    => count($this->cartService->getAllCartItems()),
            'notAvailableProducts' => $errors,
        ];

        if(!empty($input['redirect']))
        {
            return reply()->form([$success], $input['redirect']);
        }

        return reply()->form([$success],
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
        $cartItems = $this->cartService->getAllCartItems();

        foreach($cartItems as $cartItem)
        {
            if($cartItem['options']['product-id'] == $productId)
            {
                $this->cartService->removeCartItem($cartItem['id']);
            }
        }

        return reply()->json(null, [
            'code' => 204
        ]);
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
        $product = $this->productRepository->query()->where(['id' => $productId])->first();
        $errors  = [];
        $success = false;

        if(($product) && ($product['stock'] >= $quantity))
        {
            $success   = true;
            $cartItems = $this->cartService->getAllCartItems();

            foreach($cartItems as $cartItem)
            {
                if($cartItem['options']['product-id'] == $productId)
                {
                    $this->cartService->updateCartItemQuantity($cartItem['id'], $quantity);
                }
            }
        }
        else
        {
            $message  = 'The quantity can not be updated.';
            $message  .= (is_array($product)) ?
                ' The product stock(' .
                $product['stock'] .
                ') is smaller than the quantity you\'ve selected(' .
                $quantity .
                ')' : '';
            $errors[] = $message;
        }

        $response = new Entity([
            'success'              => $success,
            'addedProducts'        => $this->cartService->getAllCartItems(),
            'cartNumberOfItems'    => count($this->cartService->getAllCartItems()),
            'notAvailableProducts' => $errors,
        ]);

        return reply()->json($response, [
            'code' => 201
        ]);
    }
}