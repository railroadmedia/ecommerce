<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;
use Railroad\Location\Services\LocationService;


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
     * @var LocationService
     */
    private $locationService;

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
        LocationService $locationService)
    {
        $this->paymentRepository = $paymentRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->locationService = $locationService;
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


}