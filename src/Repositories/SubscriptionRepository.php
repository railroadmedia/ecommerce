<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Railroad\Ecommerce\Services\ConfigService;

class SubscriptionRepository extends RepositoryBase
{
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableSubscription);
    }

    /** Get all the active due subscriptions
     * @return array
     */
    public function getDueSubscriptions()
    {
        return $this->query()
            ->join(ConfigService::$tableSubscriptionPayment, ConfigService::$tableSubscription . '.id', '=', ConfigService::$tableSubscriptionPayment . '.subscription_id')
            ->join(ConfigService::$tablePayment, ConfigService::$tableSubscriptionPayment. '.payment_id', '=', ConfigService::$tablePayment . '.id')
            ->where('paid_until', '<=', Carbon::now()->toDateTimeString())
            ->where('is_active', '=', true)
            ->get()
            ->toArray();
    }
}