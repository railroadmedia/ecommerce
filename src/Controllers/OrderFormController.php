<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\UnprocessableEntityException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Repositories\OrderDiscountRepository;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Ecommerce\Responses\JsonResponse;
use Railroad\Ecommerce\Services\CartAddressService;
use Railroad\Ecommerce\Services\CartService;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Ecommerce\Services\CurrencyService;
use Railroad\Ecommerce\Services\DiscountService;
use Railroad\Ecommerce\Services\PaymentMethodService;
use Railroad\Ecommerce\Services\SubscriptionService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Location\Services\LocationService;

class OrderFormController extends Controller
{
    /**
     * @var CartService
     */
    private $cartService;

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
     * @var \Railroad\Ecommerce\Repositories\PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderPaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\ProductRepository
     */
    private $productRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository
     */
    private $subscriptionPaymentRepository;

    /**
     * @var \Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository
     */
    private $orderItemFulfillmentRepository;

    /**
     * @var  OrderDiscountRepository
     */
    private $orderDiscountRepository;

    /**
     * @var \Railroad\Ecommerce\Services\DiscountService
     */
    private $discountService;

    /**
     * OrderFormController constructor.
     *
     * @param \Railroad\Ecommerce\Services\CartService                        $cartService
     * @param \Railroad\Ecommerce\Repositories\OrderRepository                $orderRepository
     * @param \Railroad\Ecommerce\Repositories\CustomerRepository             $customerRepository
     * @param \Railroad\Ecommerce\Services\CartAddressService                 $cartAddressService
     * @param \Railroad\Ecommerce\Repositories\AddressRepository              $addressRepository
     * @param \Railroad\Ecommerce\Repositories\ShippingOptionRepository       $shippingOptionRepository
     * @param \Railroad\Ecommerce\Services\TaxService                         $taxService
     * @param \Railroad\Ecommerce\Repositories\PaymentMethodRepository        $paymentMethodRepository
     * @param \Railroad\Ecommerce\Services\PaymentMethodService               $paymentMethodService
     * @param \Railroad\Location\Services\LocationService                     $locationService
     * @param \Railroad\Ecommerce\Services\CurrencyService                    $currencyService
     * @param \Railroad\Ecommerce\Gateways\StripePaymentGateway               $stripePaymentGateway
     * @param \Railroad\Ecommerce\Gateways\PayPalPaymentGateway               $payPalPaymentGateway
     * @param \Railroad\Ecommerce\Repositories\PaymentRepository              $paymentRepository
     * @param \Railroad\Ecommerce\Repositories\OrderPaymentRepository         $orderPaymentRepository
     * @param \Railroad\Ecommerce\Repositories\ProductRepository              $productRepository
     * @param \Railroad\Ecommerce\Repositories\OrderItemRepository            $orderItemRepository
     * @param \Railroad\Ecommerce\Repositories\SubscriptionRepository         $subscriptionRepository
     * @param \Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository  $subscriptionPaymentRepository
     * @param \Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository $orderItemFulfillmentRepository
     */
    public function __construct(
        CartService $cartService,
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
        PayPalPaymentGateway $payPalPaymentGateway,
        PaymentRepository $paymentRepository,
        OrderPaymentRepository $orderPaymentRepository,
        ProductRepository $productRepository,
        OrderItemRepository $orderItemRepository,
        SubscriptionRepository $subscriptionRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        OrderItemFulfillmentRepository $orderItemFulfillmentRepository,
        OrderDiscountRepository $orderDiscountRepository,
        DiscountService $discountService
    ) {
        $this->cartService                    = $cartService;
        $this->orderRepository                = $orderRepository;
        $this->customerRepository             = $customerRepository;
        $this->cartAddressService             = $cartAddressService;
        $this->addressRepository              = $addressRepository;
        $this->shippingOptionsRepository      = $shippingOptionRepository;
        $this->taxService                     = $taxService;
        $this->paymentMethodRepository        = $paymentMethodRepository;
        $this->paymentMethodService           = $paymentMethodService;
        $this->locationService                = $locationService;
        $this->currencyService                = $currencyService;
        $this->stripePaymentGateway           = $stripePaymentGateway;
        $this->payPalPaymentGateway           = $payPalPaymentGateway;
        $this->paymentRepository              = $paymentRepository;
        $this->orderPaymentRepository         = $orderPaymentRepository;
        $this->productRepository              = $productRepository;
        $this->orderItemRepository            = $orderItemRepository;
        $this->subscriptionRepository         = $subscriptionRepository;
        $this->subscriptionPaymentRepository  = $subscriptionPaymentRepository;
        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
        $this->orderDiscountRepository        = $orderDiscountRepository;
        $this->discountService                = $discountService;
    }

