<?php

namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class DiscountDiscountCriteriaDecorator implements DecoratorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function decorate($discounts)
    {
        $discountIds = $discounts->pluck('id');

        $discountCriteria = $this->databaseManager
            ->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableDiscountCriteria)
            ->whereIn('discount_id', $discountIds->toArray())
            ->get();

        foreach ($discounts as $discountIndex => $discount) {
            $discounts[$discountIndex]['discounts'] = [];
            foreach ($discountCriteria as $discountCriteriaIndex => $discountCriteria) {
                if ($discountCriteria->discount_id == $discount['id']) {
                    $discounts[$discountIndex]['discounts'][] = $discountCriteria->toArray();
                }
            }
        }

        return $discounts;
    }
}