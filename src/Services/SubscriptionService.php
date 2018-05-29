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

    /**
     * @var \Railroad\Ecommerce\Services\TaxService
     */
    private $taxService;

    /**
     * @var \Railroad\Ecommerce\Services\CartAddressService
     */
    private $cartAddressService;

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
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        OrderService $orderService,
        TaxService $taxService,
        CartAddressService $cartAddressService
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->orderService           = $orderService;
        $this->taxService             = $taxService;
        $this->cartAddressService     = $cartAddressService;
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
     * @param $currency
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
        $currency,
        $intervalType,
        $intervalCount,
        $totalCyclesDue,
        $totalCyclesPaid
    ) {

        $subscriptionId = $this->subscriptionRepository->create(
            [
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
                'currency'                => $currency,
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

    public function createOrderSubscription($orderId, $currency)
    {
        $subscription   = [];
        $order          = $this->orderService->getOrderWithItems($orderId);
        $billingAddress = $this->cartAddressService->getAddress(CartAddressService::BILLING_ADDRESS_TYPE);

        foreach($order as $orderItem)
        {
            //check if order items it's a subscription or product
            if($orderItem['product_type'] == ProductService::TYPE_SUBSCRIPTION)
            {
                $paidUntil = $this->calculateNextBillDate($orderItem['subscription_interval_type'], $orderItem['subscription_interval_count']);

                $subscriptionTax = $this->taxService->getTaxTotal($orderItem['initial_price'], $billingAddress['country'], $billingAddress['region']);

                $subscription[] = $this->store(self::SUBSCRIPTION_TYPE,
                    $orderItem['user_id'],
                    $orderItem['customer_id'],
                    $orderItem['id'],
                    $orderItem['product_id'],
                    true,
                    Carbon::now(),
                    $paidUntil,
                    $orderItem['initial_price'] + $subscriptionTax,
                    $subscriptionTax,
                    0,
                    $currency,
                    $orderItem['subscription_interval_type'],
                    $orderItem['subscription_interval_count'],
                    null,
                    1);
            }
        }

        return $subscription;
    }

    public function update($id, $data)
    {
        $subscription = $this->getById($id);

        if(empty($subscription))
        {
            return null;
        }

        $data['updated_on'] = Carbon::now()->toDateTimeString();
        $this->subscriptionRepository->update($id, $data);

        return $this->getById($id);
    }

    /**
     * @param $orderItem
     * @return \Carbon\Carbon
     */
    public function calculateNextBillDate($intervalType, $intervalCount)
    {
        $paidUntil = Carbon::now();

        switch($intervalType)
        {
            case self::INTERVAL_TYPE_DAILY:
                $paidUntil->addDays($intervalCount);
                break;
            case self::INTERVAL_TYPE_MONTHLY:
                $paidUntil->addMonths($intervalCount);
                break;
            case self::INTERVAL_TYPE_YEARLY:
                $paidUntil->addYears($intervalCount);
                break;
        }

        return $paidUntil;
    }

    /** Get an array with all the active due subscriptions.
     * @return array
     */
    public function renewalDueSubscriptions()
    {
        $dueSubcriptions = $this->subscriptionRepository->getDueSubscriptions();

        return $dueSubcriptions;
    }
}