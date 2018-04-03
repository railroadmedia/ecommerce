<?php

namespace Railroad\Ecommerce\Services;


use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentRepository;
use Railroad\Ecommerce\Repositories\SubscriptionPaymentRepository;


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
        SubscriptionPaymentRepository $subscriptionPaymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
    }


    public function store($due, $paid, $refunded, $type, $externalProvider, $externalId, $status = false, $message = '', $paymentMethodId = null, $orderId = null, $subscriptionId = null)
    {
        //check if it's manual
        if (!$paymentMethodId) {
            $externalProvider = self::MANUAL_PAYMENT_TYPE;
            $status = true;
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

    public function getById($id)
    {
        return $this->paymentRepository->getById($id);
    }

    /**
     * @param $orderId
     * @param $paymentId
     */
    private function createOrderPayment($orderId, $paymentId)
    {
        $this->orderPaymentRepository->create([
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);
    }

    /**
     * @param $subscriptionId
     * @param $paymentId
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