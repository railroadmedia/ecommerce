<?php

namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class ShippingOptionsCostsDecorator implements DecoratorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function decorate($shippingOptions)
    {
        $shippingOptionIds = $shippingOptions->pluck('id');

        $shippingCosts = $this->databaseManager
            ->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableShippingCostsWeightRange)
            ->whereIn('shipping_option_id', $shippingOptionIds)
            ->get();

        foreach ($shippingOptions as $shippingOptionIndex => $shippingOption) {
            $shippingOptions[$shippingOptionIndex]['weightRanges'] = [];
            foreach ($shippingCosts as $weightRange) {
                $weightRange = (array)$weightRange;
                if ($weightRange['shipping_option_id'] == $shippingOption['id']) {
                    $shippingOptions[$shippingOptionIndex]['weightRanges'][] = $weightRange;
                }
            }
        }

        return $shippingOptions;
    }
}