    /** Submit an order
     *
     * @param $request
     * @return JsonResponse
     */
    public function submitOrder(OrderFormSubmitRequest $request)
    {
        $cartItems = $this->cartService->getAllCartItems();

        $user     = auth()->user() ?? null;
        $currency = $request->get('currency', $this->currencyService->get());

        //if the cart it's empty; we throw an exception
        throw_if(
            empty($cartItems),
            new NotFoundException('The cart it\'s empty')
        );

        if(!empty($request->get('validated-express-checkout-token')))
        {
            $orderFormInput = session()->get('order-form-input', []);
            unset($orderFormInput['validated-express-checkout-token']);
            session()->forget('order-form-input');
            $request->merge($orderFormInput);
        }

        //set the shipping address on session
        $shippingAddress = $this->cartAddressService->setAddress(
            [
                'firstName'       => $request->get('shipping-first-name'),
                'lastName'        => $request->get('shipping-last-name'),
                'streetLineOne'   => $request->get('shipping-address-line-1'),
                'streetLineTwo'   => $request->get('shipping-address-line-2'),
                'zipOrPostalCode' => $request->get('shipping-zip-or-postal-code'),
                'city'            => $request->get('shipping-city'),
                'region'          => $request->get('shipping-region'),
                'country'         => $request->get('shipping-country'),
            ],
            ConfigService::$shippingAddressType
        );

        // calculate totals
        $cartItemsWeight = array_sum(array_column($cartItems, 'weight'));

        $shippingCosts = $this->shippingOptionsRepository->getShippingCosts(
                $request->get('shipping-country'),
                $cartItemsWeight
            )['price'] ?? 0;

        //set the billing address on session
        $billingAddress = $this->cartAddressService->setAddress(
            [
                'country' => $request->get('billing-country'),
                'region'  => $request->get('billing-region'),
                'zip'     => $request->get('billing-zip-or-postal-code'),
            ],
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $cartItemsWithTaxesAndCosts =
            $this->taxService->calculateTaxesForCartItems(
                $cartItems,
                $billingAddress['country'],
                $billingAddress['region'],
                $shippingCosts,
                $currency
            );

        //save billing address in database
        $billingAddressDB = $this->addressRepository->create(
            [
                'type'        => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand'       => ConfigService::$brand,
                'user_id'     => $user['id'] ?? null,
                'customer_id' => $customer['id'] ?? null,
                'zip'         => $request->get('billing-zip-or-postal-code'),
                'state'       => $request->get('billing-region'),
                'country'     => $request->get('billing-country'),
                'created_on'  => Carbon::now()->toDateTimeString(),
            ]
        );

        // try to make the payment
        try
        {
            if(($request->get('payment_method_type') == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) &&
                empty($request->get('validated-express-checkout-token')))
            {
                $customer = $this->stripePaymentGateway->getOrCreateCustomer(
                    $request->get('gateway'),
                    $user['email'] ?? $request->get('email')
                );

                $card = $this->stripePaymentGateway->createCustomerCard(
                    $request->get('gateway'),
                    $customer,
                    $request->get('card-token')
                );

                $charge =
                    $this->stripePaymentGateway->chargeCustomerCard(
                        $request->get('gateway'),
                        $cartItemsWithTaxesAndCosts['totalDue'],
                        $currency,
                        $card,
                        $customer
                    );

                $paymentMethodId = $this->paymentMethodService->createUserCreditCard(
                    $user['id'],
                    $card->fingerprint,
                    $card->last4,
                    '',
                    $card->brand,
                    $card->exp_year,
                    $card->exp_month,
                    $card->id,
                    $card->customer,
                    $request->get('gateway'),
                    $billingAddressDB['id'],
                    $currency,
                    false
                );
            }
            elseif(($request->get('payment_method_type') == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) ||
                !empty($request->get('validated-express-checkout-token')))
            {
                if(empty($request->get('validated-express-checkout-token')))
                {
                    $url = $this->payPalPaymentGateway->getBillingAgreementExpressCheckoutUrl(
                        $request->get('gateway'),
                        ConfigService::$paymentGateways['paypal'][$request->get('gateway')]
                    );
                    session()->put('order-form-input', $request->all());

                    return redirect()->away($url);
                }
                $billingAgreementId =
                    $this->payPalPaymentGateway->createBillingAgreement(
                        $request->get('gateway'),
                        $cartItemsWithTaxesAndCosts['totalDue'],
                        $currency,
                        $request->get('validated-express-checkout-token')
                    );
                $transactionId      =
                    $this->payPalPaymentGateway->chargeBillingAgreement(
                        $request->get('gateway'),
                        $cartItemsWithTaxesAndCosts['totalDue'],
                        $currency,
                        $billingAgreementId
                    );
                $paymentMethodId    = $this->paymentMethodService->createPayPalBillingAgreement(
                    $user['id'],
                    $billingAgreementId,
                    $billingAddressDB['id'],
                    $request->get('gateway'),
                    $currency,
                    false
                );
            }
            else
            {
                return redirect()->to(strtok(app('url')->previous(), '?'))->withErrors(
                    ['payment' => 'Payment method not supported.']
                );
            }
        }
        catch(PaymentFailedException $paymentFailedException)
        {
            return redirect()->to(strtok(app('url')->previous(), '?'))->withErrors(
                ['payment' => $paymentFailedException->getMessage()]
            );
        }

        //save customer if billing email exists on request
        if($request->has('billing-email'))
        {
            $customer = $this->customerRepository->create(
                [
                    'email' => $request->get('billing-email'),
                    'brand' => ConfigService::$brand,
                ]
            );
        }

        //save the shipping address
        $shippingAddressDB = $this->addressRepository->create(
            [
                'type'          => ConfigService::$shippingAddressType,
                'brand'         => ConfigService::$brand,
                'user_id'       => $user['id'] ?? null,
                'customer_id'   => $customer['id'] ?? null,
                'first_name'    => $request->get('shipping-first-name'),
                'last_name'     => $request->get('shipping-last-name'),
                'street_line_1' => $request->get('shipping-address-line-1'),
                'street_line_2' => $request->get('shipping-address-line-2'),
                'city'          => $request->get('shipping-city'),
                'zip'           => $request->get('shipping-zip-or-postal-code'),
                'state'         => $request->get('shipping-region'),
                'country'       => $request->get('shipping-country'),
                'created_on'    => Carbon::now()->toDateTimeString(),
            ]
        );

        // payment
        $payment = $this->paymentRepository->create(
            [
                'due'               => $cartItemsWithTaxesAndCosts['totalDue'],
                'paid'              => $cartItemsWithTaxesAndCosts['totalDue'],
                'refunded'          => 0,
                'type'              => 'order',
                'external_id'       => $charge['id'] ?? $transactionId,
                'external_provider' => isset($charge['id']) ? 'stripe' : 'paypal',
                'status'            => 'paid',
                'message'           => '',
                'payment_method_id' => $paymentMethodId,
                'currency'          => $currency,
                'created_on'        => Carbon::now()->toDateTimeString(),
            ]
        );

        //create order
        $order = $this->orderRepository->create(
            [
                'due'                 => $cartItemsWithTaxesAndCosts['totalDue'],
                'tax'                 => $cartItemsWithTaxesAndCosts['totalTax'],
                'paid'                => $cartItemsWithTaxesAndCosts['totalDue'],
                'brand'               => $request->input('brand', ConfigService::$brand),
                'user_id'             => $user['id'] ?? null,
                'customer_id'         => $customer['id'] ?? null,
                'shipping_costs'      => $cartItemsWithTaxesAndCosts['shippingCosts'],
                'shipping_address_id' => $shippingAddressDB['id'],
                'billing_address_id'  => $billingAddressDB['id'],
                'created_on'          => Carbon::now()->toDateTimeString(),
            ]
        );

        // attach order to payment
        $this->orderPaymentRepository->create(
            [
                'order_id'   => $order['id'],
                'payment_id' => $payment['id'],
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );
        $amountDiscounted = 0;

        if(array_key_exists('applyDiscount', $cartItemsWithTaxesAndCosts['cartItems']))
        {
            //save order discount
            $orderDiscount    = $this->orderDiscountRepository->create([
                'order_id'    => $order['id'],
                'discount_id' => $cartItemsWithTaxesAndCosts['cartItems']['applyDiscount']['discount_id'],
                'created_on'  => Carbon::now()->toDateTimeString()
            ]);
            $amountDiscounted = array_sum(array_column($cartItems, 'totalPrice')) + $cartItemsWithTaxesAndCosts['shippingCosts'] - $cartItemsWithTaxesAndCosts['totalDue'];
        }

        // order items
        foreach($cartItems as $key => $cartItem)
        {
            $product = $this->productRepository->read($cartItem['options']['product-id']);
            if(!$product['active'])
            {
                continue;
            }
            $orderItem = $this->orderItemRepository->create(
                [
                    'order_id'       => $order['id'],
                    'product_id'     => $product['id'],
                    'quantity'       => $cartItem['quantity'],
                    'initial_price'  => $cartItem['price'],
                    'discount'       => $amountDiscounted,
                    'tax'            => $cartItemsWithTaxesAndCosts['totalTax'],
                    'shipping_costs' => $cartItemsWithTaxesAndCosts['shippingCosts'],
                    'total_price'    => $cartItem['totalPrice'] + $cartItemsWithTaxesAndCosts['shippingCosts'] - $amountDiscounted,
                    'created_on'     => Carbon::now()->toDateTimeString(),
                ]
            );

            if(array_key_exists('applyDiscount', $cartItemsWithTaxesAndCosts['cartItems'][$key]))
            {
                //save order item discount
                $orderDiscount = $this->orderDiscountRepository->create([
                    'order_id'      => $order['id'],
                    'order_item_id' => $orderItem['id'],
                    'discount_id'   => $cartItemsWithTaxesAndCosts['cartItems'][$key]['applyDiscount']['discount_id'],
                    'created_on'    => Carbon::now()->toDateTimeString()
                ]);

                $itemAmountDiscounted = $this->discountService->getAmountDiscounted([$cartItemsWithTaxesAndCosts['cartItems'][$key]['applyDiscount']], $cartItemsWithTaxesAndCosts['totalDue'], $cartItems);
                $this->orderItemRepository->update($orderItem['id'], [
                    'discount'    => $itemAmountDiscounted,
                    'total_price' => $orderItem['total_price'] - $itemAmountDiscounted
                ]);
            }

            if(!empty($product['subscription_interval_type']))
            {
                if($product['subscription_interval_type'] == SubscriptionService::INTERVAL_TYPE_MONTHLY)
                {
                    // dd($product['subscription_interval_type']);
                    $nextBillDate = Carbon::now()->addMonths($product['subscription_interval_count']);
                }
                elseif($product['subscription_interval_type'] == SubscriptionService::INTERVAL_TYPE_YEARLY)
                {
                    $nextBillDate = Carbon::now()->addYears($product['subscription_interval_count']);
                }
                else
                {
                    throw new UnprocessableEntityException('Failed to create subscription for order id: ' . $order['id']);
                }

                if(array_key_exists('applyDiscount', $cartItemsWithTaxesAndCosts['cartItems'][$key]))
                {
                    if($cartItemsWithTaxesAndCosts['cartItems'][$key]['applyDiscount']['discount_type'] == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE)
                    {
                        //add the days from the discount to the subscription next bill date
                        $nextBillDate = $nextBillDate->addDays($cartItemsWithTaxesAndCosts['cartItems'][$key]['applyDiscount']['amount']);
                    }
                    elseif($cartItemsWithTaxesAndCosts['cartItems'][$key]['applyDiscount']['discount_type'] == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE)
                    {
                        //calculate subscription price per payment after discount
                        $subscriptionPricePerPayment = $cartItem['price'] - $cartItemsWithTaxesAndCosts['cartItems'][$key]['applyDiscount']['amount'];
                    }
                }

                $subscription = $this->subscriptionRepository->create(
                    [
                        'brand'                   => $request->get('brand', ConfigService::$brand),
                        'type'                    => 'subscription',
                        'user_id'                 => $user['id'],
                        'order_id'                => $order['id'],
                        'product_id'              => $product['id'],
                        'is_active'               => true,
                        'start_date'              => Carbon::now()->toDateTimeString(),
                        'paid_until'              => $nextBillDate->toDateTimeString(),
                        'total_price_per_payment' => $subscriptionPricePerPayment ?? $cartItem['price'],
                        'tax_per_payment'         => $cartItemsWithTaxesAndCosts['totalTax'],
                        'shipping_per_payment'    => 0,
                        'currency'                => $currency,
                        'interval_type'           => $product['subscription_interval_type'],
                        'interval_count'          => $product['subscription_interval_count'],
                        'total_cycles_paid'       => 1,
                        'payment_method_id'       => $paymentMethodId,
                        'created_on'              => Carbon::now()->toDateTimeString(),
                    ]
                );
                // attach subscription to payment
                $this->subscriptionPaymentRepository->create(
                    [
                        'subscription_id' => $subscription['id'],
                        'payment_id'      => $payment['id'],
                        'created_on'      => Carbon::now()->toDateTimeString(),
                    ]
                );
            }
            //product fulfillment
            if($product['is_physical'])
            {
                $this->orderItemFulfillmentRepository->create([
                    'order_id'      => $orderItem['order_id'],
                    'order_item_id' => $orderItem['id'],
                    'status'        => 'pending',
                    'created_on'    => Carbon::now()->toDateTimeString()
                ]);
            }
        }

        //if the order failed; we throw the proper exception
        throw_if(
            !($order),
            new UnprocessableEntityException('Order failed. Error message: ')
        );
        event(new GiveContentAccess($order));

        return new JsonResponse($order, 200);
    }
}