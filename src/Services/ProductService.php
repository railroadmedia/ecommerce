<?php

namespace Railroad\Ecommerce\Services;

use Illuminate\Support\Facades\Cache;
use Railroad\Ecommerce\Repositories\ProductRepository;

class ProductService
{
    private static $productCache;

    /** Get product data based on sku
     *
     * @param $sku
     * @return array
     */
    public static function products($sku)
    {
        $productsBySku = array_combine(
            self::allProducts()
                ->pluck('sku')
                ->toArray(),
            self::allProducts()
                ->toArray()
        );

        return $productsBySku[$sku] ?? [];
    }

    /** Get all products
     *
     * @return mixed
     */
    public function allProducts()
    {
        if (empty(self::$productCache)) {
            self::$productCache =
                Cache::store()
                    ->remember(
                        'products.all',
                        60,
                        function () {
                            return app(ProductRepository::class)
                                ->query()
                                ->get();
                        }
                    );
        }
        return self::$productCache;
    }
}