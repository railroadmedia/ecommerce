<?php


namespace Railroad\Ecommerce\Repositories;


use function foo\func;
use Railroad\Ecommerce\Services\ConfigService;

class ShippingRepository extends RepositoryBase
{
    /**
     * @return Builder
     */
    public function query()
    {
        return $this->connection()->table(ConfigService::$tableShippingOption);
    }

    public function getShippingCosts($country, $totalWeight)
    {

        $results = $this->query()
            ->join(ConfigService::$tableShippingCostsWeightRange, ConfigService::$tableShippingOption . '.id', '=', ConfigService::$tableShippingCostsWeightRange . '.shipping_option_id')
            ->where(function ($query) use ($country) {
                $query->where('country', $country)
                    ->orWhere('country', '*');
            })
            ->where('active', 1)
            ->where('min', '<=', $totalWeight)
            ->where('max', '>=', $totalWeight)
            ->orderBy('priority', 'desc')
            ->get()->first();
        return $results;
    }
}