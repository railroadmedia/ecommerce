<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;

class CustomerStripeCustomerRepository extends RepositoryBase
{

    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableCustomerStripeCustomer);
    }

    public function getByCustomerId($customerId)
    {
        return $this->query()->where('customer_id', $customerId)->first();
    }
}