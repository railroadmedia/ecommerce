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
            ->whereIn('product_id', $productIds)
            ->get();

        foreach ($products as $productIndex => $product) {
            $products[$productIndex]['discounts'] = [];
            foreach ($productDiscounts as $productDiscountIndex => $productDiscount) {
                if ($productDiscount['product_id'] == $product['id']) {
                    $products[$productIndex]['discounts'][] = [
                        'id' => $productDiscount['id'],
                        'order_id' => $productDiscount['discount_id']
                    ];
                }
            }
        }

        return $products;
    }
}