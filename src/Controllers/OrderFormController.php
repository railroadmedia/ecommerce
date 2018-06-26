<?php

namespace Railroad\Ecommerce\Controllers;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\UnprocessableEntityException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Mail\OrderInvoice;
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
use Railroad\Ecommerce\Services\PaymentPlanService;
use Railroad\Ecommerce\Services\TaxService;
use Railroad\Location\Services\LocationService;

class OrderFormController extends BaseController
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
     * @var mixed UserProviderInterface
     */
    private $userProvider;

    /**
     * @var \Railroad\Ecommerce\Services\PaymentPlanService
     */
    private $paymentPlanService;

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
        DiscountService $discountService,
        PaymentPlanService $paymentPlanService
    ) {
        parent::__construct();

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
        $this->paymentPlanService             = $paymentPlanService;
        $this->userProvider                   = app()->make('UserProviderInterface');
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
        $cartItems = $this->cartService->getAllCartItems();

        $user = auth()->user() ?? null;

        $currency = $request->get('currency', $this->currencyService->get());

        //if the cart it's empty; we throw an exception
        throw_if(
            empty($cartItems),
            new NotFoundException('The cart it\'s empty')
        );

        if($request->has('account-creation-email'))
        {
            $user = $this->userProvider->create(
                $request->get('account-creation-email'),
                $request->get('account-creation-password')
            );
        }

        //save customer if billing email exists on request
        if($request->has('billing-email'))
        {
            $customer = $this->customerRepository->create(
                [
                    'email'      => $request->get('billing-email'),
                    'brand'      => ConfigService::$brand,
                    'created_on' => Carbon::now()->toDateTimeString()
                ]
            );
        }

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

        //calculate shipping costs
        $shippingCosts = $this->shippingOptionsRepository->getShippingCosts(
                $request->get('shipping-country'),
                array_sum(array_column($cartItems, 'weight'))
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

        $this->cartService->setPaymentPlanNumberOfPayments($request->get('payment-plan-selector'));

        $cartItemsWithTaxesAndCosts =
            $this->taxService->calculateTaxesForCartItems(
                $cartItems,
                $billingAddress['country'],
                $billingAddress['region'],
                $shippingCosts,
                $currency,
                $this->cartService->getPromoCode()
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
                list($charge, $paymentMethodId) = $this->chargeAndCreatePaymentMethod(
                    $request,
                    $user,
                    $customer ?? null,
                    $cartItemsWithTaxesAndCosts,
                    $currency,
                    $billingAddressDB
                );
            }
            elseif(($request->get('payment_method_type') == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) ||
                !empty($request->get('validated-express-checkout-token')))
            {
                if(empty($request->get('validated-express-checkout-token')))
                {
                    $url = $this->payPalPaymentGateway->getBillingAgreementExpressCheckoutUrl(
                        $request->get('gateway'),
                        ConfigService::$paymentGateways['paypal'][$request->get('gateway')]['paypal_api_checkout_return_url']
                    );
                    session()->put('order-form-input', $request->all());

                    return redirect()->away($url);
                }

                list($transactionId, $paymentMethodId) = $this->transactionAndCreatePaymentMethod(
                    $request,
                    $cartItemsWithTaxesAndCosts,
                    $currency,
                    $user,
                    $billingAddressDB
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

        //create Payment
        $payment = $this->createPayment($cartItemsWithTaxesAndCosts, $charge ?? null, $transactionId ?? null, $paymentMethodId, $currency);

        //create order
        $order = $this->createOrder($request, $cartItemsWithTaxesAndCosts, $user ?? null, $customer ?? null, $billingAddressDB, $payment);

        //create payment plan
        $paymentPlanNumbersOfPayments = $this->cartService->getPaymentPlanNumberOfPayments();
        if($paymentPlanNumbersOfPayments > 1)
        {
            $this->createSubscription(
                $request->get('brand', ConfigService::$brand),
                null,
                $order,
                $cartItemsWithTaxesAndCosts,
                0,
                [],
                $user,
                $currency,
                $paymentMethodId,
                $payment,
                false,
                $paymentPlanNumbersOfPayments);
        }
        //apply order discounts
        $amountDiscounted = $this->applyOrderDiscounts($cartItemsWithTaxesAndCosts, $order, $cartItems);

        // order items
        $orderItems = [];
        foreach($cartItems as $key => $cartItem)
        {
            $product = $this->productRepository->read($cartItem['options']['product-id']);

            if(!$product['active'])
            {
                continue;
            }

            $orderItem = $this->orderItemRepository->query()->create(
                [
                    'order_id'       => $order['id'],
                    'product_id'     => $product['id'],
                    'quantity'       => $cartItem['quantity'],
                    'initial_price'  => $cartItem['price'] * $cartItem['quantity'],
                    'discount'       => $amountDiscounted,
                    'tax'            => $cartItemsWithTaxesAndCosts['totalTax'],
                    'shipping_costs' => $cartItemsWithTaxesAndCosts['shippingCosts'],
                    'total_price'    => max((float) ($cartItem['totalPrice'] + $cartItemsWithTaxesAndCosts['shippingCosts'] - $amountDiscounted), 0),
                    'created_on'     => Carbon::now()->toDateTimeString(),
                ]
            );

            //apply order items discounts
            $orderItem = $this->applyOrderItemDiscounts($cartItemsWithTaxesAndCosts, $key, $order, $orderItem, $cartItems);

            //create subscription
            if($product['type'] == ConfigService::$typeSubscription)
            {
                $this->createSubscription(
                    $request->get('brand', ConfigService::$brand),
                    $product,
                    $order,
                    $cartItemsWithTaxesAndCosts,
                    $key,
                    $cartItem,
                    $user,
                    $currency,
                    $paymentMethodId,
                    $payment,
                    true);
            }

            //product fulfillment
            if($product['is_physical'] == 1)
            {
                $this->orderItemFulfillmentRepository->create([
                    'order_id'      => $order['id'],
                    'order_item_id' => $orderItem['id'],
                    'status'        => 'pending',
                    'created_on'    => Carbon::now()->toDateTimeString()
                ]);
            }

            $orderItems[] = $orderItem;
        }

        //if the order failed; we throw the proper exception
        throw_if(
            !($order),
            new UnprocessableEntityException('Order failed. Error message: ')
        );

        //prepare currency symbol for order invoice
        switch($currency)
        {
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

        try
        {
            //prepare the order invoice
            $orderInvoiceEmail = new OrderInvoice([
                'order'          => $order,
                'orderItems'     => $orderItems,
                'payment'        => $payment,
                'currencySymbol' => $currencySymbol
            ]);
            $emailAddress      = $user['email'] ?? $customer['email'];

            Mail::to($emailAddress)->send($orderInvoiceEmail);
        }
        catch(\Exception $e)
        {
            error_log('Failed to send invoice for order: ' . $order['id']);
        }

        event(new GiveContentAccess($order));

        //remove all items from the cart
        $this->cartService->removeAllCartItems();

        return new JsonResponse($order, 200);
    }

    /**
     * @param \Railroad\Ecommerce\Requests\OrderFormSubmitRequest $request
     * @param                                                     $user
     * @param                                                     $customer
     * @param                                                     $cartItemsWithTaxesAndCosts
     * @param                                                     $currency
     * @param                                                     $billingAddressDB
     * @return array
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    private function chargeAndCreatePaymentMethod(
        OrderFormSubmitRequest $request,
        $user,
        $customer,
        $cartItemsWithTaxesAndCosts,
        $currency,
        $billingAddressDB
    ): array {
        $customerCreditCard = $this->stripePaymentGateway->getOrCreateCustomer(
            $request->get('gateway'),
            $user['email'] ?? $customer['email']
        );

        $card = $this->stripePaymentGateway->createCustomerCard(
            $request->get('gateway'),
            $customerCreditCard,
            $request->get('card-token')
        );

        $charge =
            $this->stripePaymentGateway->chargeCustomerCard(
                $request->get('gateway'),
                $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
                $currency,
                $card,
                $customerCreditCard
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
            false,
            $customer['id']
        );

        return array($charge, $paymentMethodId);
    }

    /**
     * @param \Railroad\Ecommerce\Requests\OrderFormSubmitRequest $request
     * @param                                                     $cartItemsWithTaxesAndCosts
     * @param                                                     $currency
     * @param                                                     $user
     * @param                                                     $billingAddressDB
     * @return array
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    private function transactionAndCreatePaymentMethod(
        OrderFormSubmitRequest $request,
        $cartItemsWithTaxesAndCosts,
        $currency,
        $user,
        $billingAddressDB
    ): array {
        $billingAgreementId =
            $this->payPalPaymentGateway->createBillingAgreement(
                $request->get('gateway'),
                $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
                $currency,
                $request->get('validated-express-checkout-token')
            );
        $transactionId      =
            $this->payPalPaymentGateway->chargeBillingAgreement(
                $request->get('gateway'),
                $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
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

        return array($transactionId, $paymentMethodId);
    }

    /**
     * @param \Railroad\Ecommerce\Requests\OrderFormSubmitRequest $request
     * @param                                                     $product
     * @param                                                     $order
     * @param                                                     $cartItemsWithTaxesAndCosts
     * @param                                                     $key
     * @param                                                     $cartItem
     * @param                                                     $user
     * @param                                                     $currency
     * @param                                                     $paymentMethodId
     * @param                                                     $payment
     * @throws \Railroad\Ecommerce\Exceptions\UnprocessableEntityException
     */
    private function createSubscription(
        $brand,
        $product = null,
        $order,
        $cartItemsWithTaxesAndCosts,
        $key = 0,
        $cartItem = [],
        $user,
        $currency,
        $paymentMethodId,
        $payment,
        $applyDiscounts = false,
        $totalCyclesDue = null
    ) {
        $type = ConfigService::$typeSubscription;

        //if the product it's not defined we should create a payment plan.
        //Define payment plan next bill date, price per payment and tax per payment.
        if(is_null($product))
        {
            $nextBillDate                  = Carbon::now()->addMonths(1);
            $type                          = ConfigService::$paymentPlanType;
            $subscriptionPricePerPayment   = $cartItemsWithTaxesAndCosts['pricePerPayment'];
            $totalTaxSplitedPerPaymentPlan = $cartItemsWithTaxesAndCosts['totalTax'] / $totalCyclesDue;
        }
        else if(!empty($product['subscription_interval_type']))
        {
            if($product['subscription_interval_type'] == ConfigService::$intervalTypeMonthly)
            {
                $nextBillDate = Carbon::now()->addMonths($product['subscription_interval_count']);
            }
            elseif($product['subscription_interval_type'] == ConfigService::$intervalTypeYearly)
            {
                $nextBillDate = Carbon::now()->addYears($product['subscription_interval_count']);
            }
            elseif($product['subscription_interval_type'] == ConfigService::$intervalTypeDaily)
            {
                $nextBillDate = Carbon::now()->addDays($product['subscription_interval_count']);
            }
        }
        else
        {
            throw new UnprocessableEntityException('Failed to create subscription for order id: ' . $order['id']);
        }
        //apply subscription discounts
        if(($applyDiscounts) && (array_key_exists('applyDiscount', $cartItemsWithTaxesAndCosts['cartItems'][$key])))
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

        //create subscription
        $subscription = $this->subscriptionRepository->create(
            [
                'brand'                   => $brand,
                'type'                    => $type,
                'user_id'                 => $user['id'],
                'order_id'                => $order['id'],
                'product_id'              => $product['id'] ?? null,
                'is_active'               => true,
                'start_date'              => Carbon::now()->toDateTimeString(),
                'paid_until'              => $nextBillDate->toDateTimeString(),
                'total_price_per_payment' => $subscriptionPricePerPayment ?? $cartItem['price'],
                'tax_per_payment'         => $totalTaxSplitedPerPaymentPlan ?? $cartItemsWithTaxesAndCosts['totalTax'],
                'shipping_per_payment'    => 0,
                'currency'                => $currency,
                'interval_type'           => $product['subscription_interval_type'] ?? ConfigService::$intervalTypeMonthly,
                'interval_count'          => $product['subscription_interval_count'] ?? 1,
                'total_cycles_paid'       => 1,
                'total_cycles_due'        => $totalCyclesDue,
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

    /**
     * @param \Railroad\Ecommerce\Requests\OrderFormSubmitRequest $request
     * @param                                                     $cartItemsWithTaxesAndCosts
     * @param                                                     $user
     * @param                                                     $customer
     * @param                                                     $billingAddressDB
     * @param                                                     $payment
     * @return null|\Railroad\Resora\Entities\Entity
     */
    private function createOrder(OrderFormSubmitRequest $request, $cartItemsWithTaxesAndCosts, $user, $customer, $billingAddressDB, $payment)
    {
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

        $order = $this->orderRepository->create(
            [
                'due'                 => $cartItemsWithTaxesAndCosts['totalDue'],
                'tax'                 => $cartItemsWithTaxesAndCosts['totalTax'],
                'paid'                => $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
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

        return $order;
    }

    /**
     * @param $cartItemsWithTaxesAndCosts
     * @param $charge
     * @param $transactionId
     * @param $paymentMethodId
     * @param $currency
     * @return null|\Railroad\Resora\Entities\Entity
     */
    private function createPayment($cartItemsWithTaxesAndCosts, $charge, $transactionId, $paymentMethodId, $currency)
    {
        $payment = $this->paymentRepository->create(
            [
                'due'               => $cartItemsWithTaxesAndCosts['totalDue'],
                'paid'              => $cartItemsWithTaxesAndCosts['initialPricePerPayment'],
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

        return $payment;
    }

    /**
     * @param $cartItemsWithTaxesAndCosts
     * @param $order
     * @param $cartItems
     * @return float|int
     */
    private function applyOrderDiscounts($cartItemsWithTaxesAndCosts, $order, $cartItems)
    {
        $amountDiscounted = 0;

        foreach($cartItemsWithTaxesAndCosts['cartItems'] as $item)
        {
            if(array_key_exists('applyDiscount', $item))
            {
                //save order discount
                $orderDiscount    = $this->orderDiscountRepository->create([
                    'order_id'    => $order['id'],
                    'discount_id' => $item['applyDiscount']['discount_id'],
                    'created_on'  => Carbon::now()->toDateTimeString()
                ]);
                $amountDiscounted = array_sum(array_column($cartItems, 'totalPrice')) + $cartItemsWithTaxesAndCosts['shippingCosts'] - $cartItemsWithTaxesAndCosts['totalDue'];
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
    private function applyOrderItemDiscounts($cartItemsWithTaxesAndCosts, $key, $order, $orderItem, $cartItems)
    {
        //apply order item discount
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

            return $this->orderItemRepository->update($orderItem['id'], [
                'order_id'    => $order['id'],
                'discount'    => $itemAmountDiscounted,
                'total_price' => max((float) ($orderItem['initial_price'] - $itemAmountDiscounted), 0)
            ]);
        }

        return $orderItem;
    }
}