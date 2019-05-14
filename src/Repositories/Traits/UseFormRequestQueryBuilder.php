<?php

namespace Railroad\Ecommerce\Repositories\Traits;

use Illuminate\Http\Request;
use Railroad\Ecommerce\Composites\Query\ResultsQueryBuilderComposite;
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
            ->from($this->entityName, $alias, $indexBy);

        return $queryBuilder;
    }

    /**
     * @param $request
     * @return ResultsQueryBuilderComposite
     */
    public function indexByRequest(Request $request)
    {
        $alias = 'a';

        $qb = $this->createQueryBuilder($alias);

        $qb->paginateByRequest($request)
            ->orderByRequest($request, $alias)
            ->restrictBrandsByRequest($request, $alias)
            ->select($alias);

        $results =
            $qb->getQuery()
                ->getResult();

        return new ResultsQueryBuilderComposite($results, $qb);
    }
}