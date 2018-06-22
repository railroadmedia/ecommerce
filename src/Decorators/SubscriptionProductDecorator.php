<?php

namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class SubscriptionProductDecorator implements DecoratorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function decorate($subscriptions)
    {
        $productIds = $subscriptions->pluck('product_id');

        $products = $this->databaseManager
            ->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableProduct)
            ->whereIn(ConfigService::$tableProduct . '.id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($subscriptions as $index => $subscription) {
            $subscriptions[$index]['product'] =
                ((array)$products[$subscription['product_id']]) ?? null;
        }

        return $subscriptions;
    }
}