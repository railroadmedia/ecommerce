<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Repositories\ShippingOptionRepository;

class ShippingService
{
    /**
     * @var ShippingOptionRepository
     */
    private $shippingOptionRepository;

    /**
     * ShippingService constructor.
     *
     * @param ShippingOptionRepository $shippingOptionRepository
     */
    public function __construct(ShippingOptionRepository $shippingOptionRepository)
    {
        $this->shippingOptionRepository = $shippingOptionRepository;
    }

    /**
     * @param string $country
     * @param integer $totalWeight
     * @return mixed
     */
    public function getShippingCost($country, $totalWeight)
    {
        $cost = $this->shippingOptionRepository->query()
                ->join(
                    ConfigService::$tableShippingCostsWeightRange,
                    ConfigService::$tableShippingOption . '.id',
                    '=',
                    ConfigService::$tableShippingCostsWeightRange . '.shipping_option_id'
                )
                ->where(
                    function (Builder $query) use ($country) {
                        $query->where('country', $country)
                            ->orWhere('country', '*');
                    }
                )
                ->where('min', '<=', $totalWeight)
                ->where('max', '>=', $totalWeight)
                ->first()['price'] ?? 0;

        return $cost;
    }
}