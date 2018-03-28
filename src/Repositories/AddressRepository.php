<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\QueryBuilders\AddressQueryBuilder;
use Railroad\Ecommerce\Services\ConfigService;

class AddressRepository extends RepositoryBase
{
    public static $pullAllAddresses = false;
    /**
     * If this is false any payment method will be pulled. If its defined, only user address will be pulled.
     *
     * @var integer|bool
     */
    public static $availableUserId = false;

    /**
     * If this is false any payment method will be pulled. If its defined, only customer address will be pulled.
     *
     * @var integer|bool
     */
    public static $availableCustomerId = false;

    /**
     * @return Builder
     */
    protected function query()
    {
        return (new AddressQueryBuilder(
            $this->connection(),
            $this->connection()->getQueryGrammar(),
            $this->connection()->getPostProcessor()
        ))
            ->from(ConfigService::$tableAddress);
    }

    public function getById($id)
    {
        return $this->query()
            ->where([ConfigService::$tableAddress . '.id' => $id])
            ->get()
            ->first();
    }
}