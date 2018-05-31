<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\Queries\SubscriptionQuery;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class SubscriptionRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new SubscriptionQuery($this->connection()))->from(ConfigService::$tableSubscription);
    }

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'subscription');
    }

    /** Get all the active due subscriptions
     *
     * @return array
     */
    public function getDueSubscriptions()
    {
        return $this->query()
            ->join(
                ConfigService::$tableSubscriptionPayment,
                ConfigService::$tableSubscription . '.id',
                '=',
                ConfigService::$tableSubscriptionPayment . '.subscription_id'
            )
            ->join(
                ConfigService::$tablePayment,
                ConfigService::$tableSubscriptionPayment . '.payment_id',
                '=',
                ConfigService::$tablePayment . '.id'
            )
            ->where('paid_until', '<=', Carbon::now()->toDateTimeString())
            ->where('is_active', '=', true)
            ->get()
            ->toArray();
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}