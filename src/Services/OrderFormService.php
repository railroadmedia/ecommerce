<?php

namespace Railroad\Ecommerce\Services;

use Exception;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Entities\Subscription;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Events\PaymentEvent;
use Railroad\Ecommerce\Exceptions\Cart\ProductOutOfStockException;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\RedirectNeededException;
use Railroad\Ecommerce\Exceptions\StripeCardException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\ProductRepository;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
use Railroad\Referral\Models\Referrer;
use Railroad\Referral\Services\SaasquatchService;
use Stripe\Error\Card as StripeCard;
use Throwable;

class OrderFormService
{
    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var OrderClaimingService
     */
    private $orderClaimingService;

    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var PayPalPaymentGateway
     */
    private $payPalPaymentGateway;

    /**
     * @var PurchaserService
     */
    private $purchaserService;

    /**
     * @var ShippingService
     */
    private $shippingService;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var PaymentRepository
     * */
    private $paymentRepository;

    /**
     * @var OrderValidationService
     */
    private $orderValidationService;

    /**
     * OrderFormService constructor.
     *
     * @param CartService $cartService
     * @param OrderClaimingService $orderClaimingService
     * @param PaymentService $paymentService
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PurchaserService $purchaserService
     * @param ShippingService $shippingService
     * @param PaymentMethodRepository $paymentMethodRepository
     * @param OrderValidationService $orderValidationService
     * @param PaymentRepository $paymentRepository
     */
    public function __construct(
        CartService $cartService,
        OrderClaimingService $orderClaimingService,
        PaymentService $paymentService,
        PayPalPaymentGateway $payPalPaymentGateway,
        PurchaserService $purchaserService,
        ShippingService $shippingService,
        PaymentMethodRepository $paymentMethodRepository,
        OrderValidationService $orderValidationService,
        PaymentRepository $paymentRepository,
    )
    {
        $this->cartService = $cartService;
        $this->orderClaimingService = $orderClaimingService;
        $this->paymentService = $paymentService;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->purchaserService = $purchaserService;
        $this->shippingService = $shippingService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->orderValidationService = $orderValidationService;
        $this->paymentRepository = $paymentRepository;
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
     * @param OrderFormSubmitRequest $request
     *
     * @return array
     *
     * @throws Throwable
     */
    public function processOrderFormSubmit(OrderFormSubmitRequest $request): array
    {
        try {
            // setup the cart
            $cart = $request->getCart();
            // check if cart items are still in stock
            $this->cartService->checkProductsStock($cart);
        } catch (ProductOutOfStockException $productOutOfStockException) {
            $url = $request->get('redirect') ?? strtok(app('url')->previous(), '?');

            return [
                'redirect' => $url,
                'errors' => [
                    'out-of-stock' => $productOutOfStockException->getMessage()
                ],
            ];
        }

        try {
            $payment = null;
            $this->cartService->setCart($cart);

            $purchaser = $request->getPurchaser();

            $paymentAmountInBaseCurrency = $this->cartService->getDueForInitialPayment();

            // order validation / trial spam prevention
            $this->orderValidationService->validateOrder($cart, $purchaser);

            // if its a credit card we must validate inside the gateway to avoid spam bot accounts
            if ($request->get('payment_method_type') == PaymentMethod::TYPE_CREDIT_CARD &&
                empty($request->get('payment_method_id')) &&
                empty(auth()->user())) {
                $purchaser =
                    $this->paymentService->validateCard(
                        $purchaser,
                        $request->get('gateway', config('ecommerce.default_gateway')),
                        $request->get('card_token')
                    );
            }

            // create and login the user or create the customer
            $this->purchaserService->persist($purchaser);


            if ($purchaser->getType() == Purchaser::USER_TYPE) {
                DiscountCriteriaService::setPurchaser($purchaser->getUserObject());
            }

            // get the total due
            $paymentAmountInBaseCurrency = $this->cartService->getDueForInitialPayment();

            // if the paypal token is not set we must first redirect to paypal
            if (
                empty($cart->getPaymentMethodId())
                && $request->get('payment_method_type') == PaymentMethod::TYPE_PAYPAL
                && empty($request->get('token'))
            ) {
                $gateway = $request->get('gateway');
                $config = config('ecommerce.payment_gateways')['paypal'];
                $url = route($config[$gateway]['paypal_api_checkout_return_route']);

                $checkoutUrl =
                    $this->payPalPaymentGateway->getBillingAgreementExpressCheckoutUrl($gateway, $url);

                session()->put('order-form-input', $request->all());

                return ['redirect' => $checkoutUrl];
            }

            $paymentMethod = null;

            // try to make the payment

            if (empty($cart->getPaymentMethodId()) &&
                $request->get('payment_method_type') != PaymentMethod::TYPE_CREDIT_CARD &&
                $request->get('payment_method_type') != PaymentMethod::TYPE_PAYPAL &&
                $paymentAmountInBaseCurrency != 0) {
                throw new PaymentFailedException('Payment method not supported.');
            }

            // if its free, dont create a payment
            if ($paymentAmountInBaseCurrency == 0) {

                if (empty($cart->getPaymentMethodId())) {
                    if ($request->get('payment_method_type') == PaymentMethod::TYPE_CREDIT_CARD) {
                        $paymentMethod = $this->paymentService->createCreditCardPaymentMethod(
                            $purchaser,
                            $request->getBillingAddress(),
                            $request->get('gateway', config('ecommerce.default_gateway')),
                            $cart->getCurrency(),
                            $request->get('card_token'),
                            $request->get('set_as_default', false)
                        );
                    } elseif (!empty($request->get('token'))) {
                        $paymentMethod = $this->paymentService->createPayPalPaymentMethod(
                            $purchaser,
                            $request->getBillingAddress(),
                            $request->get('gateway', config('ecommerce.default_gateway')),
                            $cart->getCurrency(),
                            $request->get('token'),
                            $request->get('set_as_default', false)
                        );
                    }
                } else {
                    $paymentMethod = $this->paymentMethodRepository->byId($cart->getPaymentMethodId());
                }
            } // use their existing payment method if they chose one
            elseif (!empty($cart->getPaymentMethodId())) {

                if ($purchaser->getType() == Purchaser::USER_TYPE) {
                    $payment = $this->paymentService->chargeUsersExistingPaymentMethod(
                        $cart->getPaymentMethodId(),
                        $cart->getCurrency(),
                        $paymentAmountInBaseCurrency,
                        $purchaser->getId(),
                        Payment::TYPE_INITIAL_ORDER
                    );
                } elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE) {
                    $payment = $this->paymentService->chargeCustomersExistingPaymentMethod(
                        $cart->getPaymentMethodId(),
                        $cart->getCurrency(),
                        $paymentAmountInBaseCurrency,
                        $purchaser->getId(),
                        Payment::TYPE_INITIAL_ORDER
                    );
                }

                $paymentMethod = $payment->getPaymentMethod();
            } // otherwise make a new payment method
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
                        $request->get('set_as_default', false)
                    );

                } // paypal
                else {

                    $payment = $this->paymentService->chargeNewPayPalPaymentMethod(
                        $purchaser,
                        $request->getBillingAddress(),
                        $request->get('gateway', config('ecommerce.default_gateway')),
                        $cart->getCurrency(),
                        $paymentAmountInBaseCurrency,
                        $request->get('token'),
                        Payment::TYPE_INITIAL_ORDER,
                        $request->get('set_as_default', false)
                    );

                }

