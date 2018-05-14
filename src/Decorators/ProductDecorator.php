<?php
namespace Railroad\Ecommerce\Decorators;

use Illuminate\Database\DatabaseManager;
use Railroad\Ecommerce\Services\ConfigService;
use Railroad\Resora\Decorators\DecoratorInterface;

class ProductDecorator implements DecoratorInterface
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

        $productOrders = $this->databaseManager->table(ConfigService::$tableOrderItem)
            ->whereIn('product_id', $productIds)
            ->get();

        foreach ($products as $productIndex => $product) {
            $products[$productIndex]['order'] = [];
            foreach ($productOrders as $productOrderIndex => $productOrder) {
                if ($productOrder['product_id'] == $product['id']) {
                    $products[$productIndex]['order'][] = [
                        'id' => $productOrder['id'],
                        'order_id' => $productOrder['order_id']
                    ];
                }
            }
        }

        return $products;
    }
}