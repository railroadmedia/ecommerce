<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;

class SubscriptionRepository extends RepositoryBase
{
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableSubscription);
    }
}