                $paymentMethod = $payment->getPaymentMethod();
            }
        } catch (RedirectNeededException $redirectNeededException) {

            return [
                'redirect-with-message' => true,
                'redirect-needed-exception' => $redirectNeededException,
            ];

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
                    // assume request having redirect is aware and able to process stripe session errors
                    return [
                        'redirect' => $url,
                        'errors' => [
                            ['stripe' => $exceptionData['error']],
                        ],
                    ];
                } else {
                    // assume request not having redirect is json request
                    throw new StripeCardException($exceptionData['error']['message']);
                }
            }

            // throw generic
            throw new PaymentFailedException($exception->getMessage());
        } catch (Exception $paymentFailedException) {

            error_log($paymentFailedException);

            throw new PaymentFailedException($paymentFailedException->getMessage());
        }

        $shippingAddress = null;

        if ($this->shippingService->doesCartHaveAnyPhysicalItems($cart)) {

            $shippingAddress = $request->getShippingAddress();

            if (!$shippingAddress->getId()) {
                // if a new address entity is used (shipping_address_id not specified in request)
                // user or customer must be linked with shipping address
                if ($purchaser->getType() == Purchaser::USER_TYPE && !empty($purchaser->getId())) {
                    $user = $purchaser->getUserObject();

                    $shippingAddress->setUser($user);
                } elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && !empty($purchaser->getEmail())) {
                    $customer = $purchaser->getCustomerEntity();

                    $shippingAddress->setCustomer($customer);
                }

                $shippingAddress->setBrand($purchaser->getBrand());
            }
        }

        $order = $this->orderClaimingService->claimOrder($purchaser, $paymentMethod, $payment, $cart, $shippingAddress);

        if ($payment && user()) {
            // in some situations $payment->getOrder() is null or $order->getPayments() is empty
            $payment = $this->paymentRepository->find($payment->getId());
            foreach ($order->getPayments() as $orderPayment) {
                if ($orderPayment->getId() == $payment->getId()) {
                    $payment = $orderPayment;
                }
            }
            event(new PaymentEvent($payment));
        }

        event(new GiveContentAccess($order));

        //remove all items from the cart
        $this->cartService->clearCart();

        return ['order' => $order];
    }
}
