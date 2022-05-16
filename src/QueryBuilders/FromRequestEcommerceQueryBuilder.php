<?php

namespace Railroad\Ecommerce\QueryBuilders;

use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Railroad\Permissions\Services\PermissionService;

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

        if (strpos($orderByColumn, '_') !== false || strpos($orderByColumn, '-') !== false) {
            // transform order by column name from snake-case to camel-case
            // example: 'created_at' sent by UI is transformed into 'createdAt' for doctrine
            $orderByColumn = Str::camel($orderByColumn);
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

        $this->andWhere(
            $this->expr()
                ->in($entityAlias . '.brand', ':brands')
        )
            ->setParameter('brands', $brands);

        return $this;
    }

    /**
     * You must use andWhere or orWhere after using this method, since it uses a where statement.
     *
     * @param Request $request
     * @param $entityAlias
     * @param string $entityAttribute
     * @return FromRequestEcommerceQueryBuilder
     */
    public function restrictBetweenTimes(Request $request, $entityAlias, $entityAttribute = 'createdAt')
    {
        $smallDateTime =
            $request->get(
                'small_date_time',
                Carbon::now()
                    ->subDay()
                    ->toDateTimeString()
            );

        $bigDateTime =
            $request->get(
                'big_date_time',
                Carbon::now()
                    ->toDateTimeString()
            );

        $this->andWhere(
            $this->expr()
                ->gt($entityAlias . '.' . $entityAttribute, ':smallDateTime')
        )
            ->andWhere(
                $this->expr()
                    ->lte($entityAlias . '.' . $entityAttribute, ':bigDateTime')
            )
            ->setParameter('smallDateTime', $smallDateTime)
            ->setParameter('bigDateTime', $bigDateTime);

        return $this;
    }

    /**
     * You must use andWhere or orWhere after using this method, since it uses a where statement.
     *
     * @param Request $request
     * @param $entityAlias
     * @param string $entityAttribute
     *
     * @return FromRequestEcommerceQueryBuilder
     */
    public function restrictSoftDeleted(Request $request, $entityAlias, $entityAttribute = 'deletedAt')
    {
        $permissionService = app(PermissionService::class);

        if (
            $permissionService->can(auth()->id(), 'show_deleted') &&
            $request->get('view_deleted', false)
        ) {
            // if the user has 'show_deleted' permission and the request has view_deleted flag true, check entity manager settings
            if (
                $this->getEntityManager()
                    ->getFilters()
                    ->isEnabled('soft-deleteable')
            ) {
                // if the soft delete filter is enabled, toggle it
                $this->getEntityManager()
                    ->getFilters()
                    ->disable('soft-deleteable');
            }
        } else {
            $this->andWhere(
                $this->expr()
                    ->isNull($entityAlias . '.' . $entityAttribute)
            );
        }

        return $this;
    }
}
