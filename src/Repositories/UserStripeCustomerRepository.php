<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Repositories\RepositoryBase;

class UserStripeCustomerRepository extends RepositoryBase
{

    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableUserStripeCustomer);
    }

    public function getByUserId($userId)
    {
        return $this->query()->where('user_id', $userId)->first();
    }
}