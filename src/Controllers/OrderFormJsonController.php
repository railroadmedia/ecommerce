<?php

namespace Railroad\Ecommerce\Controllers;

use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\TaxService;
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
     * @var \Railroad\Ecommerce\Services\TaxService
     */
    private $taxService;

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
     * OrderFormJsonController constructor.
     *
     * @param \Railroad\Ecommerce\Services\CartAddressService           $cartAddressService
     * @param \Railroad\Ecommerce\Services\CartService                  $cartService
     * @param \Railroad\Ecommerce\Services\CurrencyService              $currencyService
     * @param \Railroad\Ecommerce\Services\OrderFormService             $orderFormService
     * @param \Railroad\Ecommerce\Services\PaymentPlanService           $paymentPlanService
     * @param \Railroad\Ecommerce\Repositories\ShippingOptionRepository $shippingOptionRepository
     * @param \Railroad\Ecommerce\Services\TaxService                   $taxService
     */
    public function __construct(
        CartAddressService $cartAddressService,
        CartService $cartService,
        CurrencyService $currencyService,
        OrderFormService $orderFormService,
        PaymentPlanService $paymentPlanService,
        ShippingOptionRepository $shippingOptionRepository,
        TaxService $taxService
    ) {
        parent::__construct();

        $this->cartAddressService = $cartAddressService;
        $this->cartService = $cartService;
        $this->currencyService = $currencyService;
        $this->orderFormService = $orderFormService;
        $this->paymentPlanService = $paymentPlanService;
        $this->shippingOptionsRepository = $shippingOptionRepository;
        $this->taxService = $taxService;
    }


    public function index()
    {
        $cartItems = $this->cartService->getAllCartItems();

        //if the cart it's empty; we throw an exception
        throw_if(
            empty($cartItems),
            new NotFoundException('The cart it\'s empty')
        );

        $currency = $this->currencyService->get();
        $billingAddress  = $this->cartAddressService->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);
        $shippingAddress = $this->cartAddressService->getAddress(CartAddressService::SHIPPING_ADDRESS_TYPE);

        //calculate shipping costs
        $shippingCosts = $this->shippingOptionsRepository->getShippingCosts(
                $shippingAddress['country'],
                array_sum(array_column($cartItems, 'weight'))
            )['price'] ?? 0;

        $cartItemsWithTaxesAndCosts =
            $this->taxService->calculateTaxesForCartItems(
                $cartItems,
                $billingAddress['country'],
                $billingAddress['region'],
                $shippingCosts,
                $currency,
                $this->cartService->getPromoCode()
            );
        return array_merge(
            [
                'shippingAddress' => $shippingAddress,
                'billingAddress'  => $billingAddress,
                'paymentPlanOptions' => $this->paymentPlanService->getPaymentPlanPricingForCartItems()
            ],
            $cartItemsWithTaxesAndCosts
        );

    }

    /** Submit an order
     *
     * @param $request
     * @return JsonResponse
     */
    public function submitOrder(OrderFormSubmitRequest $request)
    {
        //$cartItems = $this->cartService->getAllCartItems();
        $cartItems = $this->cartService->getCart();

        //if the cart it's empty; we throw an exception
        throw_if(
            empty($cartItems),
            new NotFoundException('The cart it\'s empty')
        );

        $result = $this->orderFormService
            ->processOrderForm($request, $cartItems);

        if (isset($result['order'])) {
            return reply()->json($result['order'], [
                'code' => 200
            ]);
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
