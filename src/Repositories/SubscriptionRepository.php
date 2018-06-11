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
}