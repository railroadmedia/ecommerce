<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\ResponseService;
use Spatie\Fractal\Fractal;
use Throwable;

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
     * @param CartService $cartService
     * @param OrderFormService $orderFormService
     */
    public function __construct(
        CartService $cartService,
        OrderFormService $orderFormService
    )
    {
        $this->cartService = $cartService;
        $this->orderFormService = $orderFormService;
    }

    /**
     * @return Fractal
     *
     * @throws Throwable
     */
    public function index()
    {
        $this->cartService->refreshCart();

        // if the cart it's empty; we throw an exception
        throw_if(
            empty(
            $this->cartService->getCart()
                ->getItems()
            ),
            new NotFoundException('The cart it\'s empty')
        );

        $cartArray = $this->cartService->toArray();

        return ResponseService::cart($cartArray);
    }

    /**
     * Submit an order
     *
     * @param OrderFormSubmitRequest $request
     *
     * @return JsonResponse|Fractal
     *
     * @throws Throwable
     */
    public function submitOrder(OrderFormSubmitRequest $request)
    {
        // ================================================================
        // ↓↓↓↓↓ FOR DEVELOPMENT ONLY, DEFINITELY DON'T MERGE THIS ↓↓↓↓↓↓
        // ↓↓↓ ALSO YOU CAN DISCARD THIS BRANCH ANYTIME AFTER FEB 2023 ↓↓↓
        // ================================================================
        $header = 'Something went wrong';
        $message = 'It looks like you’ve started a trial with Musora in the last 90 days. Unfortunately, that means ' .
            'that your account is not eligible to start another trial at this time. Click below to check out a ' .
            'special offer and start your membership today!';
        $buttonText = 'YOUR OFFER';
        $brand = $request->get('brand');
        $url = 'https://dev.' . $brand . '.com:8443/lp';
        return response()->json(
            [
                'modal-show-redirect-with-message' => true,
                'modal-header' => $header,
                'modal-message' => $message,
                'modal-button-text' => $buttonText,
                'modal-button-url' => $url
            ],
            422
        );
        // ================================================================
        // ↑↑↑↑↑ FOR DEVELOPMENT ONLY, DEFINITELY DON'T MERGE THIS ↑↑↑↑↑↑
        // ↑↑↑ ALSO YOU CAN DISCARD THIS BRANCH ANYTIME AFTER FEB 2023 ↑↑↑
        // ================================================================

        $this->cartService->refreshCart();

        // if the cart it's empty; we throw an exception
        throw_if(
            empty(
            $this->cartService->getCart()
                ->getItems()
            ),
            new NotFoundException('The cart is empty')
        );

        $result = $this->orderFormService->processOrderFormSubmit($request);

        if (isset($result['order'])) {
            if (!empty($result['order']->getCustomer())) {
                return ResponseService::order($result['order'])
                    ->addMeta(['redirect' => config('ecommerce.post_purchase_redirect_customer_order')]);
            } else {
                return ResponseService::order($result['order'])
                    ->addMeta(['redirect' => config('ecommerce.order_form_post_purchase_redirect_path_without_brand') . $result['order']->getBrand()]);
            }
        }
        elseif (isset($result['errors'])) {
            $errors = [];
            foreach ($result['errors'] as $message) {

                $errors[] = [
                    'title' => 'Payment failed.',
                    'detail' => $message,
                ];
            }

            return response()->json(
                [
                    'errors' => $errors,
                ],
                404
            );
        }
        elseif ($result['redirect'] && !isset($result['errors'])) {

            return ResponseService::redirect($result['redirect']);
        }
    }
}
