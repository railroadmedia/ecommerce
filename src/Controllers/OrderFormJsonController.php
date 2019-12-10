<?php

namespace Railroad\Ecommerce\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class OrderFormJsonController extends Controller
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var PaymentPlanService
     */
    private $paymentPlanService;

    /**
     * @var OrderFormService
     */
    private $orderFormService;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * OrderFormJsonController constructor.
     *
     * @param CartAddressService $cartAddressService
     * @param CartService $cartService
     * @param CurrencyService $currencyService
     * @param OrderFormService $orderFormService
     * @param PaymentPlanService $paymentPlanService
     * @param PermissionService $permissionService
     */
    public function __construct(
        CartAddressService $cartAddressService,
        CartService $cartService,
        CurrencyService $currencyService,
        OrderFormService $orderFormService,
        PaymentPlanService $paymentPlanService,
        PermissionService $permissionService
    )
    {
        $this->cartAddressService = $cartAddressService;
        $this->cartService = $cartService;
        $this->currencyService = $currencyService;
        $this->orderFormService = $orderFormService;
        $this->paymentPlanService = $paymentPlanService;
        $this->permissionService = $permissionService;
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
     * @return Fractal
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
                return ResponseService::order($result['order'])
                    ->addMeta(['redirect' => config('ecommerce.post_purchase_redirect_digital_items')]);
            }
        }
        elseif (isset($result['errors'])) {
            $errors = [];
            foreach ($result['errors'] as $message) {
                $errors = [
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
