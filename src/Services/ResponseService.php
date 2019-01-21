<?php

namespace Railroad\Ecommerce\Services;

use Doctrine\ORM\QueryBuilder;
use League\Fractal\Serializer\JsonApiSerializer;
use Railroad\Doctrine\Services\FractalResponseService;
use Railroad\Ecommerce\Transformers\AccessCodeTransformer;
use Railroad\Ecommerce\Transformers\AddressTransformer;
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
}