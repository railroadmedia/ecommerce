<?php


namespace Railroad\Ecommerce\Repositories;


use function foo\func;
use Railroad\Ecommerce\Repositories\QueryBuilders\ShippingQueryBuilder;
use Railroad\Ecommerce\Services\ConfigService;

class ShippingRepository extends RepositoryBase
{
    /**
     * Determines whether inactive shipping options will be pulled or not.
     *
     * @var array|bool
     */
    public static $pullInactiveShippingOptions = true;

    /**
     * @return Builder
     */
    public function query()
    {
        return (new ShippingQueryBuilder(
            $this->connection(),
            $this->connection()->getQueryGrammar(),
            $this->connection()->getPostProcessor()
        ))
            ->from(ConfigService::$tableShippingOption);
    }

    /** Get the first active shipping cost based on country and total weight
     * @param string $country
     * @param integer $totalWeight
     * @return mixed
     */
    public function getShippingCosts($country, $totalWeight)
    {
        return $this->query()
            ->join(ConfigService::$tableShippingCostsWeightRange,
                $this->databaseManager->raw(ConfigService::$tableShippingOption . '.id'),
                '=',
                $this->databaseManager->raw(ConfigService::$tableShippingCostsWeightRange . '.shipping_option_id'))
            ->restrictByCountry($country)
            ->restrictByWeight($totalWeight)
            ->restrictActive()
            ->orderBy('priority', 'desc')
            ->get()->first();
    }

    /** Get all the shipping costs weight ranges based on shipping option id
     * @param int $shippingOptionId
     * @return mixed
     */
    public function getShippingCostsForShippingOption($shippingOptionId)
    {
        return $this->query()
            ->join(ConfigService::$tableShippingCostsWeightRange,
                $this->databaseManager->raw(ConfigService::$tableShippingOption . '.id'),
                '=',
                $this->databaseManager->raw(ConfigService::$tableShippingCostsWeightRange . '.shipping_option_id'))
            ->restrictByShippingOption($shippingOptionId)
            ->get()->toArray();
    }
}