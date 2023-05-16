<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\RedirectNeededException;
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
    ) {
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
                /** @var Order $order */
                $order = $result['order'];

                $url = $this->getOrderResponseUrl($order);
                return ResponseService::order($result['order'])->addMeta(['redirect' => $url]);
            }
        } elseif ((isset($result['redirect-with-message']) && $result['redirect-with-message'])) {
            /** @var $redirectNeededException RedirectNeededException */
            $redirectNeededException = $result['redirect-needed-exception'];

            return response()->json(
                [
                    'modal-show-redirect-with-message' => true,
                    'modal-header' => $redirectNeededException->getMessageTitleText(),
                    'modal-message' => $redirectNeededException->getRedirectMessageToUser(),
                    'modal-button-text' => $redirectNeededException->getButtonText(),
                    'modal-button-url' => $redirectNeededException->getUrlRedirect()
                ],
                422
            );
        } elseif (isset($result['errors'])) {
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
        } elseif ($result['redirect'] && !isset($result['errors'])) {
            return ResponseService::redirect($result['redirect']);
        }
    }

    /**
     * @param Order $order
     * @return string
     */
    public function getOrderResponseUrl(Order $order): string
    {
        $productIds = $order->getOrderItems()->map(function ($orderItem) {
            /** @var OrderItem $orderItem */
            return $orderItem->getProduct()?->getId() ?? 0;
        })->toArray();

        $url = config('ecommerce.order_form_post_purchase_redirect_path_without_brand') . $order->getBrand() . '?' . http_build_query(['products' => implode(',', $productIds)]);
        return $url;
    }
}
