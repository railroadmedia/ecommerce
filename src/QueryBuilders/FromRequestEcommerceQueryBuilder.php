<?php

namespace Railroad\Ecommerce\QueryBuilders;

use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;

class FromRequestEcommerceQueryBuilder extends QueryBuilder
{
    /**
     * @param Request $request
     * @param int $defaultPage
     * @param int $defaultLimit
     *
     * @return FromRequestEcommerceQueryBuilder
     */
    public function paginateByRequest(Request $request, $defaultPage = 1, $defaultLimit = 10)
    {
        $page = $request->get('page', $defaultPage);
        $limit = $request->get('limit', $defaultLimit);

        $first = ($page - 1) * $limit;

        $this->setMaxResults($limit)
            ->setFirstResult($first);

        return $this;
    }

    /**
     * @param Request $request
     * @param $entityAlias
     * @param string $defaultOrderByColumn
     * @param string $defaultOrderByDirection
     *
     * @return FromRequestEcommerceQueryBuilder
     */
    public function orderByRequest(
        Request $request,
        $entityAlias,
        $defaultOrderByColumn = 'created_at',
        $defaultOrderByDirection = 'desc'
    )
    {
        $orderByColumn = $request->get('order_by_column', $defaultOrderByColumn);
        $orderByDirection = $request->get('order_by_direction', $defaultOrderByDirection);

        // todo: review, im not sure if this if statement is needed
        if (strpos($orderByColumn, '_') !== false || strpos($orderByColumn, '-') !== false) {
            $orderByColumn = camel_case($orderByColumn);
        }

        $orderByColumn = $entityAlias . '.' . $orderByColumn;

        $this->orderBy($orderByColumn, $orderByDirection);

        return $this;
    }

    /**
     * You must use andWhere or orWhere after using this method, since it uses a where statement.
     *
     * @param Request $request
     * @param $entityAlias
     * @param array|boolean $defaultBrands Must be an array, if false it will use the default configured brand.
     *
     * @return FromRequestEcommerceQueryBuilder
     */
    public function restrictBrandsByRequest(Request $request, $entityAlias, $defaultBrands = false)
    {
        if ($defaultBrands === false) {
            $defaultBrands = [config('ecommerce.brand')];
        }

        $brands = $request->get('brands', $defaultBrands);

        $this->where(
            $this->expr()
                ->in($entityAlias . '.brand', ':brands')
        )
            ->setParameter('brands', $brands);

        return $this;
    }
}