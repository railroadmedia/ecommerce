<?php

namespace Railroad\Ecommerce\Repositories\Traits;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Entities\AccessCode;
use Railroad\Ecommerce\QueryBuilders\FromRequestEcommerceQueryBuilder;

trait UseFormRequestQueryBuilder
{

    /**
     * @param string $alias
     * @param null $indexBy
     * @return FromRequestEcommerceQueryBuilder
     */
    public function createQueryBuilder($alias, $indexBy = null)
    {
        $queryBuilder = new FromRequestEcommerceQueryBuilder($this->entityManager);

        $queryBuilder->select($alias)
            ->from($this->entityManager, $alias, $indexBy);

        return $queryBuilder;
    }

    /**
     * @param $request
     * @return AccessCode[]
     */
    public function indexByRequest(Request $request)
    {
        return $this->indexQueryBuilderByRequest($request)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $request
     * @return FromRequestEcommerceQueryBuilder
     */
    public function indexQueryBuilderByRequest(Request $request)
    {
        $alias = 'a';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias)
            ->select($alias);

        return $qb;
    }
}