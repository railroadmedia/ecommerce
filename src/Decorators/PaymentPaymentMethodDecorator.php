<?php

namespace Railroad\Ecommerce\Decorators;

use Railroad\Ecommerce\Repositories\PaymentMethodRepository;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class PaymentPaymentMethodDecorator implements DecoratorInterface
{
    /**
     * @var \Railroad\Ecommerce\Repositories\PaymentMethodRepository
     */
    private $paymentMethodRepository;

    /**
     * PaymentPaymentMethodDecorator constructor.
     *
     * @param \Railroad\Ecommerce\Repositories\PaymentMethodRepository $paymentMethodRepository
     */
    public function __construct(PaymentMethodRepository $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function decorate($payments)
    {
        $productMethodIds = $payments->pluck('payment_method_id');

        $paymentMethods = $this->paymentMethodRepository->query()
            ->whereIn(ConfigService::$tablePaymentMethod . '.id', $productMethodIds)
            ->get()
            ->keyBy('id');

        foreach($payments as $index => $payment)
        {
            $payments[$index]['payment_method'] = $paymentMethods[$payment['payment_method_id']] ?? null;
        }

        return $payments;
    }
}