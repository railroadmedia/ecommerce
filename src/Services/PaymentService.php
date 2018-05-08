<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Factories\GatewayFactory;
use Railroad\Ecommerce\Repositories\OrderPaymentRepository;
use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
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
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * @var LocationService
     */
    private $locationService;

    /**
     * @var \Railroad\Ecommerce\Factories\GatewayFactory
     */
    private $gatewayFactory;

    /**
     * @var OrderService
     */
    private $orderService;

    const MANUAL_PAYMENT_TYPE  = 'manual';
    const ORDER_PAYMENT_TYPE   = 'order';
    const RENEWAL_PAYMENT_TYPE = 'renewal';

    /**
     * PaymentService constructor.
     *
     * @param PaymentRepository             $paymentRepository
     * @param OrderPaymentRepository        $orderPaymentRepository
     * @param SubscriptionPaymentRepository $subscriptionPaymentRepository
     */
    public function __construct(
        PaymentRepository $paymentRepository,
        OrderPaymentRepository $orderPaymentRepository,
        SubscriptionPaymentRepository $subscriptionPaymentRepository,
        LocationService $locationService,
        PaymentMethodRepository $paymentMethodRepository,
        OrderService $orderService,
        GatewayFactory $gatewayFactory
    ) {
        $this->paymentRepository             = $paymentRepository;
        $this->orderPaymentRepository        = $orderPaymentRepository;
        $this->subscriptionPaymentRepository = $subscriptionPaymentRepository;
        $this->locationService               = $locationService;
        $this->paymentMethodRepository       = $paymentMethodRepository;
        $this->orderService                  = $orderService;
        $this->gatewayFactory                = $gatewayFactory;
    }

    /** Create a new payment; link the order/subscription; if the payment method id not exist set the payment type as 'manual' and the status true.
     * Return an array with the new created payment.
     *
     * @param numeric      $due
     * @param numeric|null $paid
     * @param numeric|null $refunded
     * @param string|null  $type
     * @param numeric|null $paymentMethodId
     * @param numeric|null $orderId
     * @param array        $subscriptionIds
     * @return array
     */
    public function store(
        $due,
        $paid,
        $refunded,
        $type,
        $paymentMethodId = null,
        $currency = null,
        $orderId = null,
        $subscriptionIds = []
    ) {
        // if the currency not exist on the request and the payment it's manual, get the currency with Location package, based on ip address
        if((!$currency) && (is_null($paymentMethodId)))
        {
            $currency = $this->locationService->getCurrency();
        }

        $paymentMethod = $this->paymentMethodRepository->getById($paymentMethodId);

        $gateway     = $this->gatewayFactory->create($paymentMethod['method_type']);
        $paymentData = $gateway->chargePayment($due, $paid, $paymentMethod, $type, ($currency ?? $paymentMethod['currency']));
        if(!$paymentData['status'])
        {
            return $paymentData;
        }

        $paymentId = $this->paymentRepository->create($paymentData);

        // Save the link between order and payment and save the paid amount on order row
        if($orderId)
        {
            $this->createOrderPayment($orderId, $paymentId);
            $this->orderService->update($orderId, [
                'paid' => $paid
            ]);
        }

        if(!empty($subscriptionIds))
        {
            foreach($subscriptionIds as $subscription)
            {
                $this->createSubscriptionPayment($subscription['id'], $paymentId);
            }
        }

        return $this->getById($paymentId);
    }

    /** Get the payment based on id.
     *
     * @param integer $id
     * @return array
     */
    public function getById($id)
    {
        return $this->paymentRepository->getById($id);
    }

    /** Create a link between payment and order.
     *
     * @param integer $orderId
     * @param integer $paymentId
     */
    public function createOrderPayment($orderId, $paymentId)
    {
        $this->orderPaymentRepository->create([
            'order_id'   => $orderId,
            'payment_id' => $paymentId,
            'created_on' => Carbon::now()->toDateTimeString()
        ]);
    }

    /** Create a link between payment and subscription.
     *
     * @param integer $subscriptionId
     * @param integer $paymentId
     */
    public function createSubscriptionPayment($subscriptionId, $paymentId)
    {
        $this->subscriptionPaymentRepository->create([
            'subscription_id' => $subscriptionId,
            'payment_id'      => $paymentId,
            'created_on'      => Carbon::now()->toDateTimeString()
        ]);
    }
}