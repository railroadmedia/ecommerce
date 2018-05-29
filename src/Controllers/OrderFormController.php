<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\UnprocessableEntityException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\OrderFormService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Location\Services\LocationService;

class OrderFormController extends Controller
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
     * @var \Railroad\Ecommerce\Repositories\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\CustomerRepository
     */
    private $customerRepository;

    /**
     * @var \Railroad\Ecommerce\Services\CartAddressService
     */
    private $cartAddressService;

    /**
     * @var \Railroad\Ecommerce\Repositories\AddressRepository
     */
    private $addressRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ShippingOptionRepository
     */
    private $shippingOptionsRepository;

    /**
     * @var \Railroad\Ecommerce\Services\TaxService
     */
    private $taxService;

    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var \Railroad\Ecommerce\Services\PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var \Railroad\Location\Services\LocationService
     */
    private $locationService;

    /**
     * @var CurrencyService
     */
    private $currencyService;
    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;
    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * OrderFormJsonController constructor.
     *
     * @param $cartService
     */
    public function __construct(
        CartService $cartService,
        OrderFormService $orderFormService,
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        CartAddressService $cartAddressService,
        AddressRepository $addressRepository,
        ShippingOptionRepository $shippingOptionRepository,
        TaxService $taxService,
        PaymentMethodRepository $paymentMethodRepository,
        PaymentMethodService $paymentMethodService,
        LocationService $locationService,
        CurrencyService $currencyService,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway
    ) {
        $this->cartService = $cartService;
        $this->orderFormService = $orderFormService;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->cartAddressService = $cartAddressService;
        $this->addressRepository = $addressRepository;
        $this->shippingOptionsRepository = $shippingOptionRepository;
        $this->taxService = $taxService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentMethodService = $paymentMethodService;
        $this->locationService = $locationService;
        $this->currencyService = $currencyService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
    }

    /** Submit an order
     *
     * @param $request
     * @return JsonResponse
     */
    public function submitOrder(OrderFormSubmitRequest $request)
    {
        $cartItems = $this->cartService->getAllCartItems();

        $userId = auth()->id() ?? null;

        //if the cart it's empty; we throw an exception
        throw_if(
            empty($cartItems),
            new NotFoundException('The cart it\'s empty')
        );

        // calculate totals
        $cartItemsWeight = array_sum(array_column($cartItems, 'weight'));

        $shippingCosts = $this->shippingOptionsRepository->getShippingCosts(
                $request->get('shipping-country'),
                $cartItemsWeight
            )['price'] ?? 0;

        $cartItemsWithTaxesAndCosts =
            $this->taxService->calculateTaxesForCartItems(
                $cartItems,
                $request->get('billing-country'),
                $request->get('billing-region'),
                $shippingCosts
            );

        // try to make the payment
        try {

            if ($request->get('payment_method_type') == 'Credit Card') {
                $chargeId =
                    $this->stripePaymentGateway->chargeToken(
                        $request->get('gateway-name'),
                        $cartItemsWithTaxesAndCosts['totalDue'],
                        $request->get('currency', $this->currencyService->get()),
                        $request->get('card-token')
                    );
            } elseif ($request->get('payment_method_type') == 'PayPal') {
                $chargeId =
                    $this->payPalPaymentGateway->chargeToken(
                        $request->get('gateway-name'),
                        $cartItemsWithTaxesAndCosts['totalDue'],
                        $request->get('currency', $this->currencyService->get()),
                        $request->get('express-checkout-token')
                    );
            } else {
                return redirect()->back()->withErrors(['payment' => 'Payment method not supported.']);
            }

        } catch (PaymentFailedException $paymentFailedException) {
            return redirect()->back()->withErrors(['payment' => $paymentFailedException->getMessage()]);
        }

        //save customer if billing email exists on request
        if ($request->has('billing-email')) {
            $customer = $this->customerRepository->create(
                [
                    'email' => $request->get('billing-email'),
                    'brand' => ConfigService::$brand,
                ]
            );
        }

        //set the billing address on session
        $billingAddress = $this->cartAddressService->setAddress(
            [
                'country' => $request->get('billing-country'),
                'region' => $request->get('billing-region'),
                'zip' => $request->get('billing-zip-or-postal-code'),
            ],
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        //save billing address in database
        $billingAddressDB = $this->addressRepository->create(
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'customer_id' => $customer['id'] ?? null,
                'zip' => $request->get('billing-zip-or-postal-code'),
                'state' => $request->get('billing-region'),
                'country' => $request->get('billing-country'),
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        //set the shipping address on session
        $shippingAddress = $this->cartAddressService->setAddress(
            [
                'firstName' => $request->get('shipping-first-name'),
                'lastName' => $request->get('shipping-last-name'),
                'streetLineOne' => $request->get('shipping-address-line-1'),
                'streetLineTwo' => $request->get('shipping-address-line-2'),
                'zipOrPostalCode' => $request->get('shipping-zip-or-postal-code'),
                'city' => $request->get('shipping-city'),
                'region' => $request->get('shipping-region'),
                'country' => $request->get('shipping-country'),
            ],
            ConfigService::$shippingAddressType
        );

        //save the shipping address
        $shippingAddressDB = $this->addressRepository->create(
            [
                'type' => ConfigService::$shippingAddressType,
                'brand' => ConfigService::$brand,
                'user_id' => $userId,
                'customer_id' => $customer['id'] ?? null,
                'first_name' => $request->get('shipping-first-name'),
                'last_name' => $request->get('shipping-last-name'),
                'street_line_1' => $request->get('shipping-address-line-1'),
                'street_line_2' => $request->get('shipping-address-line-2'),
                'city' => $request->get('shipping-city'),
                'zip' => $request->get('shipping-zip-or-postal-code'),
                'state' => $request->get('shipping-region'),
                'country' => $request->get('shipping-country'),
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        // todo: create the necessary payment method rows and payment rows

//        $method = $this->paymentMethodService->saveMethod(
//            [
//                'method_type' => $request->get('payment-type-selector'),
//                'paymentGateway' => $request->get('gateway'),
//                'company_name' => $request->get('company_name'),
//                'creditCardYear' => $request->get('credit-card-year-selector'),
//                'creditCardMonth' => $request->get('credit-card-month-selector'),
//                'fingerprint' => $request->get('credit-card-number'),
//                'last4' => $request->get('credit-card-cvv'),
//                'cardholder' => $request->get('cardholder_name'),
//                'expressCheckoutToken' => $request->get('express_checkout_token'),
//                'address_id' => $request->get('address_id'),
//                'userId' => $request->get('user_id'),
//                'customerId' => $request->get('customer_id'),
//            ]
//
//        );
//
//        $paymentMethod = $this->paymentMethodRepository->create(
//            [
//                'method_type' => $request->get('payment-type-selector'),
//                'method_id' => $method['id'],
//                'currency' => $request->get('currency') ?? $this->currencyService->get(),
//                'created_on' => Carbon::now()->toDateTimeString(),
//            ]
//        );

        $order = $this->orderRepository->create(
            [
                'due' => $cartItemsWithTaxesAndCosts['totalDue'],
                'tax' => $cartItemsWithTaxesAndCosts['totalTax'],
                'paid' => $cartItemsWithTaxesAndCosts['totalDue'],
                'brand' => $request->input('brand', ConfigService::$brand),
                'user_id' => $userId,
                'customer_id' => $customer['id'] ?? null,
                'shipping_costs' => $shippingCosts,
                'shipping_address_id' => $shippingAddressDB['id'],
                'billing_address_id' => $billingAddressDB['id'],
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        //if the order failed; we throw the proper exception
        throw_if(
            !($order),
            new UnprocessableEntityException('Order failed. Error message: ')
        );

        return new JsonResponse($order, 200);
    }
}