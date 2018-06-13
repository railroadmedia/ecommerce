<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class SubscriptionPaymentMethodDecorator implements DecoratorInterface
{
    /**
     * @var PaymentMethodRepository
     */
    private $paymentMethodRepository;

    public function __construct(PaymentMethodRepository $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function decorate($subscriptions)
    {
        $productMethodIds = $subscriptions->pluck('payment_method_id');

        $paymentMethods = $this->paymentMethodRepository->query()
            ->whereIn(ConfigService::$tablePaymentMethod . '.id', $productMethodIds)
            ->get()
            ->keyBy('id');

        foreach ($subscriptions as $index => $subscription) {
            $subscriptions[$index]['payment_method'] =
                isset($paymentMethods[$subscription['payment_method_id']]) ?
                    (array)$paymentMethods[$subscription['payment_method_id']] : null;
        }

        return $subscriptions;
    }
}