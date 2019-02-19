<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Order;
use Railroad\Ecommerce\Transformers\OrderTransformer;

class DecoratedOrderTransformer extends OrderTransformer
{
    protected $payments;
    protected $refunds;
    protected $subscriptions;
    protected $paymentPlans;

    public function __construct(
        array $payments = [],
        array $refunds = [],
        array $subscriptions = [],
        array $paymentPlans = []
    ) {
        $this->payments = $payments;
        $this->refunds = $refunds;
        $this->subscriptions = $subscriptions;
        $this->paymentPlans = $paymentPlans;

        $this->defaultIncludes = [
            'payments',
            'refunds',
            'subscriptions',
            'paymentPlans'
        ];
    }

    public function includePayments(Order $order)
    {
        return $this->collection(
            $this->payments,
            new PaymentTransformer(),
            'payment'
        );
    }

    public function includeRefunds(Order $order)
    {
        return $this->collection(
            $this->refunds,
            new RefundTransformer(),
            'refund'
        );
    }

    public function includeSubscriptions(Order $order)
    {
        return $this->collection(
            $this->subscriptions,
            new SubscriptionTransformer(),
            'subscription'
        );
    }

    public function includePaymentPlans(Order $order)
    {
        return $this->collection(
            $this->paymentPlans,
            new SubscriptionTransformer(),
            'paymentPlan'
        );
    }
}
