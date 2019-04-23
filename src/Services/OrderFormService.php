<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Entities\Discount;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Entities\OrderDiscount;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Product;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Entities\User;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\StripeCardException;
use Railroad\Ecommerce\Exceptions\UnprocessableEntityException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Managers\EcommerceEntityManager;
use Railroad\Ecommerce\Repositories\AddressRepository;
use Railroad\Ecommerce\Repositories\CreditCardRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaypalBillingAgreementRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Repositories\UserStripeCustomerIdRepository;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Permissions\Services\PermissionService;
use Stripe\Error\Card as StripeCard;
use Throwable;

class OrderFormService
{
    /**
     * @var AddressRepository
     */
    private $addressRepository;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var CartAddressService
     */
    private $cartAddressService;

    /**
     * @var CreditCardRepository
     */
    private $creditCardRepository;

    /**
     * @var CurrencyService
     */
    private $currencyService;

    /**
     * @var DiscountService
     */
    private $discountService;

    /**
     * @var EcommerceEntityManager
     */
    private $entityManager;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var PaymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var PaypalBillingAgreementRepository
     */
    private $paypalBillingAgreementRepository;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var StripePaymentGateway
     */
    private $stripePaymentGateway;

    /**
     * @var mixed UserProductService
     */
    private $userProductService;

    /**
     * @var mixed UserProviderInterface
     */
    private $userProvider;
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var mixed UserStripeCustomerIdRepository
     */
    private $userStripeCustomerIdRepository;

    /**
     * @var PurchaserService
     */
    private $purchaserService;

    /**
     * @var OrderClaimingService
     */
    private $orderClaimingService;

    /**
     * OrderFormService constructor.
     *
     * @param AddressRepository $addressRepository
     * @param CartService $cartService
     * @param CartAddressService $cartAddressService
     * @param CreditCardRepository $creditCardRepository
     * @param CurrencyService $currencyService
     * @param DiscountService $discountService
     * @param EcommerceEntityManager $entityManager
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param PaymentMethodService $paymentMethodService
     * @param PaypalBillingAgreementRepository $paypalBillingAgreementRepository
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PermissionService $permissionService
     * @param ProductRepository $productRepository
     * @param StripePaymentGateway $stripePaymentGateway
     * @param UserProductService $userProductService
     * @param UserProviderInterface $userProvider
     * @param UserStripeCustomerIdRepository $userStripeCustomerIdRepository
     * @param PaymentService $paymentService
     * @param PurchaserService $purchaserService
     * @param OrderClaimingService $orderClaimingService
     */
    public function __construct(
        AddressRepository $addressRepository,
        CartService $cartService,
        CartAddressService $cartAddressService,
        CreditCardRepository $creditCardRepository,
        CurrencyService $currencyService,
        DiscountService $discountService,
        EcommerceEntityManager $entityManager,
        PaymentMethodRepository $paymentMethodRepository,
        PaymentMethodService $paymentMethodService,
        PaypalBillingAgreementRepository $paypalBillingAgreementRepository,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        ProductRepository $productRepository,
        StripePaymentGateway $stripePaymentGateway,
        UserProductService $userProductService,
        UserProviderInterface $userProvider,
        UserStripeCustomerIdRepository $userStripeCustomerIdRepository,
        PaymentService $paymentService,
        PurchaserService $purchaserService,
        OrderClaimingService $orderClaimingService
    )
    {
        $this->addressRepository = $addressRepository;
        $this->cartService = $cartService;
        $this->cartAddressService = $cartAddressService;
        $this->creditCardRepository = $creditCardRepository;
        $this->currencyService = $currencyService;
        $this->discountService = $discountService;
        $this->entityManager = $entityManager;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->paymentMethodService = $paymentMethodService;
        $this->paypalBillingAgreementRepository = $paypalBillingAgreementRepository;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->productRepository = $productRepository;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->userProductService = $userProductService;
        $this->userProvider = $userProvider;
        $this->userStripeCustomerIdRepository = $userStripeCustomerIdRepository;
        $this->paymentService = $paymentService;
        $this->purchaserService = $purchaserService;
        $this->orderClaimingService = $orderClaimingService;
    }

