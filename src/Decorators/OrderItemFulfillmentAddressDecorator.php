<?php

namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class OrderItemFulfillmentAddressDecorator implements DecoratorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function decorate($data)
    {
        $orderIds = $data->pluck('order_id');

        $orderShippingAddress = $this->databaseManager
            ->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableOrder)
            ->select(ConfigService::$tableAddress . '.*', ConfigService::$tableOrder . '.id as order_id')
            ->join(ConfigService::$tableAddress, ConfigService::$tableOrder . '.shipping_address_id', '=', ConfigService::$tableAddress . '.id')
            ->whereIn(ConfigService::$tableOrder . '.id', $orderIds)
            ->get();

        foreach($data as $dataIndex => $order)
        {
            $data[$dataIndex]['shipping_address'] = [];
            foreach($orderShippingAddress as $address)
            {
                $address = (array) $address;
                if($address['order_id'] == $order['id'])
                {
                    $data[$dataIndex]['shipping_address'] = $address;
                }
            }
        }

        return $data;
    }
}