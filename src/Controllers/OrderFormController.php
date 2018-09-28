<?php

namespace Railroad\Ecommerce\Controllers;

use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\CartService;

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

    /** Submit an order
     *
     * @param $request
     * @return JsonResponse
     */
    public function submitOrder(OrderFormSubmitRequest $request)
    {
        $cartItems = $this->cartService->getAllCartItems();

        //if the cart it's empty; we throw an exception
        throw_if(
            empty($cartItems),
            new NotFoundException('The cart it\'s empty')
        );

        $result = $this->orderFormService
            ->processOrderForm($request, $cartItems);

        return reply()->form(
            [(!isset($result['errors']) && isset($result['order']))],
            $result['redirect'],
            $result['errors'] ?? [],
            isset($result['order']) ? ['order' => $result['order']] : []
        );
    }
}
