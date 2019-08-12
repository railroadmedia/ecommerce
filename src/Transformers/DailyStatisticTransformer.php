<?php

namespace Railroad\Ecommerce\Transformers;

use League\Fractal\TransformerAbstract;
use Railroad\Ecommerce\Entities\Structures\DailyStatistic;

class DailyStatisticTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    public function transform(DailyStatistic $dailyStatistic)
    {
        if (count($dailyStatistic->getProductStatistics())) {
            $this->defaultIncludes[] = 'productStatistic';
        }
        else {
            $this->defaultIncludes = [];
        }

        return [
            'id' => $dailyStatistic->getId(),
            'total_sales' => $dailyStatistic->getTotalSales(),
            'total_ales_from_renewals' => $dailyStatistic->getTotalSalesFromRenewals(),
            'total_refunded' => $dailyStatistic->getTotalRefunded(),
            'total_number_of_orders_placed' => $dailyStatistic->getTotalOrders(),
            'total_number_of_successful_subscription_renewal_payments' => $dailyStatistic->getTotalSuccessfulRenewals(),
            'total_number_of_failed_subscription_renewal_payments' => $dailyStatistic->getTotalFailedRenewals(),
            'day' => $dailyStatistic->getDay(),
        ];
    }

    public function includeProductStatistic(DailyStatistic $dailyStatistic)
    {
        return $this->collection(
            $dailyStatistic->getProductStatistics(),
            new ProductStatisticTransformer(),
            'productStatistic'
        );
    }
}
