<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\UnprocessableEntityException;
use Railroad\Ecommerce\Exceptions\StripeCardException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Providers\UserProviderInterface;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\CustomerRepository;
use Railroad\Ecommerce\Repositories\OrderDiscountRepository;
use Railroad\Ecommerce\Repositories\OrderItemFulfillmentRepository;
use Railroad\Ecommerce\Repositories\OrderItemRepository;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\OrderRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Repositories\UserProductRepository;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;

class OrderFormService
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
     * @var \Railroad\Ecommerce\Services\PaymentMethodService
     */
    private $paymentMethodService;

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
     * @var \Railroad\Ecommerce\Repositories\PaymentMethodRepository
     */
    private $paymentMethodRepository;

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
     * @var UserProductService
     */
    private $userProductService;

    /**
     * @var \Railroad\Ecommerce\Services\DiscountService
     */
    private $discountService;

    /**
     * @var mixed UserProviderInterface
     */
    private $userProvider;

    /**
     * OrderFormService constructor.
     *
     * @param CartService $cartService
     * @param OrderRepository $orderRepository
     * @param CustomerRepository $customerRepository
     * @param CartAddressService $cartAddressService
     * @param AddressRepository $addressRepository
     * @param ShippingOptionRepository $shippingOptionRepository
     * @param TaxService $taxService
     * @param PaymentMethodService $paymentMethodService
     * @param CurrencyService $currencyService
     * @param StripePaymentGateway $stripePaymentGateway
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PaymentRepository $paymentRepository
     * @param OrderPaymentRepository $orderPaymentRepository
     * @param ProductRepository $productRepository
     * @param OrderItemRepository $orderItemRepository
     * @param SubscriptionRepository $subscriptionRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     * @param OrderItemFulfillmentRepository $orderItemFulfillmentRepository
     * @param OrderDiscountRepository $orderDiscountRepository
     * @param UserProductService $userProductService
     * @param DiscountService $discountService
     */
    public function __construct(
        CartService $cartService,
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        CartAddressService $cartAddressService,
        AddressRepository $addressRepository,
        ShippingOptionRepository $shippingOptionRepository,
        TaxService $taxService,
        PaymentMethodService $paymentMethodService,
        CurrencyService $currencyService,
        StripePaymentGateway $stripePaymentGateway,
        PayPalPaymentGateway $payPalPaymentGateway,
        PaymentRepository $paymentRepository,
        PaymentMethodRepository $paymentMethodRepository,
        OrderPaymentRepository $orderPaymentRepository,
        ProductRepository $productRepository,
        OrderItemRepository $orderItemRepository,
        SubscriptionRepository $subscriptionRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        OrderItemFulfillmentRepository $orderItemFulfillmentRepository,
        OrderDiscountRepository $orderDiscountRepository,
        UserProductService $userProductService,
        DiscountService $discountService
    ) {
        $this->cartService = $cartService;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->cartAddressService = $cartAddressService;
        $this->addressRepository = $addressRepository;
        $this->shippingOptionsRepository = $shippingOptionRepository;
        $this->taxService = $taxService;
        $this->paymentMethodService = $paymentMethodService;
        $this->currencyService = $currencyService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->paymentRepository = $paymentRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->productRepository = $productRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->orderItemFulfillmentRepository = $orderItemFulfillmentRepository;
        $this->orderDiscountRepository = $orderDiscountRepository;
        $this->userProductService = $userProductService;
        $this->discountService = $discountService;
        $this->userProvider = app()->make(UserProviderInterface::class);
    }

    /**
     * Submit an order
     *
     * @param Request $request
     * @param array $cartItems
     * @return array
     */
    public function processOrderForm(
        Request $request,
        $cartItems
    ) {
        $userId = auth()->id() ?? null;

        if (!empty($request->get('token'))) {
            $orderFormInput = session()->get('order-form-input', []);
            unset($orderFormInput['token']);
            session()->forget('order-form-input');
            $request->merge($orderFormInput);
        }

        $currency = $request->get('currency', $this->currencyService->get());

        if (!empty($request->get('account-creation-email')) && empty($user)) {
            $userId = $this->userProvider->create(
                $request->get('account-creation-email'),
                $request->get('account-creation-password'),
                $request->get('account-creation-email')
            );
        }

        //save customer if billing email exists on request
        if ($request->has('billing-email')) {
            $customer = $this->customerRepository->create(
                [
                    'email' => $request->get('billing-email'),
                    'brand' => ConfigService::$brand,
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );
        }

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

        //calculate shipping costs
        $shippingCosts = $this->shippingOptionsRepository->getShippingCosts(
                $request->get('shipping-country'),
                array_sum(array_column($cartItems, 'weight'))
            )['price'] ?? 0;

        //set the billing address on session
        $billingAddress = $this->cartAddressService->setAddress(
            [
                'country' => $request->get('billing-country'),
                'region' => $request->get('billing-region'),
                'zip' => $request->get('billing-zip-or-postal-code'),
            ],
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $this->cartService->setPaymentPlanNumberOfPayments(
            $request->get('payment-plan-selector')
        );

        $cartItemsWithTaxesAndCosts = $this->taxService->calculateTaxesForCartItems(
            $cartItems,
            $billingAddress['country'],
            $billingAddress['region'],
            $shippingCosts,
            $currency,
            $this->cartService->getPromoCode()
        );

        $billingAddressDB = null;

        // try to make the payment
        try {
            if ($request->get('payment-method-id')) {

                $paymentMethod = $this->paymentMethodRepository->read($request->get('payment-method-id'));

                if (!$paymentMethod ||
                    !$paymentMethod['user']['user_id'] ||
                    $paymentMethod['user']['user_id'] != $userId) {
                    $url = $request->get('redirect') ?? strtok(app('url')->previous(), '?');

                    return [
                        'redirect' => $url,
                        'errors' => [
                            'payment' => 'Invalid Payment Method',
                        ],
                    ];
                }

                $charge = $transactionId = null;

                if ($paymentMethod['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
                    $charge = $this->rechargeCreditCard(
                        $request,
                        $paymentMethod,
                        $cartItemsWithTaxesAndCosts,
                        $currency
                    );
                } else {
                    $transactionId = $this->rechargeAgreement(
                        $request,
                        $paymentMethod,
                        $cartItemsWithTaxesAndCosts,
                        $currency
                    );
                }

                if (!$charge && !$transactionId) {

                    $url = $request->get('redirect') ?? strtok(app('url')->previous(), '?');

                    return [
                        'redirect' => $url,
                        'errors' => [
                            'payment' => 'Could not recharge existing payment method',
                        ],
                    ];
                }

                $paymentMethodId = $paymentMethod['id'];
                $billingAddressDB = $paymentMethod['billing_address'];

            } else {
                if ($request->get('payment_method_type') == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE &&
                    empty($request->get('token'))) {

                    list(
                        $charge, $paymentMethodId, $billingAddressDB
                        ) = $this->chargeAndCreatePaymentMethod(
                        $request,
                        $userId,
                        $customer ?? null,
                        $cartItemsWithTaxesAndCosts,
                        $currency
                    );

                } elseif ($request->get('payment_method_type') == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE ||
                    !empty($request->get('token'))) {
                    if (empty($request->get('token'))) {

                        $gateway = $request->get('gateway');
                        $config = ConfigService::$paymentGateways['paypal'];
                        $url = $config[$gateway]['paypal_api_checkout_return_url'];

                        $checkoutUrl = $this->payPalPaymentGateway->getBillingAgreementExpressCheckoutUrl(
                            $gateway,
                            $url
                        );

                        session()->put('order-form-input', $request->all());

                        return ['redirect' => $checkoutUrl];
                    }

                    list (
                        $transactionId, $paymentMethodId, $billingAddressDB
                        ) = $this->transactionAndCreatePaymentMethod(
                        $request,
                        $cartItemsWithTaxesAndCosts,
                        $currency,
                        $user
                    );

                } else {
                    $url = $request->get('redirect') ?? strtok(app('url')->previous(), '?');

                    return [
                        'redirect' => $url,
                        'errors' => [
                            'payment' => 'Payment method not supported.',
                        ],
                    ];
                }
            }
        } catch (PaymentFailedException $paymentFailedException) {

            $url = $request->get('redirect') ?? strtok(app('url')->previous(), '?');

            return [
                'redirect' => $url,
                'errors' => [
                    'payment' => $paymentFailedException->getMessage(),
                ],
            ];
        } catch (\Stripe\Error\Card $exception) {
            $exceptionData = $exception->getJsonBody();

            $url = $request->get('redirect') ?? strtok(app('url')->previous(), '?');

            // validate UI known error format
            if (isset($exceptionData['error']) && isset($exceptionData['error']['code'])) {

                if ($request->has('redirect')) {
                    // assume request having redirect is aware and able to proccess stripe session errors
                    return [
                        'redirect' => $url,
                        'errors' => [
                            ['stripe' => $exceptionData['error']],
                        ],
                    ];
                } else {
                    // assume request not having redirect is json request
                    throw new StripeCardException($exceptionData['error']);
                }
            }

            // throw generic
            throw new PaymentFailedException($exception->getMessage());
        } catch (\Exception $paymentFailedException) {
            throw new PaymentFailedException($paymentFailedException->getMessage());
        }

        //create Payment
        $payment = $this->createPayment(
            $cartItemsWithTaxesAndCosts,
            $charge ?? null,
            $transactionId ?? null,
            $paymentMethodId,
            $currency
        );

        //create order
        $order = $this->createOrder(
            $request,
            $cartItemsWithTaxesAndCosts,
            $userId,
            $customer ?? null,
            $billingAddressDB,
            $payment
        );

        //create payment plan
        $paymentPlanNumbersOfPayments = $this->cartService->getPaymentPlanNumberOfPayments();

        //apply order discounts
        $amountDiscounted = $this->applyOrderDiscounts(
            $cartItemsWithTaxesAndCosts,
            $order,
            $cartItems
        );

        // order items
        $orderItems = [];

        foreach ($cartItems as $key => $cartItem) {
            $expirationDate = null;
            $product = $this->productRepository->read($cartItem['options']['product-id']);

            if (!$product['active']) {
                continue;
            }

            $totalPrice = max(
                (float)($cartItem['totalPrice'] + $cartItemsWithTaxesAndCosts['shippingCosts'] - $amountDiscounted),
                0
            );

            $orderItem =
                $this->orderItemRepository->query()
                    ->create(
                        [
                            'order_id' => $order['id'],
                            'product_id' => $product['id'],
                            'quantity' => $cartItem['quantity'],
                            'initial_price' => $cartItem['price'] * $cartItem['quantity'],
                            'discount' => $amountDiscounted,
                            'tax' => $cartItemsWithTaxesAndCosts['totalTax'],
                            'shipping_costs' => $cartItemsWithTaxesAndCosts['shippingCosts'],
                            'total_price' => $totalPrice,
                            'created_on' => Carbon::now()
                                ->toDateTimeString(),
                        ]
                    );

            //apply order items discounts
            $orderItem = $this->applyOrderItemDiscounts(
                $cartItemsWithTaxesAndCosts,
                $key,
                $order,
                $orderItem,
                $cartItems
            );

            //create subscription
            if ($product['type'] == ConfigService::$typeSubscription) {
                $subscription = $this->createSubscription(
                    $request->get('brand', ConfigService::$brand),
                    $product,
                    $order,
                    $cartItemsWithTaxesAndCosts,
                    $key,
                    $cartItem,
                    $userId,
                    $currency,
                    $paymentMethodId,
                    $payment,
                    true
                );
                $expirationDate = $subscription['paid_until'];
            }

            //product fulfillment
            if ($product['is_physical'] == 1) {
                $this->orderItemFulfillmentRepository->create(
                    [
                        'order_id' => $order['id'],
                        'order_item_id' => $orderItem['id'],
                        'status' => 'pending',
                        'created_on' => Carbon::now()
                            ->toDateTimeString(),
                    ]
                );
            }

            $orderItems[] = $orderItem;
        }

        if ($paymentPlanNumbersOfPayments > 1) {
            $this->createSubscription(
                $request->get('brand', ConfigService::$brand),
                null,
                $order,
                $cartItemsWithTaxesAndCosts,
                0,
                [],
                $userId,
                $currency,
                $paymentMethodId,
                $payment,
                false,
                $paymentPlanNumbersOfPayments
            );
        }

        //if the order failed; we throw the proper exception
        throw_if(
            !($order),
            new UnprocessableEntityException('Order failed. Error message: ')
        );

        //prepare currency symbol for order invoice
        switch ($currency) {
            case 'USD':
            case 'CAD':
            default:
                $currencySymbol = '$';
                break;
            case 'GBP':
                $currencySymbol = '£';
                break;
            case 'EUR':
                $currencySymbol = '€';
                break;
        }

        try {
            //prepare the order invoice
            $orderInvoiceEmail = new OrderInvoice(
                [
                    'order' => $order,
                    'orderItems' => $orderItems,
                    'payment' => $payment,
                    'currencySymbol' => $currencySymbol,
                ]
            );
            $emailAddress = $user['email'] ?? $customer['email'];

            Mail::to($emailAddress)
                ->send($orderInvoiceEmail);
        } catch (\Exception $e) {
            error_log('Failed to send invoice for order: ' . $order['id']);
        }

        event(new GiveContentAccess($order));

        //remove all items from the cart
        $this->cartService->removeAllCartItems();

        return ['order' => $order];
    }

    /**
     * @param OrderFormSubmitRequest $request
     * @param                        $user
     * @param                        $customer
     * @param                        $cartItemsWithTaxesAndCosts
     * @param                        $currency
     * @return array
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    private function chargeAndCreatePaymentMethod(
        OrderFormSubmitRequest $request,
        $userId,
        $customer,
        $cartItemsWithTaxesAndCosts,
        $currency
    )
    : array {

        $customerCreditCard = $this->stripePaymentGateway->getOrCreateCustomer(
            $request->get('gateway'),
            $user['email'] ?? $customer['email']
        );

        $card = $this->stripePaymentGateway->createCustomerCard(
            $request->get('gateway'),
            $customerCreditCard,
            $request->get('card-token')
        );

        $charge = $this->stripePaymentGateway->chargeCustomerCard(
            $request->get('gateway'),
            $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
            $currency,
            $card,
            $customerCreditCard
        );

        $billingAddressDB = $this->addressRepository->create(
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => ConfigService::$brand,
                'user_id' => $userId ?? null,
                'customer_id' => $customer['id'] ?? null,
                'zip' => $request->get('billing-zip-or-postal-code'),
                'state' => $request->get('billing-region'),
                'country' => $request->get('billing-country'),
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $paymentMethodId = $this->paymentMethodService->createUserCreditCard(
            $userId,
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
            false,
            $customer['id']
        );

        return [$charge, $paymentMethodId, $billingAddressDB];
    }

    /**
     * @param Request $request
     * @param         $cartItemsWithTaxesAndCosts
     * @param         $currency
     * @param         $user
     * @param         $billingAddressDB
     * @return array
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    private function transactionAndCreatePaymentMethod(
        Request $request,
        $cartItemsWithTaxesAndCosts,
        $currency,
        $user
    )
    : array {

        $billingAgreementId = $this->payPalPaymentGateway->createBillingAgreement(
            $request->get('gateway'),
            $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
            $currency,
            $request->get('token')
        );

        $transactionId = $this->payPalPaymentGateway->chargeBillingAgreement(
            $request->get('gateway'),
            $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
            $currency,
            $billingAgreementId
        );

        $billingAddressDB = $this->addressRepository->create(
            [
                'type' => CartAddressService::BILLING_ADDRESS_TYPE,
                'brand' => ConfigService::$brand,
                'user_id' => $userId ?? null,
                'customer_id' => $customer['id'] ?? null,
                'zip' => $request->get('billing-zip-or-postal-code'),
                'state' => $request->get('billing-region'),
                'country' => $request->get('billing-country'),
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        $paymentMethodId = $this->paymentMethodService->createPayPalBillingAgreement(
            $userId,
            $billingAgreementId,
            $billingAddressDB['id'],
            $request->get('gateway'),
            $currency,
            false
        );

        return [$transactionId, $paymentMethodId, $billingAddressDB];
    }

    /**
     * Re-charge an existing credit card payment method
     *
     * @param OrderFormSubmitRequest $request
     * @param PaymentMethod $paymentMethod
     * @param $cartItemsWithTaxesAndCosts
     * @param $currency
     *
     * @return mixed
     *
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    private function rechargeCreditCard(
        OrderFormSubmitRequest $request,
        PaymentMethod $paymentMethod,
        $cartItemsWithTaxesAndCosts,
        $currency
    ) {

        $customer = $this->stripePaymentGateway->getCustomer(
            $request->get('gateway'),
            $paymentMethod['method']['external_customer_id']
        );

        if (!$customer) {
            return null;
        }

        $card = $this->stripePaymentGateway->getCard(
            $customer,
            $paymentMethod['method']['external_id'],
            $request->get('gateway')
        );

        if (!$card) {
            return null;
        }

        $charge = $this->stripePaymentGateway->chargeCustomerCard(
            $request->get('gateway'),
            $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
            $currency,
            $card,
            $customer
        );

        return $charge;
    }

    /**
     * Re-charge an existing paypal agreement payment method
     *
     * @param OrderFormSubmitRequest $request
     * @param PaymentMethod $paymentMethod
     * @param $cartItemsWithTaxesAndCosts
     * @param $currency
     *
     * @return mixed
     *
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    private function rechargeAgreement(
        OrderFormSubmitRequest $request,
        PaymentMethod $paymentMethod,
        $cartItemsWithTaxesAndCosts,
        $currency
    ) {
        return $this->payPalPaymentGateway->chargeBillingAgreement(
            $request->get('gateway'),
            $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
            $currency,
            $paymentMethod['method']['external_id']
        );
    }

    /**
     * @param $cartItemsWithTaxesAndCosts
     * @param $order
     * @param $cartItems
     * @return float|int
     */
    private function applyOrderDiscounts(
        $cartItemsWithTaxesAndCosts,
        $order,
        $cartItems
    ) {
        $amountDiscounted = 0;

        foreach ($cartItemsWithTaxesAndCosts['cartItems'] as $item) {
            if (array_key_exists('applyDiscount', $item)) {
                foreach ($item['applyDiscount'] as $orderDiscount) {

                    //save order discount
                    $orderDiscount = $this->orderDiscountRepository->create(
                        [
                            'order_id' => $order['id'],
                            'discount_id' => $orderDiscount['id'],
                            'created_on' => Carbon::now()
                                ->toDateTimeString(),
                        ]
                    );
                }

                $amountDiscounted =
                    array_sum(array_column($cartItems, 'totalPrice')) +
                    $cartItemsWithTaxesAndCosts['shippingCosts'] -
                    $cartItemsWithTaxesAndCosts['totalDue'];

            }
        }

        return $amountDiscounted;
    }

    /**
     * @param $cartItemsWithTaxesAndCosts
     * @param $key
     * @param $order
     * @param $orderItem
     * @param $cartItems
     */
    private function applyOrderItemDiscounts(
        $cartItemsWithTaxesAndCosts,
        $key,
        $order,
        $orderItem,
        $cartItems
    ) {
        //apply order item discount
        if (array_key_exists(
            'applyDiscount',
            $cartItemsWithTaxesAndCosts['cartItems'][$key]
        )) {
            //save order item discount
            foreach ($cartItemsWithTaxesAndCosts['cartItems'][$key]['applyDiscount'] as $itemDiscount) {
                $orderDiscount = $this->orderDiscountRepository->create(
                    [
                        'order_id' => $order['id'],
                        'order_item_id' => $orderItem['id'],
                        'discount_id' => $itemDiscount['id'],
                        'created_on' => Carbon::now()
                            ->toDateTimeString(),
                    ]
                );
            }

            $itemAmountDiscounted =
                $cartItemsWithTaxesAndCosts['cartItems'][$key]['totalPrice'] -
                $cartItemsWithTaxesAndCosts['cartItems'][$key]['discountedPrice'];

            $totalPrice = max((float)($orderItem['initial_price'] - $itemAmountDiscounted), 0);

            return $this->orderItemRepository->update(
                $orderItem['id'],
                [
                    'order_id' => $order['id'],
                    'discount' => $itemAmountDiscounted,
                    'total_price' => $totalPrice,
                ]
            );

        }

        return $orderItem;
    }

    /**
     * @param $cartItemsWithTaxesAndCosts
     * @param $charge
     * @param $transactionId
     * @param $paymentMethodId
     * @param $currency
     * @return null|\Railroad\Resora\Entities\Entity
     */
    private function createPayment(
        $cartItemsWithTaxesAndCosts,
        $charge,
        $transactionId,
        $paymentMethodId,
        $currency
    ) {

        $paid = $cartItemsWithTaxesAndCosts['initialPricePerPayment'];

        $externalProvider = isset($charge['id']) ? 'stripe' : 'paypal';

        $payment = $this->paymentRepository->create(
            [
                'due' => $cartItemsWithTaxesAndCosts['totalDue'],
                'paid' => $paid,
                'refunded' => 0,
                'type' => 'order',
                'external_id' => $charge['id'] ?? $transactionId,
                'external_provider' => $externalProvider,
                'status' => 'paid',
                'message' => '',
                'payment_method_id' => $paymentMethodId,
                'currency' => $currency,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        return $payment;
    }

    /**
     * @param Request $request
     * @param                        $cartItemsWithTaxesAndCosts
     * @param                        $user
     * @param                        $customer
     * @param                        $billingAddressDB
     * @param                        $payment
     * @return null|\Railroad\Resora\Entities\Entity
     */
    private function createOrder(
        Request $request,
        $cartItemsWithTaxesAndCosts,
        $userId,
        $customer,
        $billingAddressDB,
        $payment
    ) {
        $shippingAddressDB = null;

        if ($request->get('shipping-address-id')) {

            $shippingAddressDB = $this->addressRepository->read($request->get('shipping-address-id'));

            $message =
                'Order failed. Error message: could not find shipping address id: ' .
                $request->get('shipping-address-id');

            throw_if(
                !($shippingAddressDB),
                new UnprocessableEntityException($message)
            );
        } else {
            //save the shipping address

            $shippingAddressDB = $this->addressRepository->create(
                [
                    'type' => ConfigService::$shippingAddressType,
                    'brand' => ConfigService::$brand,
                    'user_id' => $userId ?? null,
                    'customer_id' => $customer['id'] ?? null,
                    'first_name' => $request->get('shipping-first-name'),
                    'last_name' => $request->get('shipping-last-name'),
                    'street_line_1' => $request->get('shipping-address-line-1'),
                    'street_line_2' => $request->get('shipping-address-line-2'),
                    'city' => $request->get('shipping-city'),
                    'zip' => $request->get('shipping-zip-or-postal-code'),
                    'state' => $request->get('shipping-region'),
                    'country' => $request->get('shipping-country'),
                    'created_on' => Carbon::now()
                        ->toDateTimeString(),
                ]
            );
        }

        $paid = $cartItemsWithTaxesAndCosts['initialPricePerPayment'];
        $shipping = $cartItemsWithTaxesAndCosts['shippingCosts'];

        $order = $this->orderRepository->create(
            [
                'due' => $cartItemsWithTaxesAndCosts['totalDue'],
                'tax' => $cartItemsWithTaxesAndCosts['totalTax'],
                'paid' => $paid,
                'brand' => $request->input('brand', ConfigService::$brand),
                'user_id' => $userId ?? null,
                'customer_id' => $customer['id'] ?? null,
                'shipping_costs' => $shipping,
                'shipping_address_id' => $shippingAddressDB['id'],
                'billing_address_id' => $billingAddressDB['id'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        // attach order to payment
        $this->orderPaymentRepository->create(
            [
                'order_id' => $order['id'],
                'payment_id' => $payment['id'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        return $order;
    }

    /**
     * @param OrderFormSubmitRequest $request
     * @param                        $product
     * @param                        $order
     * @param                        $cartItemsWithTaxesAndCosts
     * @param                        $key
     * @param                        $cartItem
     * @param                        $user
     * @param                        $currency
     * @param                        $paymentMethodId
     * @param                        $payment
     * @throws \Railroad\Ecommerce\Exceptions\UnprocessableEntityException
     */
    private function createSubscription(
        $brand,
        $product = null,
        $order,
        $cartItemsWithTaxesAndCosts,
        $key = 0,
        $cartItem = [],
        $userId,
        $currency,
        $paymentMethodId,
        $payment,
        $applyDiscounts = false,
        $totalCyclesDue = null
    ) {
        $type = ConfigService::$typeSubscription;

        // if the product it's not defined we should create a payment plan.
        // Define payment plan next bill date, price per payment and tax per payment.
        if (is_null($product)) {

            $nextBillDate =
                Carbon::now()
                    ->addMonths(1);
            $type = ConfigService::$paymentPlanType;
            $subscriptionPricePerPayment = $cartItemsWithTaxesAndCosts['pricePerPayment'];
            $totalTaxSplitedPerPaymentPlan = $cartItemsWithTaxesAndCosts['totalTax'] / $totalCyclesDue;

        } else {
            if (!empty($product['subscription_interval_type'])) {
                if ($product['subscription_interval_type'] == ConfigService::$intervalTypeMonthly) {
                    $nextBillDate =
                        Carbon::now()
                            ->addMonths(
                                $product['subscription_interval_count']
                            );

                } elseif ($product['subscription_interval_type'] == ConfigService::$intervalTypeYearly) {
                    $nextBillDate =
                        Carbon::now()
                            ->addYears(
                                $product['subscription_interval_count']
                            );

                } elseif ($product['subscription_interval_type'] == ConfigService::$intervalTypeDaily) {
                    $nextBillDate =
                        Carbon::now()
                            ->addDays(
                                $product['subscription_interval_count']
                            );
                }
            } else {
                $message = 'Failed to create subscription for order id: ';
                $message .= $order['id'];
                throw new UnprocessableEntityException($message);
            }
        }

        //apply subscription discounts
        if ($applyDiscounts && array_key_exists(
                'applyDiscount',
                $cartItemsWithTaxesAndCosts['cartItems'][$key]
            )) {
            foreach ($cartItemsWithTaxesAndCosts['cartItems'][$key]['applyDiscount'] as $discount) {
                if ($discount['type'] == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {
                    //add the days from the discount to the subscription next bill date
                    $nextBillDate = $nextBillDate->addDays(
                        $discount['amount']
                    );

                } elseif ($discount['type'] == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                    //calculate subscription price per payment after discount
                    $subscriptionPricePerPayment = $cartItem['price'] - $discount['amount'];
                }
            }
        }

        //create subscription
        $subscription = $this->subscriptionRepository->create(
            [
                'brand' => $brand,
                'type' => $type,
                'user_id' => $userId,
                'order_id' => $order['id'],
                'product_id' => $product['id'] ?? null,
                'is_active' => true,
                'start_date' => Carbon::now()
                    ->toDateTimeString(),
                'paid_until' => $nextBillDate->toDateTimeString(),
                'total_price_per_payment' => $subscriptionPricePerPayment ?? $cartItem['price'],
                'tax_per_payment' => $totalTaxSplitedPerPaymentPlan ?? $cartItemsWithTaxesAndCosts['totalTax'],
                'shipping_per_payment' => 0,
                'currency' => $currency,
                'interval_type' => $product['subscription_interval_type'] ?? ConfigService::$intervalTypeMonthly,
                'interval_count' => $product['subscription_interval_count'] ?? 1,
                'total_cycles_paid' => 1,
                'total_cycles_due' => $totalCyclesDue,
                'payment_method_id' => $paymentMethodId,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        // attach subscription to payment
        $this->subscriptionPaymentRepository->create(
            [
                'subscription_id' => $subscription['id'],
                'payment_id' => $payment['id'],
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        return $subscription;
    }
}