    /**
     * @param Order $order
     *
     * @return bool
     *
     * @throws Throwable
     */
    private function createOrderDiscounts(Order $order, array $discounts)
    {
        // todo - deprecated, to be removed
        $orderDiscountTypes = [
            DiscountService::ORDER_TOTAL_SHIPPING_AMOUNT_OFF_TYPE => true,
            DiscountService::ORDER_TOTAL_SHIPPING_PERCENT_OFF_TYPE => true,
            DiscountService::ORDER_TOTAL_SHIPPING_OVERWRITE_TYPE => true,
            DiscountService::ORDER_TOTAL_AMOUNT_OFF_TYPE => true,
            DiscountService::ORDER_TOTAL_PERCENT_OFF_TYPE => true,
        ];

        foreach ($discounts as $discount) {

            /** @var $discount Discount */

            if (isset($orderDiscountTypes[$discount->getType()])) {

                $orderDiscount = new OrderDiscount();

                $orderDiscount->setOrder($order)
                    ->setDiscount($discount)
                    ->setCreatedAt(Carbon::now());

                $this->entityManager->persist($orderDiscount);
            }
        }

        $this->entityManager->flush();

        return true;
    }

    /**
     * @param Order $order
     * @param OrderItem $orderItem
     * @param array $discounts
     *
     * @return OrderItem
     *
     * @throws Throwable
     */
    private function createOrderItemDiscounts(
        Order $order,
        OrderItem $orderItem,
        array $discounts
    ): OrderItem
    {
        // todo - deprecated, to be removed
        $orderItemDiscountTypes = [
            DiscountService::PRODUCT_AMOUNT_OFF_TYPE => true,
            DiscountService::PRODUCT_PERCENT_OFF_TYPE => true,
            DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE => true,
            DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE => true,
        ];

        /** @var $orderItemProduct Product */
        $orderItemProduct = $orderItem->getProduct();

        foreach ($discounts as $discount) {

            /** @var $discount Discount */

            /** @var Product $discountProduct */
            $discountProduct = $discount->getProduct();

            if (isset($orderItemDiscountTypes[$discount->getType()]) &&
                (($discountProduct && $orderItemProduct->getId() == $discountProduct->getId()) ||
                    $orderItemProduct->getCategory() == $discount->getProductCategory())) {

                $orderDiscount = new OrderDiscount();

                $orderDiscount->setOrder($order)
                    ->setDiscount($discount)
                    ->setOrderItem($orderItem)
                    ->setCreatedAt(Carbon::now());

                $this->entityManager->persist($orderDiscount);
            }
        }

        $this->entityManager->flush();

        return $orderItem;
    }

    /**
     * @param float $paid
     * @param float $shipping
     * @param float $totalDue
     * @param float $productDue
     * @param float $financeDue
     * @param float $totalTax
     * @param User $user
     * @param Customer $customer
     * @param Address $billingAddress
     * @param Address $shippingAddress
     * @param Payment $payment
     * @param string $brand
     *
     * @return Order
     *
     * @throws Throwable
     */
    private function createOrder(
        $paid,
        $shipping,
        $totalDue,
        $productDue,
        $financeDue,
        $totalTax,
        ?User $user,
        ?Customer $customer,
        Address $billingAddress,
        ?Address $shippingAddress,
        Payment $payment,
        $brand
    ): Order
    {
        // todo - deprecated, to be removed
        $order = new Order();

        $order->setTotalDue($totalDue)
            ->setProductDue($productDue)
            ->setFinanceDue($financeDue)
            ->setTaxesDue($totalTax)
            ->setTotalPaid($paid)
            ->setBrand($brand)
            ->setUser($user)
            ->setCustomer($customer)
            ->setShippingDue($shipping)
            ->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress)
            ->setCreatedAt(Carbon::now());

        $orderPayment = new OrderPayment();

        $orderPayment->setOrder($order)
            ->setPayment($payment)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($order);
        $this->entityManager->persist($orderPayment);
        $this->entityManager->flush();

