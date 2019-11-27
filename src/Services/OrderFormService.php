<?php

namespace Railroad\Ecommerce\Services;

use Exception;
use Railroad\Ecommerce\Contracts\UserProviderInterface;
use Railroad\Ecommerce\Entities\Payment;
use Railroad\Ecommerce\Entities\PaymentMethod;
use Railroad\Ecommerce\Entities\Structures\Purchaser;
use Railroad\Ecommerce\Events\GiveContentAccess;
use Railroad\Ecommerce\Exceptions\PaymentFailedException;
use Railroad\Ecommerce\Exceptions\StripeCardException;
use Railroad\Ecommerce\Gateways\PayPalPaymentGateway;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Requests\OrderFormSubmitRequest;
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
     * @var UserProviderInterface
     */
    private $userProvider;
    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * OrderFormService constructor.
     *
     * @param CartService $cartService
     * @param OrderClaimingService $orderClaimingService
     * @param PaymentService $paymentService
     * @param PayPalPaymentGateway $payPalPaymentGateway
     * @param PurchaserService $purchaserService
     * @param ShippingService $shippingService
     * @param UserProviderInterface $userProvider
     * @param PaymentMethodRepository $paymentMethodRepository
     */
    public function __construct(
        CartService $cartService,
        OrderClaimingService $orderClaimingService,
        PaymentService $paymentService,
        PayPalPaymentGateway $payPalPaymentGateway,
        PurchaserService $purchaserService,
        ShippingService $shippingService,
        UserProviderInterface $userProvider,
        PaymentMethodRepository $paymentMethodRepository
    )
    {
        $this->cartService = $cartService;
        $this->orderClaimingService = $orderClaimingService;
        $this->paymentService = $paymentService;
        $this->payPalPaymentGateway = $payPalPaymentGateway;
        $this->purchaserService = $purchaserService;
        $this->shippingService = $shippingService;
        $this->userProvider = $userProvider;
        $this->paymentMethodRepository = $paymentMethodRepository;
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
        $purchaser = $request->getPurchaser();

        // create and login the user or create the customer
        $this->purchaserService->persist($purchaser);

        if ($purchaser->getType() == Purchaser::USER_TYPE) {
            DiscountCriteriaService::setPurchaser($purchaser->getUserObject());
        }

        // setup the cart
        $cart = $request->getCart();
        $this->cartService->setCart($cart);

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
        try {

            if (empty($cart->getPaymentMethodId()) &&
                $request->get('payment_method_type') != PaymentMethod::TYPE_CREDIT_CARD &&
                $request->get('payment_method_type') != PaymentMethod::TYPE_PAYPAL &&
                $paymentAmountInBaseCurrency != 0) {
                throw new PaymentFailedException('Payment method not supported.');
            }

            // if its free, dont create a payment
            if ($paymentAmountInBaseCurrency == 0) {
                $payment = null;

                if (empty($cart->getPaymentMethodId())) {
                    if ($request->get('payment_method_type') == PaymentMethod::TYPE_CREDIT_CARD) {
                        $paymentMethod = $this->paymentService->createCreditCartPaymentMethod(
                            $purchaser,
                            $request->getBillingAddress(),
                            $request->get('gateway', config('ecommerce.default_gateway')),
                            $cart->getCurrency(),
                            $request->get('card_token'),
                            $request->get('set_as_default', true)
                        );
                    } else {
                        $paymentMethod = $this->paymentService->createPayPalPaymentMethod(
                            $purchaser,
                            $request->getBillingAddress(),
                            $request->get('gateway', config('ecommerce.default_gateway')),
                            $cart->getCurrency(),
                            $request->get('token'),
                            $request->get('set_as_default', true)
                        );
                    }
                } else {
                    $paymentMethod = $this->paymentMethodRepository->byId($cart->getPaymentMethodId());
                }
            }

            // use their existing payment method if they chose one
            elseif (!empty($cart->getPaymentMethodId())) {

                $payment = $this->paymentService->chargeUsersExistingPaymentMethod(
                    $cart->getPaymentMethodId(),
                    $cart->getCurrency(),
                    $paymentAmountInBaseCurrency,
                    $purchaser->getId(),
                    Payment::TYPE_INITIAL_ORDER
                );

                $paymentMethod = $payment->getPaymentMethod();
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
                else {

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

                $paymentMethod = $payment->getPaymentMethod();
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
                    // assume request having redirect is aware and able to process stripe session errors
                    return [
                        'redirect' => $url,
                        'errors' => [
                            ['stripe' => $exceptionData['error']],
                        ],
                    ];
                }
                else {
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
                }
                elseif ($purchaser->getType() == Purchaser::CUSTOMER_TYPE && !empty($purchaser->getEmail())) {
                    $customer = $purchaser->getCustomerEntity();

                    $shippingAddress->setCustomer($customer);
                }

                $shippingAddress->setBrand($purchaser->getBrand());
            }
        }

        $order = $this->orderClaimingService->claimOrder($purchaser, $paymentMethod, $payment, $cart, $shippingAddress);

        event(new GiveContentAccess($order));

        //remove all items from the cart
        $this->cartService->clearCart();

        return ['order' => $order];
    }
}
