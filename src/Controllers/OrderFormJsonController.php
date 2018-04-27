<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\OrderFormService;

class OrderFormJsonController extends Controller
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var OrderFormService
     */
    private $orderFormService;

    /**
     * OrderFormJsonController constructor.
     *
     * @param $cartService
     */
    public function __construct(CartService $cartService, OrderFormService $orderFormService)
    {
        $this->cartService      = $cartService;
        $this->orderFormService = $orderFormService;
    }

    /** Prepare the order form based on request data
     *
     * @return JsonResponse
     */
    public function index()
    {
        $orderForm = $this->orderFormService->prepareOrderForm();

        //if the cart it's empty; we throw an exception
        throw_if(
            is_null($orderForm),
            new NotFoundException('The cart it\'s empty')
        );

        return new JsonResponse($orderForm, 200);
    }

    /** Submit an order
     *
     * @param $request
     * @return JsonResponse
     */
    public function submitOrder(OrderFormSubmitRequest $request)
    {
        $order = $this->orderFormService->submitOrder(
            $request->get('payment-type-selector'),
            $request->get('billing-country'),
            $request->get('billing-email'),
            $request->get('billing-zip-or-postal-code'),
            $request->get('billing-region'),
            $request->get('shipping-first-name'),
            $request->get('shipping-last-name'),
            $request->get('shipping-address-line-1'),
            $request->get('shipping-address-line-2'),
            $request->get('shipping-city'),
            $request->get('shipping-region'),
            $request->get('shipping-country'),
            $request->get('shipping-zip-or-postal-code'),
            $request->get('payment-plan-selector'),
            $request->get('paypal-express-checkout-token'),
            $request->get('stripe-credit-card-token'),
            $request->get('credit-card-month-selector'),
            $request->get('credit-card-year-selector'),
            $request->get('credit-card-number'),
            $request->get('credit-card-cvv'),
            $request->get('gateway')
        );

        return new JsonResponse($order, 200);
    }
}