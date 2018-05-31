<?php
namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class ProductDiscountDecorator implements DecoratorInterface
{
    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->databaseManager = $databaseManager;
    }

    public function decorate($products)
    {
        $productIds = $products->pluck('id');

        $productDiscounts = $this->databaseManager
            ->connection(ConfigService::$databaseConnectionName)
            ->table(ConfigService::$tableDiscountCriteria)
            ->select(ConfigService::$tableDiscountCriteria.'.*', ConfigService::$tableDiscount.'.active', ConfigService::$tableDiscount.'.type as discount_type', ConfigService::$tableDiscount.'.amount')
            ->join(ConfigService::$tableDiscount, ConfigService::$tableDiscountCriteria.'.discount_id','=', ConfigService::$tableDiscount.'.id')
            ->whereIn('product_id', $productIds)
            ->where(ConfigService::$tableDiscount.'.active', true)
            ->get();

        foreach ($products as $productIndex => $product) {
            $products[$productIndex]['discounts'] = [];
            foreach ($productDiscounts as $productDiscountIndex => $productDiscount) {
                $productDiscount = (array)$productDiscount;
                if ($productDiscount['product_id'] == $product['id']) {
                    $products[$productIndex]['discounts'][] = $productDiscount;
                }
            }
        }

        return $products;
    }
}