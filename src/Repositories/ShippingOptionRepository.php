<?php

namespace Railroad\Ecommerce\Repositories;

use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Entities\Entity;
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

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'shippingOptions');
    }

    /** Get the first active shipping cost based on country and total weight
     *
     * @param string  $country
     * @param integer $totalWeight
     * @return mixed
     */
    public function getShippingCosts($country, $totalWeight)
    {
        $results = $this->query()
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
            ->first();

        return $results;
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