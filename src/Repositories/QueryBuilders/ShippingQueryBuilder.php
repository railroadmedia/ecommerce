<?php

namespace Railroad\Ecommerce\Repositories\QueryBuilders;

use Railroad\Ecommerce\Repositories\ShippingOptionRepository;
use Railroad\Ecommerce\Services\ConfigService;

class ShippingQueryBuilder extends QueryBuilder
{
    /** Add to query builder the restriction by country
     *
     * @param $country
     * @return $this
     */
    public function restrictByCountry($country)
    {
        $this->where(
            function ($query) use ($country) {
                $query->where('country', $country)
                    ->orWhere('country', '*');
            }
        );

        return $this;
    }

    /** Based on ShippingRepository::$pullInactiveShippingOptions param return only active shipping options
     *
     * @return $this
     */
    public function restrictActive()
    {
        if (!ShippingOptionRepository::$pullInactiveShippingOptions) {
            $this->where(ConfigService::$tableShippingOption . '.active', 1);
        }

        return $this;
    }

    /** Restrict by  total weight
     *
     * @param integer $weight
     * @return $this
     */
    public function restrictByWeight($weight)
    {
        $this->where('min', '<=', $weight)
            ->where('max', '>=', $weight);

        return $this;
    }

    /** Restrict by shipping option id
     *
     * @param integer $shippingOptionId
     * @return $this
     */
    public function restrictByShippingOption($shippingOptionId)
    {
        $this->where('shipping_option_id', $shippingOptionId);

        return $this;

    }
}