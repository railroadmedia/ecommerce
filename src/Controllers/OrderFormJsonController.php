<?php

namespace Railroad\Ecommerce\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
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
     * @param $cartService
     */
    public function __construct(CartService $cartService, OrderFormService $orderFormService)
    {
        $this->cartService = $cartService;
        $this->orderFormService = $orderFormService;
    }

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
}