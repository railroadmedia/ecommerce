<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Exceptions\NotFoundException;
use Railroad\Ecommerce\Exceptions\PayPal\CreateReferenceTransactionException;
use Railroad\Ecommerce\ExternalHelpers\PayPal;
use Railroad\Ecommerce\ExternalHelpers\Stripe;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Location\Services\LocationService;
use Stripe\Error\Card;


class PaymentService
{
    /**
     * @var PaymentRepository
     */
    private $paymentRepository;

    /**
     * @var OrderPaymentRepository
     */
    private $orderPaymentRepository;

    /**
     * @var SubscriptionPaymentRepository
     */
    private $subscriptionPaymentRepository;

    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var LocationService
     */
    private $locationService;

    /**
     * @var Stripe
     */
    private $stripeService;

    /**
     * @var PayPal
     */
    private $payPalService;


    const MANUAL_PAYMENT_TYPE = 'manual';
    const ORDER_PAYMENT_TYPE = 'order';
    const RENEWAL_PAYMENT_TYPE = 'renewal';

    /**
     * PaymentService constructor.
     * @param PaymentRepository $paymentRepository
     * @param OrderPaymentRepository $orderPaymentRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        OrderPaymentRepository $orderPaymentRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        LocationService $locationService,
        PaymentMethodRepository $paymentMethodRepository,
        Stripe $stripe,
        PayPal $payPal)
    {
        $this->paymentRepository = $paymentRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->locationService = $locationService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->stripeService = $stripe;
        $this->payPalService = $payPal;
    }

    /** Create a new payment; link the order/subscription; if the payment method id not exist set the payment type as 'manual' and the status true.
     * Return an array with the new created payment.
     * @param numeric $due
     * @param numeric|null $paid
     * @param numeric|null $refunded
     * @param string|null $type
     * @param string|null $externalProvider
     * @param numeric|null $externalId
     * @param bool $status
     * @param string $message
     * @param numeric|null $paymentMethodId
     * @param numeric|null $orderId
     * @param numeric|null $subscriptionId
     * @return array
     */
    public function store($due, $paid, $refunded, $type, $externalProvider, $externalId, $status = false, $message = '', $paymentMethodId = null, $currency = null, $orderId = null, $subscriptionId = null)
    {
        //check if it's manual
        if (!$paymentMethodId) {
            $externalProvider = self::MANUAL_PAYMENT_TYPE;
            $status = true;
        }

        // if the currency not exist on the request, get the currency with Location package, based on ip address
        if (!$currency) {
            $currency = $this->locationService->getCurrency();
        }

        if (!$subscriptionId) {
            //initial payment
            $paymentMethod = $this->paymentMethodRepository->getById($paymentMethodId);

            if ($paymentMethod['method_type'] == PaymentMethodService::CREDIT_CARD_PAYMENT_METHOD_TYPE) {
                try {
                    $externalId = $this->chargeStripeCreditCardPayment($due, $paymentMethod);
                    $paid = $due;
                    $externalProvider = ConfigService::$creditCard['external_provider'];
                    $status = true;
                    $currency = $paymentMethod['currency'];

                } catch (Exception $e) {
                    $paid = 0;
                    $status = false;
                    $externalProvider = ConfigService::$creditCard['external_provider'];
                    $message = $e->getMessage();
                }

            } else if ($paymentMethod['method_type'] == PaymentMethodService::PAYPAL_PAYMENT_METHOD_TYPE) {
                try {
                    $externalId = $this->chargePayPalReferenceAgreementPayment($due, $paymentMethod);
                    $paid = $due;
                    $externalProvider = 'paypal';
                    $status = true;
                    $currency = $paymentMethod['currency'];

                } catch (Exception $e) {
                    $paid = 0;
                    $status = false;
                    $externalProvider = 'paypal';
                    $message = $e->getMessage();
                }
            }
        }
        $paymentId = $this->paymentRepository->create([
            'due' => $due,
            'paid' => $paid,
            'refunded' => $refunded,
            'type' => $type,
            'external_provider' => $externalProvider,
            'external_id' => $externalId,
            'status' => $status ?? false,
            'message' => $message,
            'payment_method_id' => $paymentMethodId,
            'currency' => $currency,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);

        if ($orderId) {
            $this->createOrderPayment($orderId, $paymentId);
        }

        if ($subscriptionId) {
            $this->createSubscriptionPayment($subscriptionId, $paymentId);
        }

        return $this->getById($paymentId);
    }

    /** Get the payment based on id.
     * @param integer $id
     * @return array
     */
    public function getById($id)
    {
        return $this->paymentRepository->getById($id);
    }

    /** Create a link between payment and order.
     * @param integer $orderId
     * @param integer $paymentId
     */
    private function createOrderPayment($orderId, $paymentId)
    {
        $this->orderPaymentRepository->create([
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);
    }

    /** Create a link between payment and subscription.
     * @param integer $subscriptionId
     * @param integer $paymentId
     */
    private function createSubscriptionPayment($subscriptionId, $paymentId)
    {
        $this->subscriptionPaymentRepository->create([
            'subscription_id' => $subscriptionId,
            'payment_id' => $paymentId,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);
    }

    /**
     * @param $due
     * @param $paymentMethod
     * @throws \Railroad\Ecommerce\ExternalHelpers\CardException
     */
    private function chargeStripeCreditCardPayment($due, $paymentMethod)
    {
        $stripeCustomer = $this->stripeService->retrieveCustomer($paymentMethod['method']['external_customer_id']);
        try {
            $stripeCard = $this->stripeService->retrieveCard(
                $stripeCustomer,
                $paymentMethod['method']['external_id']
            );
        } catch (Exception $e) {
            throw new PaymentErrorException(
                'Payment failed due to an internal error. Please contact support.', 4001
            );
        }

        try {
            $chargeResponse = $this->stripeService->createCharge(
                $due * 100,
                $stripeCustomer,
                $stripeCard,
                $paymentMethod['currency']
            );

        } catch (Card $cardException) {
            throw new PaymentFailedException('Payment failed. ' . $cardException->getMessage());
        }

        return $chargeResponse->id;
    }

    /**
     * @param $amount
     * @param PaymentMethod $paymentMethod
     * @return string
     * @throws PaymentErrorException
     * @throws PaymentFailedException
     */
    private function chargePayPalReferenceAgreementPayment(
        $due,
        $paymentMethod
    ) {
        if (empty($paymentMethod['method']['agreement_id'])) {
            throw new PaymentErrorException(
                'Payment failed due to an internal error. Please contact support.', 4000
            );
        }

        try {
            $payPalTransactionId = $this->payPalService->createReferenceTransaction(
                $due,
                '',
                $paymentMethod['method']['agreement_id']
            );
        } catch (CreateReferenceTransactionException $cardException) {
            throw new NotFoundException(
                'Payment failed. Please make sure your PayPal account is properly funded.'
            );
        }

        return $payPalTransactionId;
    }


}