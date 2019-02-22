<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Railroad\Ecommerce\Entities\Address;
use Railroad\Ecommerce\Entities\CreditCard;
use Railroad\Ecommerce\Entities\Customer;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\PaypalBillingAgreement;
use Railroad\Ecommerce\Entities\OrderDiscount;
use Railroad\Ecommerce\Entities\OrderItem;
use Railroad\Ecommerce\Entities\OrderItemFulfillment;
use Railroad\Ecommerce\Entities\OrderPayment;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Entities\SubscriptionPayment;
use Railroad\Ecommerce\Entities\Structures\Address as SessionAddress;
use Railroad\Ecommerce\Entities\Structures\Cart;
use Railroad\Ecommerce\Entities\Structures\CartItem;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\UnprocessableEntityException;
use Railroad\Ecommerce\Exceptions\StripeCardException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Gateways\StripePaymentGateway;
use Railroad\Ecommerce\Mail\OrderInvoice;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Permissions\Services\PermissionService;
use Railroad\Usora\Entities\User;
use Stripe\Error\Card as StripeCard;

class OrderFormService
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
     * @var EntityManager
     */
    private $entityManager;

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
     * @var mixed UserProviderInterface
     */
    private $userProvider;

    /**
     * @var PermissionService
     */
    private $permissionService;

    /**
     * OrderFormService constructor.
     *
     *
     *
     * @param CartService $cartService
     * @param CartAddressService $cartAddressService
     * @param CurrencyService $currencyService
     * @param EntityManager $entityManager
     * @param PaymentMethodService $paymentMethodService
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PermissionService $permissionService
     * @param StripePaymentGateway $stripePaymentGateway
     */
    public function __construct(
        CartService $cartService,
        CartAddressService $cartAddressService,
        CurrencyService $currencyService,
        EntityManager $entityManager,
        PaymentMethodService $paymentMethodService,
        PayPalPaymentGateway $payPalPaymentGateway,
        PermissionService $permissionService,
        StripePaymentGateway $stripePaymentGateway
    ) {
        $this->cartService = $cartService;
        $this->cartAddressService = $cartAddressService;
        $this->currencyService = $currencyService;
        $this->entityManager = $entityManager;
        $this->paymentMethodService = $paymentMethodService;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->permissionService = $permissionService;
        $this->stripePaymentGateway = $stripePaymentGateway;
        $this->userProvider = app()->make('UserProviderInterface');
    }

    // tmp method
    /**
     * @param OrderFormSubmitRequest $request
     *
     * @return User
     */
    public function getUserReference($userId)
    {
        return $this->entityManager
                        ->getReference(User::class, $userId);
    }

    /**
     * @param OrderFormSubmitRequest $request
     * @param User $user
     * @param Customer $customer
     * @param float $initialPrice
     * @param string $currency
     *
     * @return array
     *
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    private function chargeAndCreatePaymentMethod(
        OrderFormSubmitRequest $request,
        ?User $user,
        ?Customer $customer,
        $initialPrice,
        $currency
    ) : array {

        // todo DEVE-31 - add taxes
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
            $initialPrice, // todo - review
            $currency,
            $card,
            $customerCreditCard
        );

        $billingAddress = new Address();

        $billingAddress
            ->setType(CartAddressService::BILLING_ADDRESS_TYPE)
            ->setBrand(ConfigService::$brand)
            ->setUser($user)
            ->setCustomer($customer)
            ->setZip($request->get('billing-zip-or-postal-code'))
            ->setState($request->get('billing-region'))
            ->setCountry($request->get('billing-country'))
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($billingAddress);

        $this->entityManager->flush();

        $paymentMethod = $this->paymentMethodService->createUserCreditCard(
            $user,
            $card->fingerprint,
            $card->last4,
            '',
            $card->brand,
            $card->exp_year,
            $card->exp_month,
            $card->id,
            $card->customer,
            $request->get('gateway'),
            $customer
            $billingAddress,
            $currency,
            false,
        );

        return [$charge, $paymentMethod, $billingAddress];
    }

    /**
     * @param Request $request
     * @param float $price
     * @param string $currency
     * @param User $user
     *
     * @return array
     *
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    private function transactionAndCreatePaymentMethod(
        Request $request,
        $price,
        $currency,
        User $user
    ) : array {

        $billingAgreementId = $this->payPalPaymentGateway->createBillingAgreement(
            $request->get('gateway'),
            $price,
            $currency,
            $request->get('token')
        );

        $transactionId = $this->payPalPaymentGateway->chargeBillingAgreement(
            $request->get('gateway'),
            $price,
            $currency,
            $billingAgreementId
        );

        $billingAddress = new Address();

        $billingAddress
            ->setType(CartAddressService::BILLING_ADDRESS_TYPE)
            ->setBrand(ConfigService::$brand)
            ->setUser($user)
            ->setZip($request->get('billing-zip-or-postal-code'))
            ->setState($request->get('billing-region'))
            ->setCountry($request->get('billing-country'))
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($billingAddress);

        $this->entityManager->flush();

        $paymentMethod = $this->paymentMethodService->createPayPalBillingAgreement(
            $user,
            $billingAgreementId,
            $billingAddress,
            $request->get('gateway'),
            $currency,
            false
        );

        return [$transactionId, $paymentMethod, $billingAddress];
    }

    /**
     * Re-charge an existing credit card payment method
     *
     * @param OrderFormSubmitRequest $request
     * @param PaymentMethod $paymentMethod
     * @param float $initialPrice
     * @param string $currency
     *
     * @return mixed
     *
     * @throws \Railroad\Ecommerce\Exceptions\PaymentFailedException
     */
    private function rechargeCreditCard(
        OrderFormSubmitRequest $request,
        PaymentMethod $paymentMethod,
        $initialPrice,
        $currency
    ) {

        $creditCard = $this->entityManager
                            ->getRepository(CreditCard::class)
                            ->find($paymentMethod->getMethodId());

        $customer = $this->stripePaymentGateway->getCustomer(
            $request->get('gateway'),
            $creditCard->getExternalCustomerId()
        );

        if (!$customer) {
            return null;
        }

        $card = $this->stripePaymentGateway->getCard(
            $customer,
            $creditCard->getExternalId(),
            $request->get('gateway')
        );

        if (!$card) {
            return null;
        }

        $charge = $this->stripePaymentGateway->chargeCustomerCard(
            $request->get('gateway'),
            $initialPrice,
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
        $initialPrice,
        $currency
    ) {

        $paypalAgreement = $this->entityManager
                            ->getRepository(PaypalBillingAgreement::class)
                            ->find($paymentMethod->getMethodId());

        return $this->payPalPaymentGateway->chargeBillingAgreement(
            $request->get('gateway'),
            $initialPrice,
            $currency,
            $paypalAgreement->getExternalId()
        );
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    private function createOrderDiscounts(Order $order)
    {
        /**
         * @var $cart \Railroad\Ecommerce\Entities\Strctures\Cart
         */
        $cart = $this->cartService->getCart();

        foreach ($cart->getDiscounts() as $discount) {

            /**
             * @var $discount \Railroad\Ecommerce\Entities\Discount
             */

            $orderDiscount = new OrderDiscount();

            $orderDiscount
                ->setOrder($order)
                ->setDiscount($discount)
                ->setCreatedAt(Carbon::now());

            $this->entityManager->persist($orderDiscount);
        }

        $this->entityManager->flush();

        return true;
    }

    /**
     * @param CartItem $cartItem
     * @param Order $order
     * @param OrderItem $orderItem
     *
     * @return OrderItem
     */
    private function createOrderItemDiscounts(
        CartItem $cartItem,
        Order $order,
        OrderItem $orderItem
    ): OrderItem {
        if (!empty($cartItem->getAppliedDiscounts())) {
            foreach ($cartItem->getAppliedDiscounts() as $discount) {

                /**
                 * @var $discount \Railroad\Ecommerce\Entities\Discount
                 */

                $orderDiscount = new OrderDiscount();

                $orderDiscount
                    ->setOrder($order)
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
     * @param float $due
     * @param mixed $charge
     * @param $transactionId
     * @param PaymentMethod $paymentMethod
     * @param $currency
     *
     * @return Payment
     */
    private function createPayment(
        $paid,
        $due,
        $charge,
        $transactionId,
        PaymentMethod $paymentMethod,
        $currency
    ): Payment {

        $externalProvider = isset($charge['id']) ? 'stripe' : 'paypal';

        $payment = new Payment();

        $payment
            ->setTotalDue($due)
            ->setTotalPaid($paid)
            ->setTotalRefunded(0)
            ->setType('order')
            ->setExternalId($charge['id'] ?? $transactionId)
            ->setExternalProvider($externalProvider)
            ->setStatus('paid')
            ->setMessage('')
            ->setPaymentMethod($paymentMethod)
            ->setCurrency($currency)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($payment);

        $this->entityManager->flush();

        return $payment;
    }

    /**
     * @param Request $request
     * @param float $paid
     * @param float $shipping
     * @param float $totalDue
     * @param float $totalTax
     * @param User $user
     * @param Customer $customer
     * @param Address $billingAddress
     * @param Payment $payment
     * @param string $brand
     *
     * @return Order
     *
     * @throws UnprocessableEntityException
     */
    private function createOrder(
        Request $request,
        $paid,
        $shipping,
        $totalDue,
        $totalTax,
        ?User $user,
        ?Customer $customer,
        Address $billingAddress,
        Payment $payment,
        $brand
    ): Order {

        $shippingAddress = null;

        if ($request->get('shipping-address-id')) {

            $shippingAddress = $this->entityManager
                                    ->getRepository(Address::class)
                                    ->find($request->get('shipping-address-id'));

            $message = 'Order failed. Error message: could not find shipping address id: ' .
                $request->get('shipping-address-id');

            throw_if(
                !($shippingAddress),
                new UnprocessableEntityException($message)
            );

        } else {
            //save the shipping address
            $shippingAddress = new Address();

            $shippingAddress
                ->setType(ConfigService::$shippingAddressType)
                ->setBrand(ConfigService::$brand)
                ->setUser($user)
                ->setCustomer($customer)
                ->setFirstName($request->get('shipping-first-name'))
                ->setLastName($request->get('shipping-last-name'))
                ->setStreetLine1($request->get('shipping-address-line-1'))
                ->setStreetLine2($request->get('shipping-address-line-2'))
                ->setCity($request->get('shipping-city'))
                ->setZip($request->get('shipping-zip-or-postal-code'))
                ->setState($request->get('shipping-region'))
                ->setCountry($request->get('shipping-country'))
                ->setCreatedAt(Carbon::now());

            $this->entityManager->persist($shippingAddress);
        }

        $order = new Order();

        $order
            ->setTotalDue($totalDue)
            ->setTaxesDue($totalTax)
            ->setTotalPaid($paid)
            ->setBrand($brand)
            ->setUser($user)
            ->setCustomer($customer)
            ->setShippingDue($shipping)
            ->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress)
            ->setCreatedAt(Carbon::now());

        $orderPayment = new OrderPayment()

        $orderPayment
            ->setOrder($order)
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
     * @param User $user
     * @param string $currency
     * @param PaymentMethod $paymentMethod
     * @param Payment $payment
     * @param $applyDiscounts
     * @param int $totalCyclesDue
     *
     * @return Subscription
     *
     * @throws \Railroad\Ecommerce\Exceptions\UnprocessableEntityException
     */
    private function createSubscription(
        $brand,
        ?Product $product = null,
        Order $order,
        ?CartItem $cartItem,
        User $user,
        $currency,
        PaymentMethod $paymentMethod,
        Payment $payment,
        $applyDiscounts = false, // todo - review & remove
        $totalCyclesDue = null
    ): Subscription {
        $type = ConfigService::$typeSubscription;

        // if the product it's not defined we should create a payment plan.
        // Define payment plan next bill date, price per payment and tax per payment.

        if (is_null($product)) {

            $nextBillDate = Carbon::now()->addMonths(1);

            $type = ConfigService::$paymentPlanType;

            $subscriptionPricePerPayment = $this->cartService->getCart()
                                            ->calculatePricePerPayment();

            $totalTaxSplitedPerPaymentPlan = $this->cartService->getCart()
                                    ->calculateTaxesDue() / $totalCyclesDue; // todo - review

        } else {

            if (!empty($product->getSubscriptionIntervalType())) {
                if ($product->getSubscriptionIntervalType() == ConfigService::$intervalTypeMonthly) {
                    $nextBillDate = Carbon::now()
                            ->addMonths(
                                $product->getSubscriptionIntervalCount()
                            );

                } elseif ($product->getSubscriptionIntervalType() == ConfigService::$intervalTypeYearly) {
                    $nextBillDate = Carbon::now()
                            ->addYears(
                                $product->getSubscriptionIntervalCount()
                            );

                } elseif ($product->getSubscriptionIntervalType() == ConfigService::$intervalTypeDaily) {
                    $nextBillDate = Carbon::now()
                            ->addDays(
                                $product->getSubscriptionIntervalCount()
                            );
                }
            } else {
                $message = 'Failed to create subscription for order id: ';
                $message .= $order['id'];
                throw new UnprocessableEntityException($message);
            }
        }

        if ($cartItem) {
            foreach ($cartItem->getAppliedDiscounts() as $discount) {
                /**
                 * @var $discount \Railroad\Ecommerce\Entities\Discount
                 */

                if ($discount->getType() == DiscountService::SUBSCRIPTION_FREE_TRIAL_DAYS_TYPE) {
                    //add the days from the discount to the subscription next bill date
                    $nextBillDate = $nextBillDate->addDays(
                        $discount->getAmount()
                    );

                } elseif ($discount->getType() == DiscountService::SUBSCRIPTION_RECURRING_PRICE_AMOUNT_OFF_TYPE) {
                    //calculate subscription price per payment after discount
                    $subscriptionPricePerPayment = $cartItem->getPrice() - $discount->getAmount();
                }
            }
        }

        $subscription = new Subscription();

        $subscription
            ->setBrand($brand)
            ->setType($type)
            ->setUser($user)
            ->setOrder($order)
            ->setProduct($product)
            ->setIsActive(true)
            ->setStartDate(Carbon::now())
            ->setPaidUntil($nextBillDate)
            ->setTotalPrice($subscriptionPricePerPayment ?? $cartItem->getPrice())
            ->setCurrency($currency)
            ->setIntervalType($product->getSubscriptionIntervalType() ?? ConfigService::$intervalTypeMonthly)
            ->setIntervalCount($product->getSubscriptionIntervalCount() ?? 1)
            ->setTotalCyclesPaid(1)
            ->setTotalCyclesDue($totalCyclesDue)
            ->setPaymentMethod($paymentMethod)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($subscription);

        $subscriptionPayment = new SubscriptionPayment();

        $subscriptionPayment
            ->setSubscription($subscription)
            ->setPayment($payment)
            ->setCreatedAt(Carbon::now());

        $this->entityManager->persist($subscriptionPayment);

        $this->entityManager->flush();

        return $subscription;
    }

    /**
     * Submit an order
     *
     * @param Request $request
     *
     * @return array
     */
    public function processOrderForm(Request $request): array
    {
        $user = auth()->user() ?? null;
        $brand = ConfigService::$brand;

        if (
            $this->permissionService->can(
                auth()->id(),
                'place-orders-for-other-users'
            )
        ) {
            // todo - refactor
            $user = $this->getUserReference($request->get('user_id'));

            $brand = $request->get('brand', ConfigService::$brand);
        }

        $this->cartService->setBrand($brand);

        if (!empty($request->get('token'))) {
            $orderFormInput = session()->get('order-form-input', []);
            unset($orderFormInput['token']);
            session()->forget('order-form-input');
            $request->merge($orderFormInput);
        }

        $currency = $request->get('currency', $this->currencyService->get());

        if (!empty($request->get('account-creation-email')) && empty($user)) {
            $user = $this->userProvider->create( // todo - review/refactor
                $request->get('account-creation-email'),
                $request->get('account-creation-password')
            );
        }

        // save customer if billing email exists on request
        if ($request->has('billing-email')) {

            $customer = new Customer();

            $customer
                ->setEmail($request->get('billing-email'))
                ->setBrand($brand)
                ->setCreatedAt(Carbon::now());

            $this->entityManager->persist($customer);
        }

        // set the shipping address on session
        $sessionShippingAddress = new SessionAddress();

        $sessionShippingAddress
            ->setFirstName($request->get('shipping-first-name'))
            ->setLastName($request->get('shipping-last-name'))
            ->setStreetLineOne($request->get('shipping-address-line-1'))
            ->setStreetLineTwo($request->get('shipping-address-line-2'))
            ->setZipOrPostalCode($request->get('shipping-zip-or-postal-code'))
            ->setCity($request->get('shipping-city'))
            ->setState($request->get('shipping-region'))
            ->setCountry($request->get('shipping-country'));

        $this->cartAddressService->setAddress(
            $sessionShippingAddress,
            ConfigService::$shippingAddressType
        );

        $this->cartService->calculateShippingCosts();

        $this->cartService->getCart()->removeAppliedDiscount();

        $discountsToApply = $this->cartService->getDiscountsToApply();

        $this->cartService->getCart()->setDiscounts($discountsToApply);
        $this->cartService->getCart()->setAppliedDiscounts($discountsToApply);

        $this->cartService->applyDiscounts();

        // set the shipping address on session
        $sessionBillingAddress = new SessionAddress();

        $sessionBillingAddress
            ->setCountry($request->get('billing-country'))
            ->setState($request->get('billing-region'))
            ->setZipOrPostalCode($request->get('billing-zip-or-postal-code'));

        $this->cartAddressService->setAddress(
            $sessionBillingAddress,
            CartAddressService::BILLING_ADDRESS_TYPE
        );

        $this->cartService->setPaymentPlanNumberOfPayments(
            $request->get('payment-plan-selector')
        );

        $billingAddressDB = null;

        // try to make the payment
        try {
            if ($request->get('payment-method-id')) {

                $paymentMethod = $this->entityManager
                                        ->getRepository(PaymentMethod::class)
                                        ->find($request->get('payment-method-id'));

                if (
                    !$paymentMethod ||
                    !$paymentMethod->getUser() ||
                    $paymentMethod->getUser()->getId() != $user->getId()
                ) {

                    $url = $request->get('redirect') ?? strtok(app('url')->previous(), '?');
                    return [
                        'redirect' => $url,
                        'errors' => [
                            'payment' => 'Invalid Payment Method',
                        ],
                    ];
                }

                $charge = $transactionId = null;

                if ($paymentMethod->getMethodType() == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
                    $charge = $this->rechargeCreditCard(
                        $request,
                        $paymentMethod,
                        $this->cartService->getCart()
                            ->calculateInitialPricePerPayment(),
                        $currency
                    );
                } else {
                    $transactionId = $this->rechargeAgreement(
                        $request,
                        $paymentMethod,
                        $this->cartService->getCart()
                            ->calculateInitialPricePerPayment(),
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

                $billingAddress = $paymentMethod->getBillingAddress();

            } else {

                if (
                    $request->get('payment_method_type') == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE &&
                    empty($request->get('token'))
                ) {
                    list(
                        $charge, $paymentMethod, $billingAddress
                    ) = $this->chargeAndCreatePaymentMethod(
                        $request,
                        $user,
                        $customer ?? null,
                        $this->cartService->getCart()
                            ->calculateInitialPricePerPayment(),
                        $currency
                    );

                } elseif (
                    $request->get('payment_method_type') == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE ||
                    !empty($request->get('token'))
                ) {
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
                        $transactionId, $paymentMethod, $billingAddress
                    ) = $this->transactionAndCreatePaymentMethod(
                        $request,
                        $this->cartService->getCart()
                            ->calculateInitialPricePerPayment(),
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
                } else {
                    // assume request not having redirect is json request
                    throw new StripeCardException($exceptionData['error']);
                }
            }

            // throw generic
            throw new PaymentFailedException($exception->getMessage());
        } catch (Exception $paymentFailedException) {

            throw new PaymentFailedException($paymentFailedException->getMessage());
        }

        // create Payment
        $payment = $this->createPayment(
            $this->cartService->getCart()->calculateInitialPricePerPayment(),
            $this->cartService->getCart()->getTotalDue(),
            $charge ?? null,
            $transactionId ?? null,
            $paymentMethod,
            $currency
        );

        // create order
        $order = $this->createOrder(
            $request,
            $payment['paid'],
            $this->cartService->getCart()->calculateShippingDue(),
            $this->cartService->getCart()->getTotalDue(),
            $this->cartService->getCart()->calculateTaxesDue(),
            $user ?? null,
            $customer ?? null,
            $billingAddress,
            $payment,
            $brand
        );

        // create payment plan
        $paymentPlanNumbersOfPayments = $this->cartService->getCart()
                ->getPaymentPlanNumberOfPayments();

        // apply order discounts
        $this->createOrderDiscounts($order);

        // order items
        $orderItems = [];

        $cartItems = $this->cartService->getCart()->getItems();

        foreach ($cartItems as $key => $cartItem) {

            $expirationDate = null;

            if (!$cartItem->getProduct()['active']) {
                continue;
            }

            $orderItem = new OrderItem();

            $orderItem
                ->setOrder($order)
                ->setProduct($cartItem->getProduct())
                ->setQuantity($cartItem->getQuantity())
                ->setInitialPrice($cartItem->getPrice())
                ->setTotalDiscounted($cartItem->getDiscountedPrice() ?? 0)
                ->setFinalPrice($cartItem->getDiscountedPrice() ?? $cartItem->getTotalPrice())
                ->setCreatedAt(Carbon::now());

            $this->entityManager->persist($orderItem);

            // apply order items discounts
            $orderItem = $this->createOrderItemDiscounts(
                $cartItem,
                $order,
                $orderItem
            );

            $cartItemProduct = $cartItem->getProduct();

            // create subscription
            if (
                $cartItemProduct->getType() == ConfigService::$typeSubscription
            ) {

                $subscription = $this->createSubscription(
                    $brand,
                    $cartItem->getProduct(),
                    $order,
                    $cartItem,
                    $user,
                    $currency,
                    $paymentMethod,
                    $payment,
                    true
                );

                $expirationDate = $subscription->getPaidUntil();
            }

            // product fulfillment
            if ($cartItemProduct->getIsPhysical() == 1) {

                $orderItemFulfillment = new OrderItemFulfillment();

                $orderItemFulfillment
                    ->setOrder($order)
                    ->setOrderItem($orderItem)
                    ->setStatus('pending')
                    ->setCreatedAt(Carbon::now());

                $this->entityManager->persist($orderItemFulfillment);
            }

            $orderItems[] = $orderItem;
        }

        if ($paymentPlanNumbersOfPayments > 1) {
            $this->createSubscription(
                $this->cartService->getCart()->getBrand(),
                null,
                $order,
                null,
                $user,
                $currency,
                $paymentMethod,
                $payment,
                false,
                $paymentPlanNumbersOfPayments
            );
        }

        // if the order failed; we throw the proper exception
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
            // prepare the order invoice
            $orderInvoiceEmail = new OrderInvoice(
                [
                    'order' => $order,
                    'orderItems' => $orderItems,
                    'payment' => $payment,
                    'currencySymbol' => $currencySymbol,
                ]
            );

            $emailAddress = $user->getEmail() ?? $customer->getEmail();

            Mail::to($emailAddress)->send($orderInvoiceEmail);

        } catch (Exception $e) {
            error_log('Failed to send invoice for order: ' . $order->getId());
        }

        event(new GiveContentAccess($order)); // todo - refactor listeners to order entity param

        //remove all items from the cart
        $this->cartService->removeAllCartItems();

        return ['order' => $order];
    }
}
