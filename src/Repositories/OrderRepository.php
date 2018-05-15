<?php

namespace Railroad\Ecommerce\Repositories;

use Illuminate\Database\Query\Builder;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\Decorator;
use Railroad\Resora\Queries\CachedQuery;
use Railroad\Resora\Repositories\RepositoryBase;

class OrderRepository extends RepositoryBase
{
    /**
     * @return CachedQuery|$this
     */
    protected function newQuery()
    {
        return (new CachedQuery($this->connection()))->from(ConfigService::$tableOrder);
    }

    protected function decorate($results)
    {
        /* if(is_array($results))
         {
             $results = new Product($results);
         } */

        return Decorator::decorate($results, 'order');
    }

    public function getOrdersByConditions($conditions)
    {
        return $this->query()
            ->where($conditions)
            ->get()
            ->toArray();
    }

    /** Get the order with the corresponding order items.
     *
     * @param $id
     * @return array
     */
    public function getOrderWithItemsById($id)
    {
        return $this->query()
            ->select
            (
                ConfigService::$tableOrder . '.*',
                ConfigService::$tableOrderItem . '.id as order_item_id',
                ConfigService::$tableProduct . '.id as product_id',
                ConfigService::$tableProduct . '.is_physical',
                ConfigService::$tableProduct . '.type as product_type',
                ConfigService::$tableProduct . '.subscription_interval_type',
                ConfigService::$tableProduct . '.subscription_interval_count',
                ConfigService::$tableProduct . '.price as initial_price'
            )
            ->join(ConfigService::$tableOrderItem, ConfigService::$tableOrder . '.id', '=', ConfigService::$tableOrderItem . '.order_id')
            ->join(ConfigService::$tableProduct, ConfigService::$tableOrderItem . '.product_id', '=', ConfigService::$tableProduct . '.id')
            ->where(ConfigService::$tableOrder . '.id', $id)
            ->get()->toArray();
    }
}