<?php

namespace Railroad\Ecommerce\Repositories;

use Carbon\Carbon;
use Railroad\Ecommerce\Repositories\Queries\SubscriptionQuery;
use Railroad\Ecommerce\Repositories\Traits\SoftDelete;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Entities\Entity;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class SubscriptionRepository extends RepositoryBase
{
    use SoftDelete;

    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new SubscriptionQuery($this->connection()))
            ->from(ConfigService::$tableSubscription)
            ->whereNull(ConfigService::$tableSubscription . '.deleted_on');
    }

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'subscription');
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}