<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\QueryBuilders\ShippingQueryBuilder;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class ShippingOptionRepository extends RepositoryBase
{
    /**
     * Determines whether inactive shipping options will be pulled or not.
     *
     * @var array|bool
     */
    public static $pullInactiveShippingOptions = true;

    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tableShippingOption);
    }

    /** Get the first active shipping cost based on country and total weight
     *
     * @param string  $country
     * @param integer $totalWeight
     * @return mixed
     */
    public function getShippingCosts($country, $totalWeight)
    {
        return $this->newQuery()
            ->join(
                ConfigService::$tableShippingCostsWeightRange,
                $this->connection()->raw(ConfigService::$tableShippingOption . '.id'),
                '=',
                $this->connection()->raw(ConfigService::$tableShippingCostsWeightRange . '.shipping_option_id')
            )
            ->where(
                function ($query) use ($country) {
                    $query->where('country', $country)
                        ->orWhere('country', '*');
                }
            )
            ->where('min', '<=', $totalWeight)
            ->where('max', '>=', $totalWeight)
            ->get();
    }

    /** Get all the shipping costs weight ranges based on shipping option id
     *
     * @param int $shippingOptionId
     * @return mixed
     */
    public function getShippingCostsForShippingOption($shippingOptionId)
    {
        return $this->query()
            ->join(
                ConfigService::$tableShippingCostsWeightRange,
                $this->databaseManager->raw(ConfigService::$tableShippingOption . '.id'),
                '=',
                $this->databaseManager->raw(ConfigService::$tableShippingCostsWeightRange . '.shipping_option_id')
            )
            ->restrictByShippingOption($shippingOptionId)
            ->get()->toArray();
    }

    protected function connection()
    {
        return app('db')->connection(ConfigService::$databaseConnectionName);
    }
}