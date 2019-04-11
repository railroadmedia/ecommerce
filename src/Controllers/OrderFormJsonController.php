<?php

namespace Railroad\Ecommerce\Controllers;

use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\ResponseService;
use Railroad\Permissions\Services\PermissionService;
use Spatie\Fractal\Fractal;
use Throwable;

class OrderFormJsonController extends BaseController
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var \Railroad\Ecommerce\Services\CartAddressService
     */
    private $cartAddressService;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var \Railroad\Ecommerce\Services\PaymentPlanService
     */
    private $paymentPlanService;

    /**
     * @var \Railroad\Ecommerce\Services\OrderFormService
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
    ) {
        parent::__construct();

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
        $this->cartService
            ->setBrand(ConfigService::$brand);

        // if the cart it's empty; we throw an exception
        throw_if(
            empty(
            $this->cartService->getCart()
                ->getItems()
            ),
            new NotFoundException('The cart it\'s empty')
        );

        $billingAddress = $this->cartAddressService
            ->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);

        $shippingAddress = $this->cartAddressService
            ->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        $cartItems = $this->cartService->getCart()->getItems();
        $totalDue = $this->cartService->getCart()->getTotalDue();
        $paymentPlansPricing = $this->paymentPlanService->getPaymentPlanPricingForCartItems();

        return ResponseService::orderForm(
            $cartItems,
            $billingAddress,
            $shippingAddress,
            $paymentPlansPricing,
            $totalDue
        );
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
        if (
            $this->permissionService->can(
                auth()->id(),
                'place-orders-for-other-users'
            )
        ) {
            $brand = $request->get('brand', ConfigService::$brand);
        }

        $this->cartService->refreshCart();

        // if the cart it's empty; we throw an exception
        throw_if(
            empty(
                $this->cartService->getCart()->getItems()
            ),
            new NotFoundException('The cart is empty')
        );

        $result = $this->orderFormService->processOrderFormSubmit($request);

        if (isset($result['order'])) {
            return ResponseService::order($result['order']);
        } elseif (isset($result['errors'])) {
            $errors = [];
            foreach ($result['errors'] as $message) {
                $errors = [
                    'title' => 'Payment failed.',
                    'detail' => $message,
                ];
            }
            response()->json(
                [
                    'errors' => $errors,
                ],
                404
            );
        } elseif ($result['redirect'] && !isset($result['errors'])) {

            return ResponseService::redirect($result['redirect']);
        }
    }
}
