<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\Resource\Collection;

class DecoratedOrderTransformer extends OrderTransformer
{
    protected $payments;
    protected $refunds;
    protected $subscriptions;
    protected $paymentPlans;

    /**
     * DecoratedOrderTransformer constructor.
     *
     * @param array $payments
     * @param array $refunds
     * @param array $subscriptions
     * @param array $paymentPlans
     */
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

        $this->defaultIncludes = array_merge(
            $this->defaultIncludes,
            [
                'payments',
                'refunds',
                'subscriptions',
                'paymentPlans'
            ]
        );
    }

    /**
     * @return Collection
     */
    public function includePayments()
    {
        return $this->collection(
            $this->payments,
            new PaymentTransformer(),
            'payment'
        );
    }

    /**
     * @return Collection
     */
    public function includeRefunds()
    {
        return $this->collection(
            $this->refunds,
            new RefundTransformer(),
            'refund'
        );
    }

    /**
     * @return Collection
     */
    public function includeSubscriptions()
    {
        return $this->collection(
            $this->subscriptions,
            new SubscriptionTransformer(),
            'subscription'
        );
    }

    /**
     * @return Collection
     */
    public function includePaymentPlans()
    {
        return $this->collection(
            $this->paymentPlans,
            new SubscriptionTransformer(),
            'paymentPlan'
        );
    }
}
