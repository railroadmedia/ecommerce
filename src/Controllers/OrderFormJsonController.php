<?php

namespace Railroad\Ecommerce\Controllers;

use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Permissions\Services\PermissionService;
use Railroad\Resora\Entities\Entity;

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
     * @var \Railroad\Ecommerce\Repositories\ShippingOptionRepository
     */
    private $shippingOptionsRepository;

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
     * @param ShippingOptionRepository $shippingOptionRepository
     * @param PermissionService $permissionService
     */
    public function __construct(
        CartAddressService $cartAddressService,
        CartService $cartService,
        CurrencyService $currencyService,
        OrderFormService $orderFormService,
        PaymentPlanService $paymentPlanService,
        ShippingOptionRepository $shippingOptionRepository,
        PermissionService $permissionService
    ) {
        parent::__construct();

        $this->cartAddressService = $cartAddressService;
        $this->cartService = $cartService;
        $this->currencyService = $currencyService;
        $this->orderFormService = $orderFormService;
        $this->paymentPlanService = $paymentPlanService;
        $this->shippingOptionsRepository = $shippingOptionRepository;
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $this->cartService
            ->setBrand(ConfigService::$brand);

        //if the cart it's empty; we throw an exception
        throw_if(
            empty(
            $this->cartService->getCart()
                ->getItems()
            ),
            new NotFoundException('The cart it\'s empty')
        );

        $billingAddress = $this->cartAddressService->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);
        $shippingAddress = $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        return [
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'paymentPlanOptions' => $this->paymentPlanService->getPaymentPlanPricingForCartItems(),
            'cartItems' => $this->cartService->getCart()
                ->getItems(),
            'totalDue' => $this->cartService->getCart()
                ->getTotalDue(),
        ];
    }

    /** Submit an order
     *
     * @param $request
     * @return JsonResponse
     */
    public function submitOrder(OrderFormSubmitRequest $request)
    {
        if ($this->permissionService->can(auth()->id(), 'place-orders-for-other-users')) {
            $brand = $request->get('brand', ConfigService::$brand);
        }

        $this->cartService
            ->setBrand($brand?? ConfigService::$brand);

        // if the cart it's empty; we throw an exception
        throw_if(
            empty(
            $this->cartService->getCart()
                ->getItems()
            ),
            new NotFoundException('The cart it\'s empty')
        );

        $result = $this->orderFormService->processOrderForm($request);

        if (isset($result['order'])) {
            return reply()->json(
                $result['order'],
                [
                    'code' => 200,
                ]
            );
        } else {
            $data = $options = [];

            if (isset($result['errors'])) {
                $options['errors'] = $result['errors'];
                $options['code'] = 400;
            }

            if ($result['redirect'] && !isset($result['errors'])) {
                $data = new Entity(['redirect' => $result['redirect']]);
            }

            return reply()->json($data, $options);
        }
    }
}
