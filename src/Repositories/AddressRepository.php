<?php

namespace Railroad\Ecommerce\Repositories;


use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;

class AddressRepository extends RepositoryBase
{
    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection()->table(ConfigService::$tableAddress);
    }

    public function getById($id)
    {
        return $this->query()
            ->where([ConfigService::$tableAddress . '.id' => $id])
            ->get()
            ->first();
    }
}