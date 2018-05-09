<?php

namespace Railroad\Ecommerce\Factories;

use Carbon\Carbon;
use Faker\Generator;
use Railroad\Ecommerce\Services\SubscriptionService;

class SubscriptionFactory extends SubscriptionService
{
    /**
     * @var Generator
     */
    protected $faker;

    public function store(
        $type = '',
        $userId = null,
        $customerId = null,
        $orderId = null,
        $productId = null,
        $isActive = true,
        $startDate = null,
        $paidUntil = null,
        $totalPricePerPayment = 0,
        $taxPerPayment = 0,
        $shippingPerPayment = 0,
        $intervalType = '',
        $intervalCount = 0,
        $totalCyclesDue = 0,
        $totalCyclesPaid = 0
    ) {
        $this->faker = app(Generator::class);

        $parameters =
            func_get_args() + [
                $this->faker->randomElement([
                    SubscriptionService::SUBSCRIPTION_TYPE,
                    SubscriptionService::PAYMENT_PLAN_TYPE
                ]),
                request()->user() ? request()->user()->id : null,
                request()->user() ? null : rand(),
                rand(),
                rand(),
                true,
                Carbon::now()->toDateTimeString(),
                Carbon::now()->toDateTimeString(),
                rand(1,100),
                rand(0,10),
                rand(0,10),
                SubscriptionService::INTERVAL_TYPE_YEARLY,
                rand(1,2),
                null,
                1
            ];

        return parent::store(...$parameters);
    }
}