<?php

namespace Railroad\Ecommerce\Services;

use Doctrine\ORM\QueryBuilder;
use League\Fractal\Serializer\JsonApiSerializer;
use Railroad\Doctrine\Services\FractalResponseService;
use Railroad\Ecommerce\Transformers\AccessCodeTransformer;
use Railroad\Ecommerce\Transformers\AddressTransformer;
use Railroad\Ecommerce\Transformers\DiscountTransformer;
use Railroad\Ecommerce\Transformers\ProductTransformer;
use Spatie\Fractal\Fractal;

class ResponseService extends FractalResponseService
{
    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     * @return Fractal
     */
    public static function address($entityOrEntities, QueryBuilder $queryBuilder = null, array $includes = [])
    {
        return self::create($entityOrEntities, 'address', new AddressTransformer(), new JsonApiSerializer(), $queryBuilder)
            ->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     * @return Fractal
     */
    public static function accessCode(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'accessCode',
                new AccessCodeTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param AccessCode|array $accessCodes
     * @param array $products - array of Products
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function decoratedAccessCode(
        $accessCodes,
        $products,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $accessCodes,
                'accessCode',
                new AccessCodeTransformer($products),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function product(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'product',
                new ProductTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }

    /**
     * @param string $url
     *
     * @return Fractal
     */
    public static function productThumbnail(string $url)
    {
        return fractal(
                null,
                function($notUsed) {
                    return null;
                },
                new JsonApiSerializer()
            )->addMeta(['url' => $url]);
    }

    /**
     * @param $entityOrEntities
     * @param QueryBuilder|null $queryBuilder
     * @param array $includes
     *
     * @return Fractal
     */
    public static function discount(
        $entityOrEntities,
        QueryBuilder $queryBuilder = null,
        array $includes = []
    ) {
        return self::create(
                $entityOrEntities,
                'discount',
                new DiscountTransformer(),
                new JsonApiSerializer(),
                $queryBuilder
            )->parseIncludes($includes);
    }
}