        return $order;
    }

    /**
     * @param string $brand
     * @param Product $product
     * @param Order $order
     * @param CartItem $cartItem
     * @param array $discounts
     * @param User $user
     * @param string $currency
     * @param PaymentMethod $paymentMethod
     * @param Payment $payment
     * @param int $totalCyclesDue
     *
     * @return Subscription
     *
     * @throws UnprocessableEntityException
     * @throws Throwable
     */
    private function createSubscription(
        $brand,
        ?Product $product,
        Order $order,
        ?CartItem $cartItem,
        array $discounts,
        User $user,
        $currency,
        PaymentMethod $paymentMethod,
        Payment $payment,
        $totalCyclesDue = null
    ): Subscription
    {
        // todo - deprecated, to be removed
        $type = ConfigService::$typeSubscription;

        // if the product it's not defined we should create a payment plan.
        // Define payment plan next bill date, price per payment and tax per payment.

        $nextBillDate = null;

        if (is_null($product)) {

            $nextBillDate =
                Carbon::now()
                    ->addMonths(1);

            $type = ConfigService::$paymentPlanType;

            $subscriptionPricePerPayment = $this->cartService->getTotalDueForOrder();

        }
        else {

            if (!empty($product->getSubscriptionIntervalType())) {
                if ($product->getSubscriptionIntervalType() == ConfigService::$intervalTypeMonthly) {
                    $nextBillDate =
                        Carbon::now()
                            ->addMonths($product->getSubscriptionIntervalCount());

                }
                elseif ($product->getSubscriptionIntervalType() == ConfigService::$intervalTypeYearly) {
                    $nextBillDate =
                        Carbon::now()
                            ->addYears($product->getSubscriptionIntervalCount());

                }
                elseif ($product->getSubscriptionIntervalType() == ConfigService::$intervalTypeDaily) {
                    $nextBillDate =
                        Carbon::now()
                            ->addDays($product->getSubscriptionIntervalCount());
                }
            }
            else {
                $message = 'Failed to create subscription for order id: ';
                $message .= $order->getId();
                throw new UnprocessableEntityException($message);
            }
        }

        if ($cartItem && $product) {

            foreach ($discounts as $discount) {
                /** @var $discount Discount */

                /** @var Product $discountProduct */
                $discountProduct = $discount->getProduct();

                if (($discountProduct && $product->getId() == $discountProduct->getId()) ||
                    $product->getCategory() == $discount->getProductCategory()) {
                    if ($discount->getType() == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {
                        //add the days from the discount to the subscription next bill date
                        $nextBillDate = $nextBillDate->addDays($discount->getAmount());

                    }
                    elseif ($discount->getType() == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                        //calculate subscription price per payment after discount
                        $subscriptionPricePerPayment = $product->getPrice() - $discount->getAmount();
                    }
                }
            }
        }

        $subscription = new Subscription();

        $intervalType = $product ? $product->getSubscriptionIntervalType() : ConfigService::$intervalTypeMonthly;

        $intervalCount = $product ? $product->getSubscriptionIntervalCount() : 1;

        $subscription->setBrand($brand)
            ->setType($type)
            ->setUser($user)
            ->setOrder($order)
            ->setProduct($product)
            ->setIsActive(true)
            ->setStartDate(Carbon::now())
            ->setPaidUntil($nextBillDate)
            ->setTotalPrice($subscriptionPricePerPayment ?? $product->getPrice())
            ->setCurrency($currency)
            ->setIntervalType($intervalType)
            ->setIntervalCount($intervalCount)
            ->setTotalCyclesPaid(1)
            ->setTotalCyclesDue($totalCyclesDue)
            ->setPaymentMethod($paymentMethod)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($subscription);

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment->setSubscription($subscription)
            ->setPayment($payment)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($subscriptionPayment);

        $this->entityManager->flush();

        return $subscription;
    }

    /**
     * Returns client shipping address
     * If request contains a shipping address id key, it will look it up in db
     * Throws exception if not found
     * If cart items require shipping it will create a new address with request data
     *
     * @param Request $request
     * @param User $user
     * @param Customer $customer
     *
     * @return Address
     *
     * @throws Throwable
     */
    public function getShippingAddress(
        Request $request,
        ?User $user,
        ?Customer $customer
    ): ?Address
    {
        // todo - review and update/remove
        $shippingAddress = null;

        if ($request->get('shipping_address_id')) {

            $shippingAddress = $this->addressRepository->find($request->get('shipping_address_id'));

            $message =
                'Order failed. Error message: could not find shipping address id: ' .
                $request->get('shipping-address-id');

            throw_if(!($shippingAddress), new UnprocessableEntityException($message));

            // todo: fix
            //        } elseif ($this->cartService->cartHasAnyPhysicalItems()) {
        }
        elseif (true) {
            //save the shipping address

            $shippingAddress = $request->getShippingAddress();

            $shippingAddress->setBrand(ConfigService::$brand)
                ->setUser($user)
                ->setCustomer($customer);

            $this->entityManager->persist($shippingAddress);
        }

        return $shippingAddress;
    }

    /**
     * Creates payment and order objects
     *
     * @param PaymentMethod $paymentMethod
     * @param User $user
     * @param Customer $customer
     * @param Address $billingAddress
     * @param Address $shippingAddress
     * @param $charge
     * @param $transactionId
     * @param string $currency
     * @param string $brand
     * @param float $paymentAmount
     *
     * @return array
     *
     * Returns array [$payment, $order]
     *
     * @throws Throwable
     */
    public function createPaymentAndOrder(
        PaymentMethod $paymentMethod,
        ?User $user,
        ?Customer $customer,
        Address $billingAddress,
        ?Address $shippingAddress,
        $charge,
        $transactionId,
        string $currency,
        string $brand,
        float $paymentAmount
    )
    {
        // todo - deprecated, to be removed
        // create Payment
        $payment = $this->createPayment(
            $paymentAmount,
            $this->cartService->getDueForInitialPayment(),
            $charge ?? null,
            $transactionId ?? null,
            $paymentMethod,
            $currency
        );

        $order = $this->cartService->generateOrder();

        // create order
        $order = $this->createOrder(
            $paymentAmount,
            $productsShippingPrice,
            $totalDue,
            $productsDuePrice,
            $financeDue,
            $productsTaxPrice,
            $user ?? null,
            $customer ?? null,
            $billingAddress,
            $shippingAddress,
            $payment,
            $brand
        );

        // if the order failed; we throw the proper exception
        throw_if(!($order), new UnprocessableEntityException('Order failed.'));

        return [$payment, $order];
    }

    /**
     * Creates payment and order objects
     *
     * @param User $user
     * @param Order $order
     * @param PaymentMethod $paymentMethod
     * @param Payment $payment
     * @param array $discounts
     * @param string $currency
     * @param string $brand
     *
     * @return array
     *
     * Returns array of OrderItem
     *
     * @throws Throwable
     */
    public function createAndProcessOrderItems(
        ?User $user,
        Order $order,
        PaymentMethod $paymentMethod,
        Payment $payment,
        array $discounts,
        string $currency,
        string $brand
    ): array
    {
        // todo - deprecated, to be removed
        // order items
        $orderItems = [];

        $cartItems =
            $this->cartService->getCart()
                ->getItems();

        foreach ($cartItems as $key => $cartItem) {
            /**
             * @var $cartItem \Railroad\Ecommerce\Entities\Structures\CartItem
             */
            $expirationDate = null;

            $cartItemProduct = $this->productRepository->findOneBySku($cartItem->getSku());

            if (!$cartItemProduct->getActive()) {
                continue;
            }

            $orderItem = new OrderItem();

            $orderItem->setOrder($order)
                ->setProduct($cartItemProduct)
                ->setQuantity($cartItem->getQuantity())
                ->setWeight($cartItemProduct->getWeight())
                ->setInitialPrice($cartItemProduct->getPrice())
                ->setTotalDiscounted($cartItem->getDiscountAmount())
                ->setFinalPrice(
                    round(
                        $cartItemProduct->getPrice() * $cartItem->getQuantity() - $cartItem->getDiscountAmount(),
                        2
                    )
                )
                ->setCreatedAt(Carbon::now());

            $this->entityManager->persist($orderItem);

            // apply order items discounts
            $orderItem = $this->createOrderItemDiscounts($order, $orderItem, $discounts);

            // create subscription
            if ($cartItemProduct->getType() == ConfigService::$typeSubscription) {

                $subscription = $this->createSubscription(
                    $brand,
                    $cartItemProduct,
                    $order,
                    $cartItem,
                    $discounts,
                    $user,
                    $currency,
                    $paymentMethod,
                    $payment
                );

                $expirationDate = $subscription->getPaidUntil();

                $this->userProductService->assignUserProduct(
                    $user,
                    $cartItemProduct,
                    $expirationDate,
                    $orderItem->getQuantity()
                );
            }

            // product fulfillment
            if ($cartItemProduct->getIsPhysical() == 1) {

                $orderItemFulfillment = new OrderItemFulfillment();

                $orderItemFulfillment->setOrder($order)
                    ->setOrderItem($orderItem)
                    ->setStatus('pending')
                    ->setCreatedAt(Carbon::now());

                $this->entityManager->persist($orderItemFulfillment);
            }

            // add user products
            if ($user && $cartItemProduct->getType() == ConfigService::$typeProduct) {
                $this->userProductService->assignUserProduct($user, $cartItemProduct, null, $orderItem->getQuantity());
            }

            $orderItems[] = $orderItem;
        }

        return $orderItems;
    }

    /**
     * Submit an order.
     *
     * 1: create/get user or customer
     * 2. get cart from request
     * 3. set cart in the cart service
     * 4. get calculated totals from cart service
     * 5. bill the user based on totals
     * 6. create database records
     *
     * @param Request $request
     *
     * @return array
     *
     * @throws Throwable
     */
    public function processOrderFormSubmit(OrderFormSubmitRequest $request): array
    {
        $purchaser = $request->getPurchaser();

        // create and login the user or create the customer
        $this->purchaserService->persist($purchaser);

        // setup the cart
        $cart = $request->getCart();
        $this->cartService->setCart($cart);

        // get the total due
        $paymentAmountInBaseCurrency = $this->cartService->getDueForInitialPayment();

        // try to make the payment
        try {
            $charge = $transactionId = $paymentMethod = $billingAddress = null;

            // use their existing payment method if they chose one
            if (!empty($cart->getPaymentMethodId())) {

                $payment = $this->paymentService->chargeUsersExistingPaymentMethod(
                    $request->get('gateway', config('ecommerce.default_gateway')),
                    $cart->getPaymentMethodId(),
                    $cart->getCurrency(),
                    $paymentAmountInBaseCurrency,
                    $purchaser->getId(),
                    Payment::TYPE_INITIAL_ORDER
                );
            }

            // otherwise make a new payment method
            else {

                // credit cart
                if ($request->get('payment_method_type') == PaymentMethod::TYPE_CREDIT_CARD) {

                    $payment = $this->paymentService->chargeNewCreditCartPaymentMethod(
                        $purchaser,
                        $request->getBillingAddress(),
                        $request->get('gateway', config('ecommerce.default_gateway')),
                        $cart->getCurrency(),
                        $paymentAmountInBaseCurrency,
                        $request->get('card_token'),
                        Payment::TYPE_INITIAL_ORDER,
                        $request->get('set_as_default', true)
                    );

                }

                // paypal
                elseif ($request->get('payment_method_type') == PaymentMethod::TYPE_PAYPAL ||
                    !empty($request->get('token'))) {

                    // if the paypal token is not set we must first redirect to paypal
                    if (empty($request->get('token'))) {

                        $gateway = $request->get('gateway');
                        $config = ConfigService::$paymentGateways['paypal'];
                        $url = $config[$gateway]['paypal_api_checkout_return_url'];

                        $checkoutUrl =
                            $this->payPalPaymentGateway->getBillingAgreementExpressCheckoutUrl($gateway, $url);

                        session()->put('order-form-input', $request->all());

                        return ['redirect' => $checkoutUrl];
                    }

                    $payment = $this->paymentService->chargeNewPayPalPaymentMethod(
                        $purchaser,
                        $request->getBillingAddress(),
                        $request->get('gateway', config('ecommerce.default_gateway')),
                        $cart->getCurrency(),
                        $paymentAmountInBaseCurrency,
                        $request->get('token'),
                        Payment::TYPE_INITIAL_ORDER,
                        $request->get('set_as_default', true)
                    );

                }

                // failure
                else {
                    throw new PaymentFailedException('Payment method not supported.');
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
        } catch (StripeCard $exception) {

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
                }
                else {
                    // assume request not having redirect is json request
                    throw new StripeCardException($exceptionData['error']);
                }
            }

            // throw generic
            throw new PaymentFailedException($exception->getMessage());
        } catch (Exception $paymentFailedException) {

            error_log($paymentFailedException);

            throw new PaymentFailedException($paymentFailedException->getMessage());
        }

        $shippingAddress = $request->getShippingAddress();

        if (!$shippingAddress->getId()) {
            // if a new address entity is used (shipping_address_id not specified in request)
            // user or customer must be linked with shipping address
            if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {
                $user = $purchaser->getUserObject();

                $shippingAddress->setUser($user);
            }
            elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && !empty($purchaser->getEmail())) {
                $customer = $purchaser->getCustomerEntity();

                $shippingAddress->setCustomer($customer);
            }
        }

        $order = $this->orderClaimingService->claimOrder($purchaser, $payment, $cart, $shippingAddress);

        event(new GiveContentAccess($order)); // todo - refactor listeners to order entity param

        //remove all items from the cart
        $this->cartService->clearCart();

        return ['order' => $order];
    }
}
