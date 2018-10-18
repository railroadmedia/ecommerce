<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Repositories\SubscriptionRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class PaymentSubscriptionDecorator implements DecoratorInterface
{
    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    public function __construct( SubscriptionRepository $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    public function decorate($payments)
    {
        $paymentId = $payments->pluck('id');
        $subscriptions =
            $this->subscriptionRepository->query()
                ->join(
                    ConfigService::$tableSubscriptionPayment,
                    ConfigService::$tableSubscription . '.id',
                    '=',
                    ConfigService::$tableSubscriptionPayment . '.subscription_id'
                )
                ->whereIn(ConfigService::$tableSubscriptionPayment . '.payment_id', $paymentId)
                ->get()
                ->keyBy('payment_id');

        foreach ($payments as $index => $payment) {
            $payments[$index]['subscription'] = $subscriptions[$payment['id']] ?? null;
        }

        return $payments;
    }
}