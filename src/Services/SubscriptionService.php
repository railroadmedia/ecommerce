<?php

namespace Railroad\Ecommerce\Services;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\SubscriptionRepository;

class SubscriptionService
{
    /**
     * @var \Railroad\Ecommerce\Repositories\SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @var \Railroad\Ecommerce\Services\OrderService
     */
    private $orderService;

    //subscription types
    CONST SUBSCRIPTION_TYPE = 'subscription';
    CONST PAYMENT_PLAN_TYPE = 'payment plan';
    //subscription interval type
    const INTERVAL_TYPE_DAILY   = 'day';
    const INTERVAL_TYPE_MONTHLY = 'month';
    const INTERVAL_TYPE_YEARLY  = 'year';

    /**
     * SubscriptionService constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\SubscriptionRepository $subscriptionRepository
     */
    public function __construct(SubscriptionRepository $subscriptionRepository, OrderService $orderService)
    {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->orderService           = $orderService;
    }

    /**
     * Save a new subscription in the database.
     *
     * @param $type
     * @param $userId
     * @param $customerId
     * @param $orderId
     * @param $productId
     * @param $isActive
     * @param $startDate
     * @param $paidUntil
     * @param $totalPricePerPayment
     * @param $taxPerPayment
     * @param $shippingPerPayment
     * @param $intervalType
     * @param $intervalCount
     * @param $totalCyclesDue
     * @param $totalCyclesPaid
     * @return array
     */
    public function store(
        $type,
        $userId,
        $customerId,
        $orderId,
        $productId,
        $isActive,
        $startDate,
        $paidUntil,
        $totalPricePerPayment,
        $taxPerPayment,
        $shippingPerPayment,
        $intervalType,
        $intervalCount,
        $totalCyclesDue,
        $totalCyclesPaid
    ) {

        $subscriptionId = $this->subscriptionRepository->create(
            [
                'uuid'                    => bin2hex(openssl_random_pseudo_bytes(16)),
                'brand'                   => ConfigService::$brand,
                'type'                    => $type,
                'user_id'                 => $userId,
                'customer_id'             => $customerId,
                'order_id'                => $orderId,
                'product_id'              => $productId,
                'is_active'               => $isActive,
                'start_date'              => $startDate,
                'paid_until'              => $paidUntil,
                'total_price_per_payment' => $totalPricePerPayment,
                'tax_per_payment'         => $taxPerPayment,
                'shipping_per_payment'    => $shippingPerPayment,
                'interval_type'           => $intervalType,
                'interval_count'          => $intervalCount,
                'total_cycles_due'        => $totalCyclesDue,
                'total_cycles_paid'       => $totalCyclesPaid,
                'created_on'              => Carbon::now()->toDateTimeString()
            ]
        );

        return $this->getById($subscriptionId);
    }

    public function getById($id)
    {
        return $this->subscriptionRepository->getById($id);
    }

    public function createOrderSubscription($orderId)
    {
        $subscription = [];
        $order        = $this->orderService->getOrderWithItems($orderId);

        foreach($order as $orderItem)
        {
            //check if order items it's a subscription or product
            if($orderItem['product_type'] == ProductService::TYPE_SUBSCRIPTION)
            {

                //calculate paid until
                $paidUntil = Carbon::now();
                if($orderItem['subscription_interval_type'] == self::INTERVAL_TYPE_DAILY)
                {
                    $paidUntil->addDays($orderItem['subscription_interval_count']);
                }
                elseif($orderItem['subscription_interval_type'] == self::INTERVAL_TYPE_MONTHLY)
                {
                    $paidUntil->addMonths($orderItem['subscription_interval_count']);
                }
                elseif($orderItem['subscription_interval_type'] == self::INTERVAL_TYPE_YEARLY)
                {
                    $paidUntil->addYears($orderItem['subscription_interval_count']);
                }
                //TODO: calculate subscription tax
                $subscriptionTax = 0;

                $subscription[] = $this->store(self::SUBSCRIPTION_TYPE,
                    $orderItem['user_id'],
                    $orderItem['customer_id'],
                    $orderItem['id'],
                    $orderItem['product_id'],
                    true,
                    Carbon::now(),
                    $paidUntil,
                    $orderItem['due'] + $subscriptionTax,
                    $subscriptionTax,
                    0,
                    $orderItem['subscription_interval_type'],
                    $orderItem['subscription_interval_count'],
                    null,
                    1);
            }
        }

        return $subscription;
    }
}