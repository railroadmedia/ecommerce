<?php

namespace Railroad\Ecommerce\Transformers;

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
    )
    {
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

    public function includePayments()
    {
        return $this->collection(
            $this->payments,
            new PaymentTransformer(),
            'payment'
        );
    }

    public function includeRefunds()
    {
        return $this->collection(
            $this->refunds,
            new RefundTransformer(),
            'refund'
        );
    }

    public function includeSubscriptions()
    {
        return $this->collection(
            $this->subscriptions,
            new SubscriptionTransformer(),
            'subscription'
        );
    }

    public function includePaymentPlans()
    {
        return $this->collection(
            $this->paymentPlans,
            new SubscriptionTransformer(),
            'paymentPlan'
        );
    }
}
