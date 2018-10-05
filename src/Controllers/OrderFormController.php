<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;

class OrderFormController extends BaseController
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var \Railroad\Ecommerce\Services\OrderFormService
     */
    private $orderFormService;

    /**
     * OrderFormJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Services\CartService      $cartService
     * @param \Railroad\Ecommerce\Services\OrderFormService $orderFormService
     */
    public function __construct(
        CartService $cartService,
        OrderFormService $orderFormService
    ) {
        parent::__construct();

        $this->cartService = $cartService;
        $this->orderFormService = $orderFormService;
    }

    /**
     * Landing action for paypal agreement redirect
     *
     * @param $request
     * @return JsonResponse
     */
    public function submitPaypalOrder(Request $request)
    {
        $cartItems = $this->cartService->getAllCartItems();

        //if the cart it's empty; we throw an exception
        throw_if(
            !$request->has('token'),
            new NotFoundException('Invalid request')
        );

        $result = $this->orderFormService
            ->processOrderForm($request, $cartItems);

        return reply()->form(
            [(!isset($result['errors']) && isset($result['order']))],
            url()->route(ConfigService::$paypalAgreementFulfilledRoute),
            $result['errors'] ?? [],
            isset($result['order']) ? ['order' => $result['order']] : []
        );
    }
}
